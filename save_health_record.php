<?php
// save_health_record.php
header('Content-Type: application/json; charset=utf-8');

require_once 'db.php'; 

try {
    // 1. استقبال البيانات الأساسية للطفل والملف الصحي
    $child_id           = $_POST['child_id'] ?? null;
    $blood_type         = $_POST['blood_type'] ?? null;
    $allergies          = $_POST['allergies'] ?? null;
    $medical_conditions = $_POST['medical_conditions'] ?? null;
    $appointment_id     = $_POST['appointment_id'] ?? null;

    if (!$child_id) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الطفل مطلوب']);
        exit;
    }

    // 2. حفظ / تحديث البيانات الصحية في جدول health_records
    // نتحقق أولاً هل للطفل سجل صحي مسبق أم لا
    $checkStmt = $pdo->prepare("SELECT id FROM health_records WHERE child_id = ?");
    $checkStmt->execute([$child_id]);
    $existingRecord = $checkStmt->fetch();

    if ($existingRecord) {
        // تحديث السجل الحالي
        $updateStmt = $pdo->prepare("
            UPDATE health_records 
            SET blood_type = ?, allergies = ?, medical_conditions = ? 
            WHERE child_id = ?
        ");
        $updateStmt->execute([$blood_type, $allergies, $medical_conditions, $child_id]);
    } else {
        // إنشاء سجل صحي جديد
        $insertStmt = $pdo->prepare("
            INSERT INTO health_records (child_id, blood_type, allergies, medical_conditions) 
            VALUES (?, ?, ?, ?)
        ");
        $insertStmt->execute([$child_id, $blood_type, $allergies, $medical_conditions]);
    }

    // 3. معالجة رفع الملف الطبي الاختياري وحفظه في جدول medical_files
    if (isset($_FILES['health_document']) && $_FILES['health_document']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['health_document']['name'];
        $fileTmp  = $_FILES['health_document']['tmp_name'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $enumFileType  = null;

        if ($ext === 'pdf') {
            $enumFileType = 'pdf';
        } elseif (in_array($ext, $allowedImages)) {
            $enumFileType = 'image';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'نوع الملف غير مدعوم، يرجى رفع صورة أو ملف PDF فقط']);
            exit;
        }

        $newFileName = 'doc_' . time() . '_' . uniqid() . '.' . $ext;
        $uploadDir   = 'uploads/medical_files/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $filePath)) {
            $fileStmt = $pdo->prepare("
                INSERT INTO medical_files (appointment_id, file_title, file_path, file_type) 
                VALUES (?, ?, ?, ?)
            ");
            $fileStmt->execute([
                $appointment_id ? $appointment_id : null,
                $fileName,
                $filePath,
                $enumFileType
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'فشل في حفظ الملف المرفق بالسيرفر']);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'تم حفظ الملف الصحي والبيانات بنجاح']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>