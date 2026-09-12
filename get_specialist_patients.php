<?php
// get_specialist_patients.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

try {
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $specRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$specRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
        exit();
    }

    // جلب الأطفال الذين لديهم مواعيد مقبولة ACCEPTED مع هذا الأخصائي
    $sql = "SELECT DISTINCT c.id, 
                        c.full_name, 
                        c.birth_date, 
                        c.gender, 
                        c.uid,
                        hr.blood_type, 
                        hr.allergies, 
                        hr.medical_conditions
        FROM children c
        INNER JOIN appointments a ON c.id = a.child_id
        LEFT JOIN health_records hr ON c.id = hr.child_id
        WHERE a.specialist_id = ? AND a.status = 'ACCEPTED'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$specRow['id']]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $patients]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>