<?php
// إظهار الأخطاء أثناء التطوير
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// 1. استقبال البيانات وتطهيرها
$input = json_decode(file_get_contents('php://input'), true);

$name    = filter_var(trim($input['name'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
$email   = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$subject = filter_var(trim($input['subject'] ?? 'رسالة جديدة من تواصل معنا'), FILTER_SANITIZE_SPECIAL_CHARS);
$message = filter_var(trim($input['message'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);

if (!$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة بشكل صحيح.']);
    exit();
}

$apiKey = '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';
$adminEmail = 'bassira.support@gmail.com';

// دالة مساعدة لإرسال البريد عبر Brevo API
function sendBrevoEmail($apiKey, $data) {
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode === 200 || $httpCode === 201);
}

// 2. إعداد وقالب رسالة الإشعار للدعم الفني (Admin Notification)
$adminPayload = [
    'sender' => [
        'name'  => $name,
        'email' => $adminEmail // يجب استخدام البريد المعتمد في Brevo كمرسل
    ],
    'replyTo' => [
        'name'  => $name,
        'email' => $email // ليتسنى لك الرد مباشرة على بريد المستخدم عند الضغط على Reply
    ],
    'to' => [
        ['email' => $adminEmail, 'name' => 'فريق منصة بصيرة']
    ],
    'subject' => "📩 رسالة جديدة: " . $subject,
    'htmlContent' => "
        <div style='direction: rtl; font-family: Tahoma, Arial, sans-serif; padding: 20px; background-color: #f4f6f9;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e0e0e0;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>وصلتك رسالة جديدة عبر نموذج التواصل</h2>
                <hr style='border: none; border-top: 1px solid #eee; margin: 15px 0;'>
                <p><strong>اسم المرسل:</strong> {$name}</p>
                <p><strong>البريد الإلكتروني:</strong> {$email}</p>
                <p><strong>الموضوع:</strong> {$subject}</p>
                <div style='background: #f8f9fa; padding: 15px; border-right: 4px solid #3498db; margin-top: 15px; border-radius: 4px;'>
                    <p style='margin: 0; white-space: pre-wrap; color: #444;'>{$message}</p>
                </div>
            </div>
        </div>
    "
];

// 3. إعداد وقالب الرد التلقائي للمستخدم (Auto-Responder)
$userPayload = [
    'sender' => [
        'name'  => 'منصة بصيرة',
        'email' => $adminEmail
    ],
    'to' => [
        ['email' => $email, 'name' => $name]
    ],
    'subject' => 'شكرًا لتواصلك مع منصة بصيرة - تم استلام رسالتك',
    'htmlContent' => "
        <div style='direction: rtl; font-family: Tahoma, Arial, sans-serif; padding: 20px; background-color: #f4f6f9;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: #27ae60; margin: 0;'>منصة بصيرة</h1>
                </div>
                <h3 style='color: #2c3e50;'>مرحباً {$name}،</h3>
                <p style='color: #555; line-height: 1.6;'>
                    نشكرك على تواصلك معنا. تم استلام رسالتك بنجاح وسيقوم فريق الدعم الفني بمراجعتها والرد عليك في أقرب وقت ممكن.
                </p>
                <div style='background: #fdfefe; border: 1px dashed #27ae60; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <p style='margin: 0; color: #7f8c8d; font-size: 14px;'><strong>ملخص رسالتك:</strong></p>
                    <p style='margin: 5px 0 0 0; color: #333;'>{$message}</p>
                </div>
                <p style='color: #888; font-size: 13px; text-align: center; margin-top: 30px;'>
                    هذه رسالة تلقائية، لا داعي للرد عليها مباشرة.
                </p>
            </div>
        </div>
    "
];

// 4. تنفيذ إرسال البريدين
$adminSent = sendBrevoEmail($apiKey, $adminPayload);

if ($adminSent) {
    // إرسال الرد التلقائي دون تعطيل النتيجة في حال فشله
    sendBrevoEmail($apiKey, $userPayload);
    
    echo json_encode(['success' => true, 'message' => 'تم إرسال رسالتك بنجاح! سنترد عليك في أقرب وقت.']);
} else {
    echo json_encode(['success' => false, 'message' => 'تعذر إرسال الرسالة حالياً، يرجى المحاولة لاحقاً.']);
}
?>