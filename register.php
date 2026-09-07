<?php
// register.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'ملف db.php غير موجود.']);
    exit();
}
require_once 'db.php';

// استلام البيانات بحجم JSON
$input = json_decode(file_get_contents('php://input'), true);

$email     = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$otpCode   = trim($input['otp_code'] ?? '');
$fullName  = trim($input['full_name'] ?? '');
$phone     = trim($input['phone'] ?? '');
$password  = $input['password'] ?? '';
$role      = strtoupper(trim($input['role'] ?? 'PARENT'));
$specialty = trim($input['specialty'] ?? null);

// 1. التحقق من صحة المدخلات
if (!$email || empty($otpCode) || empty($fullName) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'جميع الحقول الأساسية مطلوبة.']);
    exit();
}

// 2. التحقق من الـ OTP المخزن في الجلسة
$savedOtp     = $_SESSION['register_otp'] ?? '';
$savedEmail   = $_SESSION['register_email'] ?? '';
$expiresTime  = $_SESSION['otp_expires'] ?? 0;

if (empty($savedOtp) || $savedEmail !== $email || time() > $expiresTime) {
    echo json_encode(['success' => false, 'message' => 'انتهت صلاحية رمز التحقق، يرجى طلب رمز جديد.']);
    exit();
}

if ($savedOtp !== $otpCode) {
    echo json_encode(['success' => false, 'message' => 'رمز التحقق غير صحيح.']);
    exit();
}

try {
    // 3. توليد رمز مستخدم الفريد (user_code) مثل P00001 لولي الأمر أو S00001 للأخصائي
    $prefix = ($role === 'SPECIALIST') ? 'S' : 'P';
    $userCode = $prefix . sprintf("%05d", mt_rand(1, 99999));

    // 4. تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 5. حفظ البيانات في جدول users
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("INSERT INTO users (user_code, full_name, email, phone, password, user_type, specialist_type, created_at) 
                               VALUES (:ucode, :fname, :email, :phone, :pass, :utype, :spec, NOW())");
        $stmt->execute([
            'ucode' => $userCode,
            'fname' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'pass'  => $hashedPassword,
            'utype' => ($role === 'SPECIALIST') ? 'أخصائي' : 'ولي أمر',
            'spec'  => $specialty
        ]);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $userTypeStr = ($role === 'SPECIALIST') ? 'أخصائي' : 'ولي أمر';
        $stmt = $conn->prepare("INSERT INTO users (user_code, full_name, email, phone, password, user_type, specialist_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $userCode, $fullName, $email, $phone, $hashedPassword, $userTypeStr, $specialty);
        $stmt->execute();
        $stmt->close();
    }

    // 6. مسح بيانات OTP وتسجيل دخول المستخدم مباشرة
    unset($_SESSION['register_otp'], $_SESSION['register_email'], $_SESSION['otp_expires']);

    $_SESSION['logged_in']       = true;
    $_SESSION['user_code']       = $userCode;
    $_SESSION['full_name']       = $fullName;
    $_SESSION['user_type']       = ($role === 'SPECIALIST') ? 'أخصائي' : 'ولي أمر';
    $_SESSION['specialist_type'] = $specialty;

    $redirect = ($role === 'SPECIALIST') ? 'specialistHome.html' : 'parentHome.html';

    echo json_encode([
        'success'  => true,
        'message'  => 'تم إنشاء الحساب بنجاح.',
        'redirect' => $redirect
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ البيانات بقاعدة البيانات.']);
}
?>