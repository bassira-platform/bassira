<?php
// get_specialist_profile.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

// مسح أي مخرجات نصية سابقة
ob_clean();

// 1. التحقق من الجلسة ورمز الأخصائي user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'جلسة العمل انتهت، يرجى إعادة تسجيل الدخول']);
    exit();
}

try {
    // 2. جلب بيانات الأخصائي والتخصص وعنوان العيادة
    $stmt = $pdo->prepare("SELECT 
                                id, 
                                user_code,
                                full_name, 
                                email, 
                                COALESCE(phone, '') AS phone, 
                                COALESCE(address, '') AS clinic_address, 
                                COALESCE(specialist_type, '') AS specialist_type,
                                COALESCE(avatar, '') AS avatar 
                            FROM users 
                            WHERE user_code = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_code']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $upload_dir = 'uploads/';
        if (!empty($user['avatar']) && file_exists($upload_dir . $user['avatar'])) {
            $user['avatar_url'] = $upload_dir . $user['avatar'];
        } else {
            $user['avatar_url'] = null;
        }

        echo json_encode([
            'status' => 'success', 
            'data'   => $user
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
exit();
?>