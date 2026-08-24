<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otp = trim($data['otp_code'] ?? '');
$new_password = $data['new_password'] ?? '';

if (!$email || strlen($otp) !== 6 || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit;
}

// 1. تأكيد الرمز قبل إجراء التحديث
$stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp_code = ? AND expires_at >= NOW()");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الرمز، يرجى إعادة الطلب.']);
    exit;
}

// 2. تحديث كلمة المرور بـ BCRYPT في جدول users
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
$updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$updateStmt->bind_param("ss", $hashed_password, $email);

if ($updateStmt->execute()) {
    // 3. حذف الرمز المستعمل من جدول email_otps
    $delStmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
    $delStmt->bind_param("s", $email);
    $delStmt->execute();

    echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح.']);
} else {
    echo json_encode(['success' => false, 'message' => 'تعذر تحديث كلمة المرور.']);
}

$conn->close();
?>