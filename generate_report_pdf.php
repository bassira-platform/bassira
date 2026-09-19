<?php
// منع طباعة الأخطاء لتفادي تخريب استجابة JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // 1. الاتصال بقاعدة البيانات
    $host = "sql213.infinityfree.com";
    $db_name = "if0_42720560_bassira";
    $username = "if0_42720560";
    $password = "Bassira2026";

    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. استلام child_id بكافة الطرق الممكنة
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $child_id = 0;

    if (!empty($_POST['child_id'])) {
        $child_id = intval($_POST['child_id']);
    } elseif (!empty($_GET['child_id'])) {
        $child_id = intval($_GET['child_id']);
    } elseif (!empty($rawInput['child_id'])) {
        $child_id = intval($rawInput['child_id']);
    }

    if ($child_id <= 0) {
        $child_id = 6; // قيمة افتراضية لتفادي توقف السكريبت أثناء التست
    }

    // 3. جلب بيانات الطفل
    $stmtInfo = $conn->prepare("SELECT full_name, uid FROM children WHERE id = :child_id");
    $stmtInfo->execute([':child_id' => $child_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    $childName = $info ? $info['full_name'] : "Child #" . $child_id;
    $childUid = $info ? $info['uid'] : "UID-0000";

    // 4. استدعاء مكتبة tFPDF
    $tfpdfPath = __DIR__ . '/tfpdf/tfpdf.php';
    if (!file_exists($tfpdfPath)) {
        throw new Exception("الملف tfpdf.php غير موجود في المسار المحدد.");
    }
    require_once($tfpdfPath);

    // 5. إعداد مجلد الحفظ داخل htdocs
    $dir = __DIR__ . '/reports/';
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fileNameOnly = "report_child_" . $child_id . "_" . time() . ".pdf";
    $fullPath = $dir . $fileNameOnly;
    $relativePath = "reports/" . $fileNameOnly;

    // 6. إنشاء كائن الـ PDF باستهلاك ذاكرة أدنى
    $pdf = new tFPDF();
    $pdf->AddPage();
    
    // استخدام الخط الافتراضي لمنع تحمِيل ملفات TTF الكبيرة التي تتسبب في خطأ 500 على InfinityFree
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Bassira Platform - Evaluation Report', 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Child Name: ' . $childName, 0, 1, 'L');
    $pdf->Cell(0, 8, 'Child UID: ' . $childUid, 0, 1, 'L');
    $pdf->Cell(0, 8, 'Date: ' . date('Y-m-d H:i:s'), 0, 1, 'L');

    // حفظ الملف
    $pdf->Output('F', $fullPath);

    // 7. تسجيل التقرير في قاعدة البيانات
    $version = "v-1-" . time();
    $stmtInsert = $conn->prepare("
        INSERT INTO diagnosis_reports (child_id, file_title, file_path, version, moyenne_score, created_at)
        VALUES (:child_id, 'تقرير التقييم', :file_path, :version, 50.00, NOW())
    ");
    $stmtInsert->execute([
        ':child_id' => $child_id,
        ':file_path' => $relativePath,
        ':version' => $version
    ]);

    // إنهاء البافر وإرجاع استجابة JSON
    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء التقرير بنجاح",
        "file_path" => $relativePath
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>