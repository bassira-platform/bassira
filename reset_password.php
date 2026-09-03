<?php
// إخفاء الأخطاء النصية المباشرة لمنع تخريب استجابة JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// 1. التحقق من وجود ملف الاتصال
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'ملف db.php غير موجود.']);
    exit();
}
require_once 'db.php';

// 2. استقبال المدخلات
$data = json_decode(file_get_contents("php://input"), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otp = trim($data['otp_code'] ?? $data['otp'] ?? '');
$new_password = $data['new_password'] ?? '';

if (!$email || strlen($otp) !== 6 || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة أو غير مكتملة.']);
    exit();
}

try {
    $isValid = false;
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // 3. التعامل مع PDO
    if (isset($pdo) && $pdo instanceof PDO) {
        // فحص الرمز
        $stmt = $pdo->prepare("SELECT id FROM email_otps WHERE email = :email AND otp_code = :otp AND expires_at >= NOW()");
        $stmt->execute(['email' => $email, 'otp' => $otp]);
        
        if ($stmt->fetch()) {
            // تحديث كلمة المرور في جدول users
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
            $updateStmt->execute(['password' => $hashed_password, 'email' => $email]);

            // حذف الرمز المستعمل
            $delStmt = $pdo->prepare("DELETE FROM email_otps WHERE email = :email");
            $delStmt->execute(['email' => $email]);

            echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الرمز أو أنه غير صحيح.']);
        }
    } 
    // 4. التعامل مع MySQLi
    elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp_code = ? AND expires_at >= NOW()");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();

            // تحديث كلمة المرور
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $updateStmt->bind_param("ss", $hashed_password, $email);
            $updateStmt->execute();
            $updateStmt->close();

            // حذف الرمز
            $delStmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
            $delStmt->bind_param("s", $email);
            $delStmt->execute();
            $delStmt->close();

            echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح.']);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الرمز أو أنه غير صحيح.']);
        }
        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()]);
}
?>