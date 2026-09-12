<?php
// update_appointment_status.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

// دالة مساعدة لضمان إرجاع JSON نظيف وبدون أخطاء
function sendJsonResponse($data) {
    if (ob_get_length()) ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    sendJsonResponse(['status' => 'error', 'message' => 'غير مصرح بالوصول، يرجى إعادة تسجيل الدخول']);
}

try {
    // 1. جلب id الرقمي للأخصائي
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $specRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$specRow) {
        sendJsonResponse(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
    }

    $specialistId    = $specRow['id'];
    $appointmentId  = $_POST['appointment_id'] ?? null;
    $newStatus       = $_POST['status'] ?? null;
    $appointmentDate = $_POST['appointment_date'] ?? null;
    $appointmentTime = $_POST['appointment_time'] ?? null;
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if (!$appointmentId || !$newStatus) {
        sendJsonResponse(['status' => 'error', 'message' => 'معرف الموعد والحالة مطلوبان']);
    }

    // التأكد أن الموعد يخص الأخصائي الحالي
    $stmtChk = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND specialist_id = ?");
    $stmtChk->execute([$appointmentId, $specialistId]);
    $appointment = $stmtChk->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        sendJsonResponse(['status' => 'error', 'message' => 'الموعد غير موجود أو لا تملك صلاحية تعديله']);
    }

    // -------------------------------------------------------------
    // معالجة الحالات بناءً على دورة حياة الموعد
    // -------------------------------------------------------------

    if ($newStatus === 'ACCEPTED') {
        $finalDate = !empty($appointmentDate) ? $appointmentDate : $appointment['appointment_date'];
        $finalTime = !empty($appointmentTime) ? $appointmentTime : $appointment['appointment_time'];

        if (empty($finalDate) || empty($finalTime)) {
            sendJsonResponse(['status' => 'error', 'message' => 'يرجى تحديد التاريخ والوقت لقبول الموعد']);
        }

        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'ACCEPTED', appointment_date = ?, appointment_time = ? WHERE id = ?");
        $stmtUpd->execute([$finalDate, $finalTime, $appointmentId]);

        sendJsonResponse(['status' => 'success', 'message' => 'تم قبول الموعد وتحديد توقيته بنجاح.']);
    } 
    
    elseif ($newStatus === 'REJECTED') {
        if (empty($rejectionReason)) {
            sendJsonResponse(['status' => 'error', 'message' => 'يرجى إدخال سبب رفض الموعد']);
        }

        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        $stmtUpd->execute([$rejectionReason, $appointmentId]);

        sendJsonResponse(['status' => 'success', 'message' => 'تم رفض الموعد وتسجيل السبب بنجاح.']);
    } 

    elseif ($newStatus === 'WAITLIST') {
        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'WAITLIST' WHERE id = ?");
        $stmtUpd->execute([$appointmentId]);

        sendJsonResponse(['status' => 'success', 'message' => 'تم نقل الطلب إلى قائمة الانتظار بنجاح.']);
    } 

    elseif ($newStatus === 'ATTENDED') {
        // تسجيل حضور الموعد وحذفه من الجدول النشط
        $stmtDel = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmtDel->execute([$appointmentId]);

        // التصعيد التلقائي: البحث عن أول موعد في قائمة الانتظار (WAITLIST)
        $stmtEsc = $pdo->prepare("SELECT id FROM appointments WHERE specialist_id = ? AND status = 'WAITLIST' ORDER BY id ASC LIMIT 1");
        $stmtEsc->execute([$specialistId]);
        $nextWaitlist = $stmtEsc->fetch(PDO::FETCH_ASSOC);

        $escalated = false;
        if ($nextWaitlist) {
            $stmtUpdWait = $pdo->prepare("UPDATE appointments SET status = 'PENDING' WHERE id = ?");
            $stmtUpdWait->execute([$nextWaitlist['id']]);
            $escalated = true;
        }

        $msg = 'تم تسجيل حضور الموعد وإنجازه بنجاح.';
        if ($escalated) {
            $msg .= ' وتم تلقائياً تصعيد طلب من قائمة الانتظار مخصص لك لمراجعته!';
        }

        sendJsonResponse(['status' => 'success', 'message' => $msg]);
    }

    sendJsonResponse(['status' => 'error', 'message' => 'حالة غير معروفة']);

} catch (PDOException $e) {
    sendJsonResponse(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>