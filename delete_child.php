<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

// التحقق من تسجيل دخول ولي الأمر
if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_id  = $_POST['child_id'] ?? null;
    $parent_id = $_SESSION['user_id'];

    if (!$child_id) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الطفل مفقود']);
        exit();
    }

    try {
        // حذف الطفل فقط إذا كان يخص ولي الأمر الحالي
        $sql  = "DELETE FROM children WHERE id = ? AND parent_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$child_id, $parent_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'تم حذف الطفل بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'لم يتم العثور على الطفل أو لا تملك صلاحية حذفه']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ أثناء الحذف: ' . $e->getMessage()]);
    }
}