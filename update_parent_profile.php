<?php
// update_parent_profile.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز المستخدم user_code
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($full_name) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'الاسم والبريد الإلكتروني مطلوبان']);
        exit();
    }

    try {
        // 2. جلب id المعرف الرقمي لولي الأمر باستخدام user_code
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
        $stmtUser->execute([$_SESSION['user_code']]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userRow) {
            echo json_encode(['status' => 'error', 'message' => 'حساب ولي الأمر غير موجود']);
            exit();
        }

        $parent_id = $userRow['id'];

        // 3. معالجة رفع صورة الملف الشخصي إن وجدت
        $avatar_sql = "";
        $params = [$full_name, $email, $phone, $address];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $parent_id . '_' . time() . '.' . $ext;
            $upload_dir = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_sql = ", avatar = ?";
                $params[] = $new_filename;
            }
        }

        // 4. معالجة تغيير كلمة المرور إن تم إدخالها
        $password_sql = "";
        if (!empty($new_password)) {
            $password_sql = ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $params[] = $parent_id;

        // 5. تنفيذ تحديث بيانات ولي الأمر
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? {$avatar_sql} {$password_sql} WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // تحديث الاسم في الجلسة ليظهر التغيير فوراً في الواجهة
        $_SESSION['full_name'] = $full_name;

        echo json_encode(['status' => 'success', 'message' => 'تم التحديث بنجاح']);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء الحفظ: ' . $e->getMessage()]);
        exit();
    }
}
?>