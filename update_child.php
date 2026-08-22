<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id   = $_POST['child_id'] ?? null;
    $parent_id  = $_SESSION['user_id'];
    $full_name  = trim($_POST['full_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');

    if (!$child_id || empty($full_name) || empty($birth_date) || empty($gender)) {
        echo json_encode(['status' => 'error', 'message' => 'جميع الحقول مطلوبة']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE children SET full_name = ?, birth_date = ?, gender = ? WHERE id = ? AND parent_id = ?");
        $stmt->execute([$full_name, $birth_date, $gender, $child_id, $parent_id]);

        echo json_encode(['status' => 'success', 'message' => 'تم التحديث بنجاح']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
}