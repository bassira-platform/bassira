<?php
// get_specialist_profile.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

ob_clean();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'جلسة العمل انتهت، يرجى إعادة تسجيل الدخول']);
    exit();
}

try {
    // 1. جلب بيانات الأخصائي الحالية
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

    // 2. جلب قائمة التخصصات المتاحة ديناميكياً من قاعدة البيانات
    // (ملاحظة: إذا كان لديك جدول خاص بالتخصصات مثل specialties استخدمه، أو اجلب التخصصات المميزة من جدول المستخدمين)
    $stmtSpecialties = $pdo->query("SELECT DISTINCT specialist_type FROM users WHERE specialist_type IS NOT NULL AND specialist_type != ''");
    $specialties = $stmtSpecialties->fetchAll(PDO::FETCH_COLUMN);

    if ($user) {
        $upload_dir = 'uploads/';
        if (!empty($user['avatar']) && file_exists($upload_dir . $user['avatar'])) {
            $user['avatar_url'] = $upload_dir . $user['avatar'];
        } else {
            $user['avatar_url'] = null;
        }

        echo json_encode([
            'status'      => 'success', 
            'data'        => $user,
            'specialties' => $specialties // إرسال قائمة التخصصات مع الاستجابة
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