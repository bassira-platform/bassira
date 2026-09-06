<?php
// منع إخراج أخطاء PHP مباشرة لتجنب كسر صيغة JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// 1. التحقق من وجود ملف الاتصال
if (!file_exists('db.php')) {
    echo json_encode(['success' => false, 'message' => 'ملف db.php غير موجود.']);
    exit();
}
require_once 'db.php';

// 2. استقبال البيانات المدخلة
$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$otp = trim($input['otp_code'] ?? $input['otp'] ?? '');

if (!$email || strlen($otp) !== 6) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة أو رمز غير صالح.']);
    exit();
}

try {
    $isValid = false;

    // 3. التحقق باستخدام PDO إذا كان مفتاح الاتصال $pdo
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT id FROM email_otps WHERE email = :email AND otp_code = :otp AND expires_at >= NOW()");
        $stmt->execute(['email' => $email, 'otp' => $otp]);
        if ($stmt->fetch()) {
            $isValid = true;
        }
    } 
    // 4. التحقق باستخدام MySQLi إذا كان مفتاح الاتصال $conn
    elseif (isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT id FROM email_otps WHERE email = ? AND otp_code = ? AND expires_at >= NOW()");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $isValid = true;
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'خطأ في الاتصال بقاعدة البيانات.']);
        exit();
    }

    // 5. إرجاع النتيجة
    if ($isValid) {
        echo json_encode(['success' => true, 'message' => 'الرمز صحيح ويمكنك الآن تعيين كلمة المرور.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'الرمز غير صحيح أو انتهت صلاحيته.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الفحص: ' . $e->getMessage()]);
}
?>