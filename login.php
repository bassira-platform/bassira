<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php'; // أو 'db.php' حسب اسم الملف المعتمد لديك للاتصال بالداتابيز

// استقبال البيانات عبر $_POST لتتوافق مع FormData
$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'يرجى إدخال البريد الإلكتروني/الرمز وكلمة المرور.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, user_code, full_name, email, password, user_type, specialist_type 
        FROM users 
        WHERE email = ? OR user_code = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'بيانات الدخول غير صحيحة (البريد/الرمز أو كلمة المرور خطأ).'
        ]);
        exit;
    }

    // ==========================================
    // ضبط الجلسة بالمعايير المطلوبة في جميع الملفات
    // ==========================================
    $_SESSION['logged_in']       = true; // هذا هو المفتاح الناقص الذي كان يسبب المشكلة!
    $_SESSION['user_id']         = $user['id'];
    $_SESSION['user_code']       = $user['user_code'];
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['user_type']       = $user['user_type'];
    $_SESSION['specialist_type'] = $user['specialist_type'];

    // تحديد رابط التوجيه بحسب نوع الحساب
    $redirectUrl = ($user['user_type'] === 'SPECIALIST') ? 'specialistHome.html' : 'parentHome.html';

    echo json_encode([
        'status'   => 'success',
        'message'  => 'تم تسجيل الدخول بنجاح.',
        'redirect' => $redirectUrl
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}