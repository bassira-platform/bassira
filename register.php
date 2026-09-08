<?php
// ضبط جلسات الكوكيز لضمان استقرارها على InfinityFree
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'true');
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db.php';

try {
    // 2. استقبال بيانات JSON المرسلة من الجافاسكريبت
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'لم يتم استقبال أي بيانات.']);
        exit;
    }

    $email          = trim($input['email'] ?? '');
    $otpCode        = trim($input['otp_code'] ?? '');
    $fullName       = trim($input['full_name'] ?? '');
    $phone          = trim($input['phone'] ?? '');
    $password       = $input['password'] ?? '';
    
    // استقبال نوع المستخدم (مع مراعاة المسميين user_type أو role)
    $userType       = trim($input['user_type'] ?? $input['role'] ?? 'PARENT'); 

    // استقبال التخصص (مع مراعاة المسميين specialist_type أو specialty)
    $specialistType = trim($input['specialist_type'] ?? $input['specialty'] ?? '');
     $specialistType = !empty($specialistType) ? strtoupper($specialistType) : null;
    if ($userType === 'PARENT' || empty($specialistType)) {
        $specialistType = null;
    }

    // 3. التحقق من الـ OTP المخزن في الجلسة (Session)
    if (empty($_SESSION['otp']) || empty($_SESSION['otp_email'])) {
        echo json_encode(['success' => false, 'message' => 'انتهت جلسة التحقق، يرجى إعادة طلب الرمز.']);
        exit;
    }

    if ($_SESSION['otp_email'] !== $email || (string)$_SESSION['otp'] !== (string)$otpCode) {
        echo json_encode(['success' => false, 'message' => 'رمز التحقق غير صحيح.']);
        exit;
    }

    // 4. التحقق من عدم تكرار البريد الإلكتروني
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل بالفعل.']);
        exit;
    }

    // 5. تشفير كلمة المرور وتوليد تسلسل الكود
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $seqStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = ?");
    $seqStmt->execute([$userType]);
    $seqRow = $seqStmt->fetch();
    $nextSeq = ($seqRow['count'] ?? 0) + 1;

    $prefix = ($userType === 'PARENT') ? 'P' : 'S';
    $userCode = $prefix . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

    // 6. الإدراج في جدول users
    $sql = "INSERT INTO users (
                user_code, 
                type_seq, 
                full_name, 
                phone, 
                email, 
                password, 
                user_type, 
                specialist_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userCode,
        $nextSeq,
        $fullName,
        $phone,
        $email,
        $hashedPassword,
        $userType,
        $specialistType
    ]);

    $newUserId = $pdo->lastInsertId();

    // 7. إخلاء بيانات OTP وحفظ بيانات المستخدم بداخل الجلسة
    unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time']);

    $_SESSION['user_id']   = $newUserId;
    $_SESSION['user_code'] = $userCode;
    $_SESSION['email']     = $email;
    $_SESSION['user_type'] = $userType;

    echo json_encode([
        'success'  => true,
        'message'  => 'تم إنشاء الحساب بنجاح!',
        'redirect' => ($userType === 'SPECIALIST') ? 'specialistHome.html' : 'parentHome.html'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()
    ]);
}
?>