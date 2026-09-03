<?php
// إخفاء الأخطاء النصية لضمان إرجاع JSON نظيف دائماً
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

if (!file_exists('db.php')) {
    echo json_encode(['logged_in' => false, 'message' => 'ملف db.php غير موجود.']);
    exit();
}
require_once 'db.php';

// جلب التوكن من الكوكيز
$token = $_COOKIE['bassira_remember_token'] ?? '';

if (empty($token)) {
    echo json_encode(['logged_in' => false]);
    exit();
}

try {
    $hashedToken = hash('sha256', $token);
    $user = null;

    // 1. الاستعلام باستخدام PDO (اعتماد user_code و user_type)
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("
            SELECT u.user_code, u.email, u.user_type 
            FROM user_tokens t 
            JOIN users u ON t.user_id = u.user_code 
            WHERE t.token = :token AND t.expires_at > NOW()
        ");
        $stmt->execute(['token' => $hashedToken]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    // 2. الاستعلام باستخدام MySQLi (اعتماد user_code و user_type)
    elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("
            SELECT u.user_code, u.email, u.user_type 
            FROM user_tokens t 
            JOIN users u ON t.user_id = u.user_code 
            WHERE t.token = ? AND t.expires_at > NOW()
        ");
        $stmt->bind_param("s", $hashedToken);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }

    if ($user) {
        // تحديد الصفحة المناسبة بناءً على قيمة user_type المخزنة في جدول users
        $redirectUrl = 'parentHome.html'; // الافتراضي لولي الأمر (parent)
        
        $type = strtolower(trim($user['user_type']));
        
        if ($type === 'specialist' || $type === 'אخصائي' || $type === 'أخصائي') {
            $redirectUrl = 'specialistHome.html';
        } elseif ($type === 'child' || $type === 'طفل') {
            $redirectUrl = 'childHome.html';
        }

        echo json_encode([
            'logged_in' => true,
            'user_code' => $user['user_code'],
            'user_type' => $user['user_type'],
            'redirect'  => $redirectUrl
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['logged_in' => false, 'error' => $e->getMessage()]);
}
?>