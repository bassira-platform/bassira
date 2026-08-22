<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id     = $_POST['child_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $parent_id    = $_SESSION['user_id'];

    if (!$child_id || empty($new_password)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال كلمة المرور الجديدة']);
        exit();
    }

    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE children SET password = ? WHERE id = ? AND parent_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hashed_password, $child_id, $parent_id]);

        echo json_encode(['status' => 'success', 'message' => 'تم تغيير كلمة المرور بنجاح']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
}