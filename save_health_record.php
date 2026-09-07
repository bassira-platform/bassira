<?php
// save_health_record.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id_input     = $_POST['child_id'] ?? null;
    $blood_type         = trim($_POST['blood_type'] ?? '');
    $allergies          = trim($_POST['allergies'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');

    if (!$child_id_input) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الطفل غير محدد']);
        exit();
    }

    try {
        // 2. جلب معرف id الرقمي لولي الأمر باستعمال user_code من الجلسة
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
        $stmtUser->execute([$_SESSION['user_code']]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userRow) {
            echo json_encode(['status' => 'error', 'message' => 'حساب ولي الأمر غير موجود']);
            exit();
        }

        $parent_id = $userRow['id'];

        // 3. التحقق من وجود الطفل وملكيته لولي الأمر وجلب id الرقمي الصحيح
        $stmtChild = $pdo->prepare("SELECT id FROM children WHERE (id = ? OR uid = ?) AND parent_id = ? LIMIT 1");
        $stmtChild->execute([$child_id_input, $child_id_input, $parent_id]);
        $childRow = $stmtChild->fetch(PDO::FETCH_ASSOC);

        if (!$childRow) {
            echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطفل أو لا تملك صلاحية التعديل عليه']);
            exit();
        }

        $real_child_id = $childRow['id'];

        // 4. حفظ أو تحديث السجل الطبي
        $sql = "INSERT INTO health_records (child_id, blood_type, allergies, medical_conditions)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                blood_type = VALUES(blood_type),
                allergies = VALUES(allergies),
                medical_conditions = VALUES(medical_conditions)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$real_child_id, $blood_type, $allergies, $medical_conditions]);

        echo json_encode(['status' => 'success', 'message' => 'تم حفظ الملف الصحي بنجاح']);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit();
    }
}
?>