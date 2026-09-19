<?php
// إيقاف طباعة الأخطاء في HTML لمنع تشويه JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // 1. استخدام المسار المطلق للمكتبة
    $tfpdfPath = __DIR__ . '/tfpdf/tfpdf.php';
    if (!file_exists($tfpdfPath)) {
        throw new Exception("تعذر العثور على مكتبة tFPDF في المسار: " . $tfpdfPath);
    }
    require_once($tfpdfPath);

    // 2. الاتصال بقاعدة البيانات
    $host = "sql213.infinityfree.com";
    $db_name = "if0_42720560_bassira";
    $username = "if0_42720560";
    $password = "Bassira2026";

    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. قراءة child_id
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
        throw new Exception("لم يتم استلام معرف الطفل بشكل صحيح.");
    }

    // 4. جلب البيانات
    $stmtInfo = $conn->prepare("
        SELECT c.full_name AS child_name, c.uid 
        FROM children c 
        WHERE c.id = :child_id
    ");
    $stmtInfo->execute([':child_id' => $child_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("لم يتم العثور على بيانات هذا الطفل.");
    }

    // 5. إعداد المجلد وتأكيد وجوده
    $dir = __DIR__ . '/reports/';
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception("تعذر إنشاء مجلد الحفظ reports/");
        }
    }

    $fileNameOnly = "report_child_" . $child_id . "_" . time() . ".pdf";
    $fullPath = $dir . $fileNameOnly;
    $relativePath = "reports/" . $fileNameOnly;

    // 6. تهيئة tFPDF مع ضبط المسارات
    define('FPDF_FONTPATH', __DIR__ . '/tfpdf/font/');
    $pdf = new tFPDF();
    $pdf->AddPage();

    $fontFile = __DIR__ . '/tfpdf/font/unifont/DejaVuSans.ttf';
    if (file_exists($fontFile)) {
        $pdf->AddFont('DejaVu', '', 'unifont/DejaVuSans.ttf', true);
        $pdf->SetFont('DejaVu', '', 14);
    } else {
        $pdf->SetFont('Arial', 'B', 14);
    }

    $pdf->Cell(0, 10, 'تقرير منصة بصيرة للتقييم', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->Cell(0, 8, 'الطفل: ' . $info['child_name'], 0, 1, 'R');
    $pdf->Cell(0, 8, 'المعرف: ' . $info['uid'], 0, 1, 'R');

    // حفظ الملف
    $pdf->Output('F', $fullPath);

    // 7. إرجاع النتيجة بنجاح
    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء التقرير بنجاح",
        "file_path" => $relativePath
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(200); // إرجاع 200 لضمان وصول رسالة الخطأ لـ Fetch في JavaScript
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>