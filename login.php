<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$remember   = isset($_POST['remember']); // الفحص إن تم تحديد خيار "تذكرني"

if (empty($identifier) || empty($password)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'يرجى إدخال البريد الإلكتروني/الرمز وكلمة المرور.'
    ]);
    exit;
}

try {
    // 1. الاستعلام المرن ليدعم PDO أو MySQLi
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
            'message' => 'بيانات الدخول غير صحيحة (البريد/الرمز أو كلمة المرور خطأ).'
        ]);
        exit;
    }

    // 2. تعيين جلسة PHP
    $_SESSION['logged_in']       = true;
    $_SESSION['user_code']       = $user['user_code'];
    $_SESSION['full_name']       = $user['full_name'];
    $_SESSION['user_type']       = $user['user_type'];
    $_SESSION['specialist_type'] = $user['specialist_type'];

    // 3. معالجة خيار "تذكرني" (Remember Me)
    if ($remember) {
        $randomToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $randomToken);
        $expiresAt   = date('Y-m-d H:i:s', strtotime('+30 days'));

        if (isset($pdo) && $pdo instanceof PDO) {
            $tStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:uid, :token, :expires)");
            $tStmt->execute(['uid' => $user['user_code'], 'token' => $hashedToken, 'expires' => $expiresAt]);
        } elseif (isset($conn) && $conn instanceof mysqli) {
            $tStmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $tStmt->bind_param("sss", $user['user_code'], $hashedToken, $expiresAt);
            $tStmt->execute();
        }

        setcookie('bassira_remember_token', $randomToken, [
            'expires'  => time() + (86400 * 30),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // 4. تحديد رابط التوجيه
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