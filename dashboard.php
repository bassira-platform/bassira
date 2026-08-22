<?php
// dashboard.php
session_start();

// حظر الوصول في حال عدم تسجيل الدخول
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - منصة بصيرة</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 2rem;">
    <h2>مرحباً بك، <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h2>
    <p><strong>رمز الحساب:</strong> <?php echo htmlspecialchars($_SESSION['user_code']); ?></p>
    <p><strong>نوع الحساب:</strong> <?php echo $_SESSION['user_type'] === 'PARENT' ? 'ولي أمر' : 'أخصائي'; ?></p>
    
    <?php if ($_SESSION['user_type'] === 'SPECIALIST'): ?>
        <p><strong>التخصص:</strong> <?php echo htmlspecialchars($_SESSION['specialist_type']); ?></p>
    <?php endif; ?>

    <br>
    <a href="logout.php" style="color: red;">تسجيل الخروج</a>
</body>
</html>