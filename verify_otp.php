<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otp = trim($data['otp_code'] ?? '');

if (!$email || strlen($otp) !== 6) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة.']);
    exit;
}

// التحقق من المطابقة وتاريخ الانتهاء
$stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp_code = ? AND expires_at >= NOW()");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'الرمز صحيح.']);
} else {
    echo json_encode(['success' => false, 'message' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
}

$stmt->close();
$conn->close();
?>