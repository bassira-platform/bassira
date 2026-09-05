<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

// 1. التحقق من كعكة "تذكرني" الكوكيز الممتد
$token = $_COOKIE['bassira_remember_token'] ?? '';

if (!empty($token)) {
    $hashedToken = hash('sha256', $token);

    $tokenRow = null;
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT u.user_code, u.full_name, u.user_type, u.specialist_type 
                               FROM user_tokens t 
                               JOIN users u ON t.user_id = u.user_code 
                               WHERE t.token = :token AND t.expires_at > NOW() LIMIT 1");
        $stmt->execute(['token' => $hashedToken]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT u.user_code, u.full_name, u.user_type, u.specialist_type 
                               FROM user_tokens t 
                               JOIN users u ON t.user_id = u.user_code 
                               WHERE t.token = ? AND t.expires_at > NOW() LIMIT 1");
        $stmt->bind_param("s", $hashedToken);
        $stmt->execute();
        $tokenRow = $stmt->get_result()->fetch_assoc();
    }

    if ($tokenRow) {
        // تجديد بيانات الجلسة إذا كان التوكن صحيحاً
        $_SESSION['logged_in']       = true;
        $_SESSION['user_code']       = $tokenRow['user_code'];
        $_SESSION['full_name']       = $tokenRow['full_name'];
        $_SESSION['user_type']       = $tokenRow['user_type'];
        $_SESSION['specialist_type'] = $tokenRow['specialist_type'];
        $_SESSION['is_remembered']   = true;

        $type = strtolower(trim($tokenRow['user_type']));
        $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';

        echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
        exit;
    }
}

// 2. إذا كانت الجلسة مفتوحة ولكن لم يتم وضع علامة "تذكرني" (is_remembered)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['is_remembered']) && $_SESSION['is_remembered'] === true) {
    $type = strtolower(trim($_SESSION['user_type'] ?? ''));
    $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';
    
    echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
    exit;
}

// إذا لم يتم تحديد "تذكرني"، يُعتبر غير مسجل للدخول تلقائياً عند زيارة index.html
echo json_encode(['logged_in' => false]);
?>