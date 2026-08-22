<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id           = $_POST['child_id'] ?? null;
    $blood_type         = trim($_POST['blood_type'] ?? '');
    $allergies          = trim($_POST['allergies'] ?? '');
    $medical_conditions = trim($_POST['medical_conditions'] ?? '');

    if (!$child_id) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الطفل غير محدد']);
        exit();
    }

    try {
        // تحديث البيانات إذا كانت موجودة مسبقاً أو إدراجها (ON DUPLICATE KEY UPDATE)
        $sql = "INSERT INTO health_records (child_id, blood_type, allergies, medical_conditions)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                blood_type = VALUES(blood_type),
                allergies = VALUES(allergies),
                medical_conditions = VALUES(medical_conditions)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$child_id, $blood_type, $allergies, $medical_conditions]);

        echo json_encode(['status' => 'success', 'message' => 'تم حفظ الملف الصحي بنجاح']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
    }
}