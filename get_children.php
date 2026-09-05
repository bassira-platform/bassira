<?php
// get_children.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. جلب رمز أو معرف المستخدم المرن من الجلسة
$user_code_or_id = $_SESSION['user_code'] ?? $_SESSION['user_id'] ?? null;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($user_code_or_id)) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

try {
    // 2. الحصول على id الرقمي الصحيح للمستخدم من جدول users
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? OR id = ? LIMIT 1");
    $stmtUser->execute([$user_code_or_id, $user_code_or_id]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب ولي الأمر غير موجود']);
        exit();
    }

    $parent_id = $userRow['id'];

    // 3. استعلام جلب الأطفال باستخدام parent_id الرقمي
    $sql = "SELECT id, uid, full_name, birth_date, gender, created_at 
            FROM children 
            WHERE parent_id = ? 
            ORDER BY id DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data'   => $children
    ]);
    exit();

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    exit();
}
?>