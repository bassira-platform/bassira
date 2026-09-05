<?php
// get_health_record.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

$child_id_input = $_GET['child_id'] ?? null;

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
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطفل أو لا تملك صلاحية الوصول إليه']);
        exit();
    }

    $real_child_id = $childRow['id'];

    // 4. جلب السجل الطبي للطفل
    $stmt = $pdo->prepare("SELECT blood_type, allergies, medical_conditions FROM health_records WHERE child_id = ? LIMIT 1");
    $stmt->execute([$real_child_id]);
    $healthRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($healthRecord) {
        echo json_encode([
            'status' => 'success',
            'data'   => $healthRecord
        ]);
    } else {
        // إرجاع كائن فارغ في حال عدم وجود سجل طبي سابق
        echo json_encode([
            'status' => 'success',
            'data'   => [
                'blood_type'         => '',
                'allergies'          => '',
                'medical_conditions' => ''
            ]
        ]);
    }
    exit();

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    exit();
}
?>