<?php
// update_appointment_status.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

ob_clean();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول، يرجى إعادة تسجيل الدخول']);
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

    $specialistId    = $specRow['id'];
    $appointmentId  = $_POST['appointment_id'] ?? null;
    $newStatus       = $_POST['status'] ?? null;
    $appointmentDate = $_POST['appointment_date'] ?? null;
    $appointmentTime = $_POST['appointment_time'] ?? null;
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if (!$appointmentId || !$newStatus) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الموعد والحالة مطلوبان']);
        exit();
    }

    // التأكد أن الموعد يخص الأخصائي الحالي
    $stmtChk = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND specialist_id = ?");
    $stmtChk->execute([$appointmentId, $specialistId]);
    $appointment = $stmtChk->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        echo json_encode(['status' => 'error', 'message' => 'الموعد غير موجود أو لا تملك صلاحية تعديله']);
        exit();
    }

    // -------------------------------------------------------------
    // معالجة الحالات بناءً على دورة حياة الموعد
    // -------------------------------------------------------------

    if ($newStatus === 'ACCEPTED') {
        // عند القبول يجب تحديد التاريخ والوقت إذا لم ينقلا مسبقاً
        $finalDate = !empty($appointmentDate) ? $appointmentDate : $appointment['appointment_date'];
        $finalTime = !empty($appointmentTime) ? $appointmentTime : $appointment['appointment_time'];

        if (empty($finalDate) || empty($finalTime)) {
            echo json_encode(['status' => 'error', 'message' => 'يرجى تحديد التاريخ والوقت لقبول الموعد']);
            exit();
        }

        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'ACCEPTED', appointment_date = ?, appointment_time = ? WHERE id = ?");
        $stmtUpd->execute([$finalDate, $finalTime, $appointmentId]);

        echo json_encode(['status' => 'success', 'message' => 'تم قبول الموعد وتحديد توقيته بنجاح.']);
        exit();
    } 
    
    elseif ($newStatus === 'REJECTED') {
        // الرفض يتطلب كتابة سبب الرفض
        if (empty($rejectionReason)) {
            echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال سبب رفض الموعد']);
            exit();
        }

        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        $stmtUpd->execute([$rejectionReason, $appointmentId]);

        echo json_encode(['status' => 'success', 'message' => 'تم رفض الموعد وتسجيل السبب بنجاح.']);
        exit();
    } 

    elseif ($newStatus === 'WAITLIST') {
        // الإحالة لقائمة الانتظار
        $stmtUpd = $pdo->prepare("UPDATE appointments SET status = 'WAITLIST' WHERE id = ?");
        $stmtUpd->execute([$appointmentId]);

        echo json_encode(['status' => 'success', 'message' => 'تم نقل الطلب إلى قائمة الانتظار بنجاح.']);
        exit();
    } 

    elseif ($newStatus === 'ATTENDED') {
        // 1. تسجيل حضور الموعد وحذفه من الجدول النشط (أو تحديثه)
        // تفريغ سجل الموعد للحفاظ على خفة الجدول
        $stmtDel = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmtDel->execute([$appointmentId]);

        // 2. التصعيد التلقائي: البحث عن أول موعد في قائمة الانتظار (WAITLIST) بحسب الأقدمية
        $stmtEsc = $pdo->prepare("SELECT id FROM appointments WHERE specialist_id = ? AND status = 'WAITLIST' ORDER BY id ASC LIMIT 1");
        $stmtEsc->execute([$specialistId]);
        $nextWaitlist = $stmtEsc->fetch(PDO::FETCH_ASSOC);

        $escalated = false;
        if ($nextWaitlist) {
            // تحويل أول موعد احتياطي تلقائياً إلى PENDING ليقوم الأخصائي بتحديد موعد له
            $stmtUpdWait = $pdo->prepare("UPDATE appointments SET status = 'PENDING' WHERE id = ?");
            $stmtUpdWait->execute([$nextWaitlist['id']]);
            $escalated = true;
        }

        $msg = 'تم تسجيل حضور الموعد وإنجازه بنجاح.';
        if ($escalated) {
            $msg .= ' وتم تلقائياً تصعيد طلب من قائمة الانتظار مخصص لك لمراجعته!';
        }

        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'حالة غير معروفة']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>