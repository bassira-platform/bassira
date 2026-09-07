<?php
// login.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التأكد من وجود ملف الاتصال
if (!file_exists('db.php')) {
    echo json_encode(['status' => 'error', 'message' => 'ملف db.php غير موجود على السيرفر.']);
    exit();
}
require_once 'db.php';

// التقاط الأخطاء غير المتوقعة لتفادي قطع الاتصال
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(200);
        echo json_encode([
            'status' => 'error',
            'message' => 'حدث خطأ في الخادم: ' . $error['message']
        ]);
        exit();
    }
});

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$remember   = isset($_POST['remember']);

if (empty($identifier) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور.']);
    exit();
}

try {
    $user = null;

    // دعم PDO المعتمد في db.php
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :id OR user_code = :id LIMIT 1");
        $stmt->execute(['id' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR user_code = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل الاتصال بقاعدة البيانات.']);
        exit();
    }

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'بيانات الدخول غير صحيحة.']);
        exit();
    }

    // تعيين بيانات الجلسة
    $_SESSION['logged_in']       = true;
    $_SESSION['user_id']         = $user['id'] ?? null;
    $_SESSION['user_code']       = $user['user_code'] ?? '';
    $_SESSION['full_name']       = $user['full_name'] ?? '';
    $_SESSION['user_type']       = $user['user_type'] ?? '';
    $_SESSION['specialist_type'] = $user['specialist_type'] ?? '';

    // معالجة خيار "تذكرني" بحماية معززة
    if ($remember) {
        try {
            $rawToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            if (isset($pdo) && $pdo instanceof PDO) {
                $tStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:u_id, :token, :exp)");
                $tStmt->execute([
                    'u_id'  => $user['id'] ?? $user['user_code'],
                    'token' => $hashedToken,
                    'exp'   => $expiresAt
                ]);
            }

            setcookie('bassira_remember_token', $rawToken, [
                'expires'  => time() + (30 * 24 * 60 * 60),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } catch (Exception $tokenEx) {
            // تجاهل خطأ التوكن والسماح بتسجيل الدخول
        }
    }

    $type = strtoupper(trim($user['user_type'] ?? ''));
    $redirect = ($type === 'SPECIALIST' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';

    echo json_encode([
        'status'   => 'success',
        'message'  => 'تم تسجيل الدخول بنجاح.',
        'redirect' => $redirect
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في النظام: ' . $e->getMessage()]);
}
?>