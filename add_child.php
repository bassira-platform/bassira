<?php
// add_child.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. جلب معرف المستخدم أو رمزه من الجلسة
$user_code_or_id = $_SESSION['user_code'] ?? $_SESSION['user_id'] ?? null;

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($user_code_or_id)) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_name = trim($_POST['child_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $password   = $_POST['child_password'] ?? '';

    if (empty($child_name) || empty($birth_date) || empty($gender) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى ملء جميع الحقول المطلوبة']);
        exit();
    }

    try {
        // 2. البحث عن قيمة id الرقمية الصحيحة لولي الأمر من جدول users
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? OR id = ? LIMIT 1");
        $stmtUser->execute([$user_code_or_id, $user_code_or_id]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userRow) {
            echo json_encode(['status' => 'error', 'message' => 'حساب ولي الأمر غير موجود في قاعدة البيانات']);
            exit();
        }

        $parent_id = $userRow['id']; // استخدام الرقم التعريفي الصحيح المقترن بـ FOREIGN KEY

        // 3. توليد UID وتشفير كلمة المرور
        $uid = 'C-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 4. إدراج بيانات الطفل
        $sql = "INSERT INTO children (parent_id, uid, full_name, birth_date, gender, password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parent_id, $uid, $child_name, $birth_date, $gender, $hashed_password]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'تم إنشاء حساب الطفل بنجاح',
            'uid'     => $uid
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit();
    }
}
?>