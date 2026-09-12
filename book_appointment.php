<?php
// book_appointment.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

ob_clean();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'جلسة العمل انتهت، يرجى إعادة تسجيل الدخول']);
    exit();
}

try {
    $parentCode   = $_SESSION['user_code'];
    $specialistId = $_POST['specialist_id'] ?? null;
    $childId      = $_POST['child_id'] ?? null;
    
    // التاريخ والوقت أصبحا اختيارين (يمكن إرسالهما خاليين ليحددهما الأخصائي)
    $bookingDate  = !empty($_POST['booking_date']) ? $_POST['booking_date'] : (!empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null);
    $bookingTime  = !empty($_POST['booking_time']) ? $_POST['booking_time'] : (!empty($_POST['appointment_time']) ? $_POST['appointment_time'] : null);
    
    $notes        = trim($_POST['notes'] ?? '');

    // التحقق من إدخال الأخصائي والطفل
    if (!$specialistId || !$childId) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى اختيار الأخصائي والطفل لمتابعة الطلب']);
        exit();
    }

    // حفظ الموعد في قاعدة البيانات بحالة PENDING
    $sql = "INSERT INTO appointments (
                parent_code, 
                specialist_id, 
                child_id, 
                appointment_date, 
                appointment_time, 
                notes, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, 'PENDING')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $parentCode,
        $specialistId,
        $childId,
        $bookingDate,
        $bookingTime,
        $notes
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'تم إرسال طلب الحجز بنجاح! وهو الآن قيد الانتظار لحين مراجعته وتحديد الموعد من قِبل الأخصائي.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
exit();
?>