<?php
header('Content-Type: application/json; charset=utf-8');

// ضبط إعدادات السيشن لتكون آمنة وتستمر بشكل صحيح
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

session_start();

require_once 'db.php';

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$remember   = isset($_POST['remember']);

if (empty($identifier) || empty($password)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'يرجى إدخال البريد الإلكتروني/الرمز وكلمة المرور.'
    ]);
    exit;
}

try {
    $user = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT user_code, full_name, email, password, user_type, specialist_type FROM users WHERE email = ? OR user_code = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT user_code, full_name, email, password, user_type, specialist_type FROM users WHERE email = ? OR user_code = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'بيانات الدخول غير صحيحة.'
        ]);
        exit;
    }

    // 1. تعيين كافة مفاتيح الجلسة المحتملة لتجنب التعارض مع ملفات API
    $_SESSION['logged_in']       = true;
    $_SESSION['user_code']       = $user['user_code'];
    $_SESSION['user_id']         = $user['user_code']; // توحيد المعرف
    $_SESSION['parent_id']       = $user['user_code']; // لضمان توافق get_parent_profile.php
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['user_type']       = $user['user_type'];
    $_SESSION['specialist_type'] = $user['specialist_type'];
    $_SESSION['is_remembered']   = $remember;

    // 2. التعامل مع خيار "تذكرني"
    if ($remember) {
        $cookieExpiry = time() + (365 * 24 * 60 * 60); 
        $dbExpiresAt  = date('Y-m-d H:i:s', $cookieExpiry);

        $randomToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $randomToken);

        if (isset($pdo) && $pdo instanceof PDO) {
            $tStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:uid, :token, :expires)");
            $tStmt->execute(['uid' => $user['user_code'], 'token' => $hashedToken, 'expires' => $dbExpiresAt]);
        } elseif (isset($conn) && $conn instanceof mysqli) {
            $tStmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $tStmt->bind_param("sss", $user['user_code'], $hashedToken, $dbExpiresAt);
            $tStmt->execute();
        }

        setcookie('bassira_remember_token', $randomToken, [
            'expires'  => $cookieExpiry,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        if (isset($_COOKIE['bassira_remember_token'])) {
            setcookie('bassira_remember_token', '', time() - 3600, '/');
        }
    }

    // 3. كتابة الجلسة وإغلاق الملف فوراً لمنع السباق البياناتي (Race Condition)
    session_write_close();

    // 4. توجيه المستخدم حسب نوع الحساب
    $type = strtolower(trim($user['user_type']));
    $redirectUrl = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';

    echo json_encode([
        'status'   => 'success',
        'message'  => 'تم تسجيل الدخول بنجاح.',
        'redirect' => $redirectUrl
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()
    ]);
}
?>