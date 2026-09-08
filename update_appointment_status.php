<?php
// update_appointment_status.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

// استقبال البيانات
$appointmentId = $_POST['appointment_id'] ?? null;
$newStatus     = $_POST['status'] ?? null;

// التحقق من القيم المدخلة
if (!$appointmentId || !in_array($newStatus, ['ACCEPTED', 'REJECTED'])) {
    echo json_encode(['status' => 'error', 'message' => 'بيانات الطلب غير صالحة']);
    exit();
}

try {
    // 1. التأكد من معرف الأخصائي المسجل
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $specRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$specRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
        exit();
    }

    // 2. تحديث حالة الموعد بشرط أن يكون الموعد تائباً لنفس الأخصائي
    $sql = "UPDATE appointments SET status = ? WHERE id = ? AND specialist_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newStatus, $appointmentId, $specRow['id']]);

    if ($stmt->rowCount() > 0) {
        $msg = ($newStatus === 'ACCEPTED') ? 'تم قبول الموعد بنجاح' : 'تم رفض الموعد';
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الموعد أو لم تتغير الحالة']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>