<?php
session_start();
require_once 'db.php';

// 1. مسح التوكن من قاعدة البيانات والكوكيز
$token = $_COOKIE['bassira_remember_token'] ?? '';

if (!empty($token)) {
    $hashedToken = hash('sha256', $token);

    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE token = :token");
        $stmt->execute(['token' => $hashedToken]);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->bind_param("s", $hashedToken);
        $stmt->execute();
    }
}

// 2. إبطال الكعكة من المتصفح
setcookie('bassira_remember_token', '', time() - 3600, '/');

// 3. إنهاء جلسة PHP
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: index.html");
exit();
?>