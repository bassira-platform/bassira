<?php
// delete_child.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id = $_POST['child_id'] ?? null;

    if (!$child_id) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الطفل مفقود']);
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

        // 3. حذف الطفل بشرط اقترانه بـ parent_id الرقمي
        $sql  = "DELETE FROM children WHERE id = ? AND parent_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$child_id, $parent_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'تم حذف الطفل بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطفل أو لا تملك صلاحية حذفه']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء الحذف: ' . $e->getMessage()]);
    }
}
?>