<?php
// add_child.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// التأكد من تسجيل دخول الأب
if (!isset($_SESSION['logged_in']) || $_SESSION['user_id'] === null) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parent_id  = $_SESSION['user_id'];
    $child_name = trim($_POST['child_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender     = trim($_POST['gender'] ?? '');
    $password   = $_POST['child_password'] ?? '';

    if (empty($child_name) || empty($birth_date) || empty($gender) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى ملء جميع الحقول المطلوب']);
        exit();
    }

    try {
        // توليد الـ UID التلقائي (مثال: C-8F9A2B)
        $uid = 'C-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO children (parent_id, uid, full_name, birth_date, gender, password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parent_id, $uid, $child_name, $birth_date, $gender, $hashed_password]);

        echo json_encode([
            'status' => 'success',
            'message' => 'تم إنشاء حساب الطفل بنجاح',
            'uid' => $uid
        ]);
        exit();

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
        exit();
    }
}