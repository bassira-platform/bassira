<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit;
}

$parent_code = $_SESSION['user_code'];

try {
    // جلب مواعيد أطفال ولي الأمر مع تفاصيل الأخصائي والطفل بالربط مع جدول users
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.status,
            a.appointment_date,
            a.appointment_time,
            a.rejection_reason,
            a.notes,
            c.full_name AS child_name,
            u.full_name AS specialist_name,
            u.specialist_type
        FROM appointments a
        INNER JOIN children c ON a.child_id = c.id
        INNER JOIN users u ON a.specialist_id = u.id
        WHERE a.parent_code = :parent_code
        ORDER BY a.id DESC
    ");
    
    $stmt->execute([':parent_code' => $parent_code]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $bookings]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}