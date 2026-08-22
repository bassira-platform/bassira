<?php
// منع إخراج أي تحذيرات أو أخطاء نصية تفقد صيغة JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

// مسح أي مخرجات نصية سابقة
ob_clean();

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'جلسة العمل انتهت، يرجى إعادة تسجيل الدخول']);
    exit();
}

try {
    $parent_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT 
                                id, 
                                full_name, 
                                email, 
                                COALESCE(phone, '') AS phone, 
                                COALESCE(address, '') AS address, 
                                COALESCE(avatar, '') AS avatar 
                            FROM users 
                            WHERE id = ?");
    $stmt->execute([$parent_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حساب المستخدم غير موجود']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
exit();