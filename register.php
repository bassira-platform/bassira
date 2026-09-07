<?php
// ضبط إعدادات الكوكيز والجلسة لضمان استقرارها على InfinityFree
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'true');
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // 1. استقبال بيانات JSON المرسلة من Front-end
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
    $userType       = trim($input['user_type'] ?? $input['role'] ?? 'PARENT'); // يدعم الاسمين
    $specialistType = trim($input['specialist_type'] ?? $input['specialty'] ?? null);

    // إذا كان المستخدم ولي أمر، نتأكد من وضع قيمة التخصص NULL
    if ($userType === 'PARENT' || empty($specialistType)) {
        $specialistType = null;
    }

    // 2. التحقق من الـ OTP المخزن في الجلسة (Session)
    if (empty($_SESSION['otp']) || empty($_SESSION['otp_email'])) {
        echo json_encode(['success' => false, 'message' => 'انتهت جلسة التحقق، يرجى إعادة طلب الرمز.']);
        exit;
    }

    if ($_SESSION['otp_email'] !== $email || (string)$_SESSION['otp'] !== (string)$otpCode) {
        echo json_encode(['success' => false, 'message' => 'رمز التحقق غير صحيح.']);
        exit;
    }

    // 3. الاتصال بقاعدة البيانات
    // ⚠️ استبدل البيانات التالية ببيانات قاعدة بياناتك من InfinityFree
    $db_host = "sqlXXX.infinityfree.com"; 
    $db_user = "if0_XXXXXXXX";          
    $db_pass = "YOUR_PASSWORD";         
    $db_name = "if0_XXXXXXXX_dbname";   

    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 4. التحقق من عدم تكرار البريد الإلكتروني
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل بالفعل.']);
        exit;
    }

    // 5. توليد التسلسل وتشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // حساب التسلسل الحركي بناءً على نوع الحساب (type_seq)
    $seqStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = ?");
    $seqStmt->execute([$userType]);
    $seqRow = $seqStmt->fetch();
    $nextSeq = ($seqRow['count'] ?? 0) + 1;

    // إنشاء كود معرف للمستخدم تلقائياً (مثال: P-0001 أو S-0001)
    $prefix = ($userType === 'PARENT') ? 'P' : 'S';
    $userCode = $prefix . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

    // 6. الإدراج المطابق لأعمدة جدول قاعدة البيانات الخاص بك
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

    // 7. تنظيف الـ Session وحفظ بيانات الجلسة للمستخدم الجديد
    unset($_SESSION['otp']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_time']);

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