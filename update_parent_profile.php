<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id    = $_SESSION['user_id'];
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
        // معالجة رفع الصورة إن وجدت
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

        // معالجة تغيير كلمة المرور إن تم إدخالها
        $password_sql = "";
        if (!empty($new_password)) {
            $password_sql = ", password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }

        $params[] = $parent_id;

        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? {$avatar_sql} {$password_sql} WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['status' => 'success', 'message' => 'تم الحديث بنجاح']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء الحفظ: ' . $e->getMessage()]);
    }
}