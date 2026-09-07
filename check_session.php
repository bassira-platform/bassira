<?php
// check_session.php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once 'db.php';

// 1. التحقق مما إذا كانت الجلسة الحالية قائمة بالفعل
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $type = strtolower(trim($_SESSION['user_type'] ?? ''));
    $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';
    
    echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
    exit();
}

// 2. إذا لم تكن الجلسة قائمة، البحث عن كوكيز "تذكرني"
if (isset($_COOKIE['bassira_remember_token'])) {
    $rawToken = $_COOKIE['bassira_remember_token'];
    $hashedToken = hash('sha256', $rawToken);
    $now = date('Y-m-d H:i:s');

    try {
        $tokenData = null;

        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("SELECT ut.user_id, u.full_name, u.user_type, u.specialist_type 
                                   FROM user_tokens ut 
                                   JOIN users u ON ut.user_id = u.user_code 
                                   WHERE ut.token = :token AND ut.expires_at > :now LIMIT 1");
            $stmt->execute(['token' => $hashedToken, 'now' => $now]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare("SELECT ut.user_id, u.full_name, u.user_type, u.specialist_type 
                                    FROM user_tokens ut 
                                    JOIN users u ON ut.user_id = u.user_code 
                                    WHERE ut.token = ? AND ut.expires_at > ? LIMIT 1");
            $stmt->bind_param("ss", $hashedToken, $now);
            $stmt->execute();
            $tokenData = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        // إذا تم العثور على توكن صالحة، استعادة الجلسة تلقائياً
        if ($tokenData) {
            $_SESSION['logged_in']       = true;
            $_SESSION['user_code']       = $tokenData['user_id'];
            $_SESSION['full_name']       = $tokenData['full_name'] ?? '';
            $_SESSION['user_type']       = $tokenData['user_type'] ?? '';
            $_SESSION['specialist_type'] = $tokenData['specialist_type'] ?? '';

            $type = strtolower(trim($tokenData['user_type'] ?? ''));
            $redirect = ($type === 'specialist' || $type === 'أخصائي') ? 'specialistHome.html' : 'parentHome.html';

            echo json_encode(['logged_in' => true, 'redirect' => $redirect]);
            exit();
        }
    } catch (Exception $e) {
        // الاستمرار عادي في حال حدوث خطأ
    }
}

// 3. في حال عدم وجود جلسة ولا توكن صالحة
echo json_encode(['logged_in' => false]);
exit();
?>