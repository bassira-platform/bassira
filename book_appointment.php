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
    $bookingDate  = $_POST['booking_date'] ?? null;
    $bookingTime  = $_POST['booking_time'] ?? null;
    $notes        = trim($_POST['notes'] ?? '');

    if (!$specialistId || !$childId || !$bookingDate || !$bookingTime) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى ملء جميع الحقول المطلوبة (الطفل، التاريخ، الوقت)']);
        exit();
    }

    // معالجة الملف الطبي الاختياري (PDF / الصورة)
    $medicalFileDB = null;
    if (isset($_FILES['medical_file']) && $_FILES['medical_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['medical_file']['tmp_name'];
        $fileName    = $_FILES['medical_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['status' => 'error', 'message' => 'نوع الملف غير مدعوم! يرجى رفع ملف PDF أو صورة (PNG, JPG)']);
            exit();
        }

        $uploadDir = 'uploads/medical_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'med_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $destPath    = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $medicalFileDB = $newFileName;
        }
    }

    // حفظ الموعد في قاعدة البيانات
    $sql = "INSERT INTO appointments (
                parent_code, 
                specialist_id, 
                child_id, 
                appointment_date, 
                appointment_time, 
                notes, 
                medical_file, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $parentCode,
        $specialistId,
        $childId,
        $bookingDate,
        $bookingTime,
        $notes,
        $medicalFileDB
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => 'تم إرسال طلب حجز الموعد بنجاح! سينتظر موافقة الأخصائي.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
exit();
?>