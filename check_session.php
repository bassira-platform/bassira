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

// دالة مسح الكوكيز والجلسة عند الفشل لمنع الحلقة التكرارية
function clearInvalidSession() {
    unset($_SESSION['logged_in'], $_SESSION['user_code'], $_SESSION['full_name'], $_SESSION['user_type'], $_SESSION['specialist_type']);
    if (isset($_COOKIE['bassira_remember_token'])) {
        setcookie('bassira_remember_token', '', time() - 3600, '/');
    }
}

// 1. فحص الجلسة الحالية أولاً (Active PHP Session)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !empty($_SESSION['user_type'])) {
    $type = strtolower(trim($_SESSION['user_type']));
    $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';
    
    echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
    exit();
}

// 2. إذا لم تكن الجلسة مفتوحة، نفحص الكوكيز (تذكرني)
$token = $_COOKIE['bassira_remember_token'] ?? '';

if (!empty($token)) {
    $hashedToken = hash('sha256', $token);
    $tokenRow = null;

    try {
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
    } catch (Exception $e) {
        $tokenRow = null;
    }

    // إذا كان التوكن صحيحاً وموجوداً في قاعدة البيانات
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
    } else {
        // إذا كان الكوكيز غير صالح أو انتهت صلاحيته في قاعدة البيانات، نمسحه فوراً لكسر الحلقة
        clearInvalidSession();
    }
}

// إذا لم ينطبق أي شرط مما سبق، فالمستخدم غير مسجل الدخول قطعاً
echo json_encode(['logged_in' => false]);
?>