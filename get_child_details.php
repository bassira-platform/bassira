<?php
// get_child_details.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

$child_id = $_GET['id'] ?? null;

if (!$child_id) {
    echo json_encode(['status' => 'error', 'message' => 'معرف الطفل مفقود']);
    exit();
}

try {
    // 2. جلب معرف id الرقمي لولي الأمر باستعمال user_code من الجلسة
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب ولي الأمر غير موجود']);
        exit();
    }

    $parent_id = $userRow['id'];

    // 3. جلب تفاصيل الطفل مع التحقق من ملكيته لولي الأمر
    $stmt = $pdo->prepare("SELECT id, full_name, birth_date, gender FROM children WHERE id = ? AND parent_id = ?");
    $stmt->execute([$child_id, $parent_id]);
    $child = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($child) {
        echo json_encode(['status' => 'success', 'data' => $child]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على البيانات']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>