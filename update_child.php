<?php
// update_child.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id   = $_POST['child_id'] ?? null;
    $full_name  = trim($_POST['full_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');

    if (!$child_id || empty($full_name) || empty($birth_date) || empty($gender)) {
        echo json_encode(['status' => 'error', 'message' => 'جميع الحقول مطلوبة']);
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

        // 3. تحديث بيانات الطفل مع التحقق من الملكية (يدعم البحث بـ id أو uid)
        $stmt = $pdo->prepare("UPDATE children SET full_name = ?, birth_date = ?, gender = ? WHERE (id = ? OR uid = ?) AND parent_id = ?");
        $stmt->execute([$full_name, $birth_date, $gender, $child_id, $child_id, $parent_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'تم التحديث بنجاح']);
        } else {
            // تنبيه في حال عدم تغيير أي بيانات أو عدم العثور على السجل
            echo json_encode(['status' => 'success', 'message' => 'لم يتم تغيير أي بيانات أو السجل غير موجود']);
        }
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit();
    }
}
?>