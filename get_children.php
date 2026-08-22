<?php
// get_children.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

try {
    $parent_id = $_SESSION['user_id'];
    
    // إضافة uid إلى الاستعلام
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