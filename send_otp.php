<?php
header('Content-Type: application/json; charset=utf-8');

// 1. تضمين ملف الاتصال بقاعدة البيانات ومكتبة PHPMailer
require_once 'db.php';

// يرجى التأكد من مسار ملفات PHPMailer الصحيح في مشروعك
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';// أو استدعاء الملفات يدوياً require 'PHPMailer/src/PHPMailer.php';

// 2. استقبال البيانات المدخلة
$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال بريد إلكتروني صحيح.']);
    exit;
}

try {
    // 3. توليد رمز OTP وتحديد زمن الصلاحية (10 دقائق)
    $otpCode = (string) random_int(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // 4. حفظ الرمز في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO email_otps (email, otp_code, expires_at) VALUES (:email, :otp, :expires) 
                          ON DUPLICATE KEY UPDATE otp_code = :otp_update, expires_at = :expires_update");
    $stmt->execute([
        'email'          => $email,
        'otp'            => $otpCode,
        'expires'        => $expiresAt,
        'otp_update'     => $otpCode,
        'expires_update' => $expiresAt
    ]);

    // 5. إعداد وإرسال البريد الإلكتروني عبر PHPMailer
    $mail = new PHPMailer(true);

    // ==========================================
    // 🛑 ضع معلوماتك الشخصية وسيرفرك هنا 🛑
    // ==========================================
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';             // عنوان سيرفر SMTP (مثل smtp.gmail.com أو سيرفر استضافتك)
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bassira.support@gmail.com';       // بريدك الإلكتروني
    $mail->Password   = 'upgk rsah uwrb hpux';          // كلمة مرور التطبيقات (App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;                          // البورت (587 لـ STARTTLS أو 465 لـ SSL)
    $mail->CharSet    = 'UTF-8';

    // بيانات المرسل والمستقبل
    $mail->setFrom('bassira.support@gmail.com', 'منصة بصيرة');
    $mail->addAddress($email);

    // محتوى الرسالة
    $mail->isHTML(true);
    $mail->Subject = 'رمز التحقق الخاص بك - منصة بصيرة';
    $mail->Body    = "
        <div style='direction: rtl; font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;'>
            <h2 style='color: #2c3e50;'>مرحباً بك في منصة بصيرة</h2>
            <p>رمز التحقق الخاص بك لإتمام عملية التسجيل هو:</p>
            <h1 style='color: #27ae60; letter-spacing: 5px;'>$otpCode</h1>
            <p>هذا الرمز صالحة لمدة 10 دقائق فقط.</p>
        </div>
    ";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'تم إرسال رمز التحقق بنجاح.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'فشل إرسال البريد الإلكتروني: ' . $mail->ErrorInfo]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}