<?php
// check_session.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!file_exists('db.php')) {
    echo json_encode(['logged_in' => false]);
    exit();
}
require_once 'db.php';

// 1. التحقق من الكوكيز أولاً (تذكرني)
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
        $stmt->close();
    }

    if ($tokenRow) {
        $_SESSION['logged_in']       = true;
        $_SESSION['user_code']       = $tokenRow['user_code'];
        $_SESSION['full_name']       = $tokenRow['full_name'];
        $_SESSION['user_type']       = $tokenRow['user_type'];
        $_SESSION['specialist_type'] = $tokenRow['specialist_type'];

        $type = strtolower(trim($tokenRow['user_type']));
        $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';

        echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
        exit();
    }
}

// 2. التحقق من وجود جلسة نشطة أساساً
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $type = strtolower(trim($_SESSION['user_type'] ?? ''));
    $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';
    
    echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
    exit();
}

echo json_encode(['logged_in' => false]);
?>