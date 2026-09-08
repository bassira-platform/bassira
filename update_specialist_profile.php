<?php
// update_specialist_profile.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// 1. التحقق من وجود الجلسة ورمز الأخصائي
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name       = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $clinic_address  = trim($_POST['clinic_address'] ?? $_POST['address'] ?? '');
    $specialist_type = trim($_POST['specialist_type'] ?? $_POST['specialty'] ?? '');
    $new_password    = $_POST['new_password'] ?? '';

    if (empty($full_name) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'الاسم والبريد الإلكتروني مطلوبان']);
        exit();
    }

    try {
        // 2. جلب id للأخصائي باستخدام user_code
        $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
        $stmtUser->execute([$_SESSION['user_code']]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$userRow) {
            echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
            exit();
        }

        $specialist_id = $userRow['id'];

        // 3. معالجة رفع الصورة الشخصية
        $avatar_sql = "";
        $params = [$full_name, $email, $phone, $clinic_address, $specialist_type];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_spec_' . $specialist_id . '_' . time() . '.' . $ext;
            $upload_dir = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
                $avatar_sql = ", avatar = ?";
                $params[] = $new_filename;
            }
        }

        // 4. معالجة كلمة المرور
        $password_sql = "";
        if (!empty($new_password)) {
            $password_sql = ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $params[] = $specialist_id;

        // 5. تحديث بيانات الأخصائي في قاعدة البيانات
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, specialist_type = ? {$avatar_sql} {$password_sql} WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['full_name'] = $full_name;

        echo json_encode(['status' => 'success', 'message' => 'تم تحديث الملف المهني بنجاح']);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء الحفظ: ' . $e->getMessage()]);
        exit();
    }
}
?>