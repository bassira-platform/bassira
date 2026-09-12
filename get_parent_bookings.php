<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php'; // عدل اسم الملف بحسب ما هو لديك

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit;
}

$parent_id = $_SESSION['user_id'];

try {
    // جلب مواعيد أطفال ولي الأمر مع تفاصيل الأخصائي والطفل
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.status,
            a.appointment_date,
            a.appointment_time,
            a.rejection_reason,
            a.notes,
            c.child_name,
            s.full_name AS specialist_name,
            s.specialist_type
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN specialists s ON a.specialist_id = s.id
        WHERE c.parent_id = :parent_id
        ORDER BY a.id DESC
    ");
    
    $stmt->execute([':parent_id' => $parent_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $bookings]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}