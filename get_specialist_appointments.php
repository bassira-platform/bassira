<?php
// get_specialist_appointments.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

try {
    // 1. جلب id الرقمي للأخصائي
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $specRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$specRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
        exit();
    }

    // 2. جلب الحجوزات المرتبطة بالأخصائي مع بيانات الطفل وولي الأمر
    $sql = "SELECT a.id, 
               a.appointment_date AS booking_date, 
               a.appointment_time AS booking_time, 
               a.status, 
               a.notes,
               c.id AS child_id, 
               c.full_name AS child_name, 
               c.birth_date, 
               c.gender,
               u.full_name AS parent_name, 
               u.phone AS parent_phone
        FROM appointments a
        JOIN children c ON a.child_id = c.id
        JOIN users u ON c.parent_id = u.id
        WHERE a.specialist_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$specRow['id']]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $appointments]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>