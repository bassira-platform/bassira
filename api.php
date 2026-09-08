<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// استدعاء ملف الربط الخاص بك
require_once 'db.php'; // أو اسم ملف الاتصال الموجود لديك

// التأكد من معرف الأخصائي المسجل في الجلسة
$specialist_id = $_SESSION['user_id'] ?? null;

if (!$specialist_id) {
    echo json_encode(["status" => "error", "message" => "غير مصرح، يرجى تسجيل الدخول"]);
    exit;
}

$action = $_GET['action'] ?? '';

// 1. جلب معلومات الأخصائي المسجل حيوياً من جدول users
if ($action === 'get_profile') {
    $stmt = $pdo->prepare("SELECT full_name, specialist_type, phone, address, avatar FROM users WHERE id = ? AND user_type = 'SPECIALIST'");
    $stmt->execute([$specialist_id]);
    $specialist = $stmt->fetch();

    if ($specialist) {
        echo json_encode(["status" => "success", "data" => $specialist]);
    } else {
        echo json_encode(["status" => "error", "message" => "تعذر جلب بيانات الأخصائي"]);
    }
    exit;
}

// 2. جلب الملفات المرفقة حيوياً بربط جدول المرفقات مع المواعيد والأطفال وأولياء الأمور
if ($action === 'get_medical_files') {
    $sql = "SELECT mf.id, mf.file_title, mf.file_path, mf.file_type, mf.created_at,
                   c.full_name AS child_name, 
                   u.full_name AS parent_name
            FROM medical_files mf
            INNER JOIN appointments a ON mf.appointment_id = a.id
            INNER JOIN children c ON a.child_id = c.id
            INNER JOIN users u ON c.parent_id = u.id
            WHERE a.specialist_id = ?
            ORDER BY mf.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$specialist_id]);
    $files = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $files]);
    exit;
}
?>