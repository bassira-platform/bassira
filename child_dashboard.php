<?php
session_start();

// 1. الاتصال بقاعدة البيانات
$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026"; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

$error_msg = "";

// 2. معالجة تسجيل الدخول عند تقديم النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $uid = trim($_POST['uid']);
    $pass = trim($_POST['password']);

    if (!empty($uid) && !empty($pass)) {
        $stmt = $conn->prepare("SELECT * FROM children WHERE uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $uid]);
        $child = $stmt->fetch(PDO::FETCH_ASSOC);

        // التحقق من وجود الطفل وكلمة المرور المشفرة
        if ($child && password_verify($pass, $child['password'])) {
            $_SESSION['child_id'] = $child['id'];
            $_SESSION['child_name'] = $child['full_name'];
            $_SESSION['child_uid'] = $child['uid'];
            header("Location: child_dashboard.php");
            exit();
        } else {
            $error_msg = "رمز المستخدم (UID) أو كلمة المرور غير صحيحة!";
        }
    } else {
        $error_msg = "يرجى إدخال جميع البيانات المطلوب!";
    }
}

// 3. معالجة تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: child_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة البطل الصغير - منصة بصيرة</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); color: #fff; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* شاشة تسجيل الدخول */
        .login-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: rgba(30, 41, 59, 0.85); border: 2px solid #6366f1; border-radius: 24px; padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center; }
        .card h2 { color: #38bdf8; margin-bottom: 10px; font-size: 28px; }
        .card p { color: #94a3b8; margin-bottom: 25px; }
        .input-group { margin-bottom: 20px; text-align: right; }
        .input-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: bold; }
        .input-group input { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #475569; background: #0f172a; color: #fff; font-size: 16px; outline: none; }
        .input-group input:focus { border-color: #38bdf8; }
        .btn { width: 100%; background: #10b981; color: white; border: none; padding: 14px; font-size: 18px; font-weight: bold; border-radius: 12px; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #059669; transform: translateY(-2px); }
        .error { background: #ef444422; color: #f87171; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }

        /* لوحة التحكم والترحيب */
        header { background: rgba(15, 23, 42, 0.8); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 50px; height: 50px; background: #6366f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 20px; }
        .logout-btn { color: #f87171; text-decoration: none; font-weight: bold; border: 1px solid #f87171; padding: 8px 16px; border-radius: 8px; transition: 0.2s; }
        .logout-btn:hover { background: #f87171; color: white; }

        .dashboard-content { max-width: 1100px; margin: 40px auto; padding: 0 20px; flex: 1; }
        .welcome-banner { background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%); border-radius: 20px; padding: 35px; text-align: right; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .welcome-banner h1 { font-size: 32px; margin-bottom: 10px; }
        .welcome-banner p { font-size: 18px; opacity: 0.9; }

        .section-title { font-size: 24px; color: #38bdf8; margin-bottom: 20px; text-align: right; border-right: 4px solid #38bdf8; padding-right: 12px; }

        /* شبكة الألعاب */
        .games-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1dfr)); gap: 25px; }
        .game-card { background: #1e293b; border-radius: 18px; border: 1px solid #334155; overflow: hidden; transition: transform 0.3s, border-color 0.3s; text-align: right; }
        .game-card:hover { transform: translateY(-8px); border-color: #38bdf8; }
        .game-banner { height: 160px; background-size: cover; background-position: center; display: flex; align-items: flex-end; padding: 15px; }
        .game-1-bg { background: linear-gradient(180deg, transparent, #1e293b), #d97706; }
        .game-2-bg { background: linear-gradient(180deg, transparent, #1e293b), #a855f7; }
        .game-body { padding: 20px; }
        .game-body h3 { font-size: 20px; margin-bottom: 10px; color: #f8fafc; }
        .game-body p { color: #94a3b8; font-size: 14px; line-height: 1.5; margin-bottom: 20px; }
        .play-btn { display: block; text-align: center; background: #38bdf8; color: #0f172a; text-decoration: none; font-weight: bold; padding: 12px; border-radius: 10px; transition: 0.2s; }
        .play-btn:hover { background: #0284c7; color: white; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['child_id'])): ?>

    <!-- نموذج تسجيل الدخول للطفل -->
    <div class="login-container">
        <div class="card">
            <h2>مرحباً بك يا بطل! 🌟</h2>
            <p>سجّل دخولك للبدء في المغامرة واللعب</p>

            <?php if (!empty($error_msg)): ?>
                <div class="error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <label for="uid">رمز الطفل (UID):</label>
                    <input type="text" id="uid" name="uid" placeholder="مثال: C-F8286D" required>
                </div>
                <div class="input-group">
                    <label for="password">كلمة المرور:</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn">دخول اللعبة 🚀</button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- لوحة التحكم الترحيبية للطفل -->
    <header>
        <div class="user-info">
            <div class="avatar"><?php echo mb_substr($_SESSION['child_name'], 0, 1, 'UTF-8'); ?></div>
            <div>
                <h3><?php echo htmlspecialchars($_SESSION['child_name']); ?></h3>
                <small style="color: #94a3b8;">معرّف الطفل: <?php echo htmlspecialchars($_SESSION['child_uid']); ?></small>
            </div>
        </div>
        <a href="?logout=true" class="logout-btn">خروج</a>
    </header>

    <div class="dashboard-content">
        <!-- بنر الترحيب -->
        <div class="welcome-banner">
            <h1>أهلاً بك يا <?php echo htmlspecialchars($_SESSION['child_name']); ?> في منصة بصيرة! 🎉</h1>
            <p>جاهز للمغامرة اليوم؟ اختر إحدى الألعاب في الأسفل واستمتع باللعب بعينيك!</p>
        </div>

        <!-- قسم الألعاب -->
        <h2 class="section-title">مركز الألعاب والتحديات 🎮</h2>
        <div class="games-grid">
            
            <!-- اللعبة الأولى: مغامرة فرفور (التتبع الاستكشافي) -->
            <div class="game-card">
                <div class="game-banner game-1-bg">
                    <span style="font-size: 40px;">🐿️</span>
                </div>
                <div class="game-body">
                    <h3>مغامرة فرفور في الغابة السحرية</h3>
                    <p>ساعد السنجاب فرفور في جمع الجوز واستكشاف الكائنات الحية والرموز بعينيك!</p>
                    <a href="game2.html?child_id=<?php echo $_SESSION['child_id']; ?>" class="play-btn">ابدأ اللعب الآن 🚀</a>
                </div>
            </div>

            <!-- اللعبة الثانية: صيد الفراشات والرموز (تتبع حركة العين السريعة) -->
            <div class="game-card">
                <div class="game-banner game-2-bg">
                    <span style="font-size: 40px;">🦋</span>
                </div>
                <div class="game-body">
                    <h3>تحدي الفراشات الحرفية</h3>
                    <p>اختبر تركيزك ودقة عينيك في تتبع الحروف والأنماط البصرية المتحركة بسرعة!</p>
                    <a href="game.html?stage=SLD&child_id=<?php echo $_SESSION['child_id']; ?>" class="play-btn">ابدأ التحدي 🎯</a>
                </div>
            </div>

        </div>
    </div>

<?php endif; ?>

</body>
</html>