<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);

$email     = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otpCode   = trim($input['otp_code'] ?? '');
$fullName  = trim($input['full_name'] ?? '');
$phone     = trim($input['phone'] ?? '');
$password  = $input['password'] ?? '';
$userType  = strtoupper(trim($input['role'] ?? 'PARENT'));

// إسناد قيمة null لنوع التخصص إذا كان المستخدم ولي أمر
$specialistType = ($userType === 'SPECIALIST' && !empty($input['specialty'])) ? trim($input['specialty']) : null;

if (!$email || !$otpCode || !$fullName || !$password) {
    echo json_encode(['success' => false, 'message' => 'جميع البيانات الأساسية مطلوبة.']);
    exit;
}

try {
    // 1. التحقق من الرمز ووقت الصلاحية
    $stmt = $pdo->prepare("SELECT * FROM email_otps WHERE email = ? AND otp_code = ? AND expires_at > NOW()");
    $stmt->execute([$email, $otpCode]);
    $otpRecord = $stmt->fetch();

    if (!$otpRecord) {
        echo json_encode(['success' => false, 'message' => 'رمز التحقق غير صحيح أو انتهت صلاحيته.']);
        exit;
    }

    // 2. التحقق من عدم وجود البريد مسبقاً
    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetch()) {
        echo json_encode(['success' => false, 'message' => 'هذا البريد الإلكتروني مُسجل بالفعل.']);
        exit;
    }

    // 3. تشفير كلمة المرور وتوليد user_code فريد
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $userCode = 'USR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // 4. إدراج المستخدم مع مطابقة أسماء أعمدة الجدول (user_type و specialist_type)
    $insertStmt = $pdo->prepare("
        INSERT INTO users (user_code, full_name, email, phone, password, user_type, specialist_type)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([$userCode, $fullName, $email, $phone, $hashedPassword, $userType, $specialistType]);
    $userId = $pdo->lastInsertId();

    // 5. حذف الرمز المستعمل
    $pdo->prepare("DELETE FROM email_otps WHERE email = ?")->execute([$email]);

    // 6. تسجيل الجلسة (Session)
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_code'] = $userCode;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['user_type'] = $userType;

    // توجيه الصفحة حسب نوع المستخدم
    $redirectUrl = ($userType === 'SPECIALIST') ? 'specialistHome.html' : 'parentHome.html';

    echo json_encode([
        'success'  => true,
        'message'  => 'تم إنشاء الحساب بنجاح.',
        'redirect' => $redirectUrl
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}