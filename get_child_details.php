<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

$child_id = $_GET['id'] ?? null;
$parent_id = $_SESSION['user_id'];

if (!$child_id) {
    echo json_encode(['status' => 'error', 'message' => 'معرف الطفل مفقود']);
    exit();
}

$stmt = $pdo->prepare("SELECT id, full_name, birth_date, gender FROM children WHERE id = ? AND parent_id = ?");
$stmt->execute([$child_id, $parent_id]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if ($child) {
    echo json_encode(['status' => 'success', 'data' => $child]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على البيانات']);
}