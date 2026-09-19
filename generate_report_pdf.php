<?php
// إظهار الأخطاء إذا لزم الأمر للتتبع
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. تحديد مسار الخطوط بوضوح
define('FPDF_FONTPATH', __DIR__ . '/tfpdf/font/unifont/');

// 2. التحقق من وجود مكتبة tFPDF لتجنب Fatal Error
$tfpdfPath = __DIR__ . '/tfpdf/tfpdf.php';
if (!file_exists($tfpdfPath)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "تعذر العثور على ملف tfpdf.php في المسار المحدّد."]);
    exit;
}

require_once($tfpdfPath);

$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. قراءة child_id بجميع الصيغ الممكنة (POST / GET / JSON Body)
    $rawInput = json_decode(file_get_contents('php://input'), true);
    $child_id = 0;

    if (isset($_POST['child_id'])) {
        $child_id = intval($_POST['child_id']);
    } elseif (isset($_GET['child_id'])) {
        $child_id = intval($_GET['child_id']);
    } elseif (isset($rawInput['child_id'])) {
        $child_id = intval($rawInput['child_id']);
    }
    
    if ($child_id <= 0) {
        throw new Exception("لم يتم استلام معرف الطفل بشكل صحيح.");
    }

    // 4. جلب بيانات الطفل وولي الأمر والملف الصحي (تم تصحيح c.uid)
    $stmtInfo = $conn->prepare("
        SELECT 
            c.full_name AS child_name, c.uid, c.birth_date, c.gender,
            hr.blood_type, hr.allergies, hr.medical_conditions,
            u.full_name AS parent_name, u.email AS parent_email, u.phone AS parent_phone, u.address AS parent_address
        FROM children c
        LEFT JOIN health_records hr ON c.id = hr.child_id
        LEFT JOIN users u ON c.parent_id = u.id
        WHERE c.id = :child_id
    ");
    $stmtInfo->execute([':child_id' => $child_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        throw new Exception("لم يتم العثور على بيانات الطفل.");
    }

    // 5. جلب نتائج الألعاب
    $stmtScores = $conn->prepare("
        SELECT social_preference_score, created_at 
        FROM game_results 
        WHERE child_id = :child_id 
        ORDER BY id ASC
    ");
    $stmtScores->execute([':child_id' => $child_id]);
    $results = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

    $totalGames = count($results);
    $finalScore = 0;
    $evaluationType = "";

    if ($totalGames === 0) {
        throw new Exception("لا توجد نتائج ألعاب مسجلة لهذا الطفل بعد.");
    } elseif ($totalGames === 1) {
        $finalScore = round(floatval($results[0]['social_preference_score']) * 100, 2);
        $evaluationType = "تقييم تشخيصي مبدئي (اللعبة الأولى)";
    } else {
        $total = 0;
        foreach ($results as $r) {
            $total += floatval($r['social_preference_score']);
        }
        $finalScore = round(($total / $totalGames) * 100, 2);
        $evaluationType = "تقييم تراكمي (متوسط " . $totalGames . " جلسات)";
    }

    if ($finalScore >= 70) {
        $statusText = "طبيعي (خطر منخفض)";
        $conditionText = "استجابة بصرية وتثبيت طبيعي عبر الاختبارات";
        $recommendation = "متابعة الأداء الدوري عبر ألعاب المنصة للحفاظ على التطور الطبيعي.";
    } else {
        $statusText = "اشتباه (مؤشر مرتفع)";
        $conditionText = "اشتباه بناءً على اختبار تتبع العين والتثبيت البصري";
        $recommendation = "يوصى بعرض الطفل على أخصائي معتمد كأداة مساعدة في التقييم الكلينيكي.";
    }

    // 6. تحديد الإصدار ومجلد الحفظ
    $stmtVer = $conn->prepare("SELECT COUNT(*) FROM diagnosis_reports WHERE child_id = :child_id");
    $stmtVer->execute([':child_id' => $child_id]);
    $count = $stmtVer->fetchColumn();

    $version = "v-1-" . $count;
    $fileTitle = "تقرير تقييم الطفل - " . $version;
    
    $dir = 'reports/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $fileName = $dir . "report_child_" . $child_id . "_" . time() . ".pdf";

    // 7. إنشاء PDF وإضافة الخط الداعم للعربية
    $pdf = new tFPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    $fontFile = __DIR__ . '/tfpdf/font/unifont/DejaVuSans.ttf';
    if (file_exists($fontFile)) {
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->SetFont('DejaVu', 'B', 16);
    } else {
        $pdf->SetFont('Arial', 'B', 16);
    }

    // الهيدر
    $pdf->Cell(0, 10, 'منصة بصيرة - تقرير التقييم البصري والنمائي', 0, 1, 'C');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(0, 6, 'تاريخ الإصدار: ' . date('Y-m-d') . ' | الإصدار: ' . $version, 0, 1, 'C');
    $pdf->Ln(5);

    // 1. معلومات الطفل
    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '1. معلومات الطفل', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(95, 8, 'الاسم: ' . ($info['child_name'] ?? '-'), 1, 0, 'R');
    $pdf->Cell(95, 8, 'المعرف UID: ' . ($info['uid'] ?? '-'), 1, 1, 'R');
    $pdf->Cell(95, 8, 'تاريخ الميلاد: ' . ($info['birth_date'] ?? '-'), 1, 0, 'R');
    $pdf->Cell(95, 8, 'فصيلة الدم: ' . ($info['blood_type'] ?? 'غير محددة'), 1, 1, 'R');
    $pdf->Ln(5);

    // 2. معلومات ولي الأمر
    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '2. معلومات ولي الأمر', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(95, 8, 'ولي الأمر: ' . ($info['parent_name'] ?? '-'), 1, 0, 'R');
    $pdf->Cell(95, 8, 'الهاتف: ' . ($info['parent_phone'] ?? '-'), 1, 1, 'R');
    $pdf->Cell(95, 8, 'البريد الإلكتروني: ' . ($info['parent_email'] ?? '-'), 1, 0, 'R');
    $pdf->Cell(95, 8, 'العنوان: ' . ($info['parent_address'] ?? '-'), 1, 1, 'R');
    $pdf->Ln(5);

    // 3. نتائج التقييم
    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '3. نتائج التقييم (' . $evaluationType . ')', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(95, 8, 'النتيجة: ' . $finalScore . '%', 1, 0, 'R');
    $pdf->Cell(95, 8, 'الحالة: ' . $statusText, 1, 1, 'R');
    $pdf->Ln(5);

    // 4. الملاحظات والتوصيات
    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '4. الملاحظات الطبية والتوصيات', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->MultiCell(0, 7, 'الحساسية: ' . ($info['allergies'] ?: 'لا يوجد'), 1, 'R');
    $pdf->MultiCell(0, 7, 'الحالة المسجلة: ' . $conditionText, 1, 'R');
    $pdf->MultiCell(0, 7, 'التوصية: ' . $recommendation, 1, 'R');
    $pdf->Ln(8);

    // إخلاء المسؤولية
    $pdf->SetFont('DejaVu', '', 8);
    $pdf->MultiCell(0, 5, 'ملاحظة: تم إنشاء هذا التقرير تلقائياً بناءً على تحليل حركة العين والتثبيت البصري أثناء ألعاب المنصة. يرجى استخدام هذه النتائج كأداة مساعدة للتقييم الإكلينيكي لدى المختصين.', 0, 'R');

    // حفظ الملف
    $pdf->Output('F', $fileName);

    // 8. حفظ السجل في قاعدة البيانات
    $stmtInsert = $conn->prepare("
        INSERT INTO diagnosis_reports (child_id, file_title, file_path, version, moyenne_score, created_at)
        VALUES (:child_id, :file_title, :file_path, :version, :moyenne_score, NOW())
    ");
    $stmtInsert->execute([
        ':child_id' => $child_id,
        ':file_title' => $fileTitle,
        ':file_path' => $fileName,
        ':version' => $version,
        ':moyenne_score' => $finalScore
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء التقرير بنجاح",
        "version" => $version,
        "moyenne_score" => $finalScore,
        "file_path" => $fileName
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>