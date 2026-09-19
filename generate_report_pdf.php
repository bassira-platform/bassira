<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// استدعاء مكتبة FPDF
require_once('fpdf/fpdf.php');

$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : (isset($_GET['child_id']) ? intval($_GET['child_id']) : 0);
    
    if ($child_id <= 0) {
        throw new Exception("لم يتم استلام معرف الطفل بشكل صحيح.");
    }

    // 1. جلب بيانات الطفل وولي الأمر والملف الصحي
    $stmtInfo = $conn->prepare("
        SELECT 
            c.full_name AS child_name, c.uid_code, c.birth_date, c.gender,
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

    // 2. جلب نتائج الألعاب لتطبيق المنطق (أول مرة vs المتوسط التراكمي)
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
        // لعب لأول مرة -> اعتماد النتيجة الأولى مباشرة
        $finalScore = round(floatval($results[0]['social_preference_score']) * 100, 2);
        $evaluationType = "تقييم تشخيصي مبدئي (اللعبة الأولى)";
    } else {
        // لعب أكثر من مرة -> حساب المتوسط التراكمي
        $total = 0;
        foreach ($results as $r) {
            $total += floatval($r['social_preference_score']);
        }
        $finalScore = round(($total / $totalGames) * 100, 2);
        $evaluationType = "تقييم تراكمي (متوسط " . $totalGames . " جلسات)";
    }

    // تحديد حالة الخطر والتوصية بناءً على الدرجة
    if ($finalScore >= 70) {
        $statusText = "طبيعي (خطر منخفض)";
        $conditionText = "استجابة بصرية وتثبيت طبيعي عبر الاختبارات";
        $recommendation = "متابعة الأداء الدوري عبر ألعاب المنصة للحفاظ على التطور الطبيعي.";
    } else {
        $statusText = "اشتباه (مؤشر مرتفع)";
        $conditionText = "اشتباه بناءً على اختبار تتبع العين والتثبيت البصري";
        $recommendation = "يوصى بعرض الطفل على أخصائي معتمد كأداة مساعدة في التقييم الكلينيكي.";
    }

    // 3. تحديد إصدار التقرير
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

    // 4. إنشاء ملف PDF بأسلوب منظم
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    // الهيدر
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'Bassira - Visual & Developmental Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Date: ' . date('Y-m-d') . ' | Version: ' . $version, 0, 1, 'C');
    $pdf->Ln(5);

    // بيانات الطفل
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '1. Child Information', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(95, 6, 'Name: ' . ($info['child_name'] ?? '-'), 1);
    $pdf->Cell(95, 6, 'UID: ' . ($info['uid_code'] ?? '-'), 1, 1);
    $pdf->Cell(95, 6, 'Birth Date: ' . ($info['birth_date'] ?? '-'), 1);
    $pdf->Cell(95, 6, 'Blood Type: ' . ($info['blood_type'] ?? 'Unspecified'), 1, 1);
    $pdf->Ln(5);

    // بيانات ولي الأمر
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '2. Parent Information', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(95, 6, 'Parent Name: ' . ($info['parent_name'] ?? '-'), 1);
    $pdf->Cell(95, 6, 'Phone: ' . ($info['parent_phone'] ?? '-'), 1, 1);
    $pdf->Cell(95, 6, 'Email: ' . ($info['parent_email'] ?? '-'), 1);
    $pdf->Cell(95, 6, 'Address: ' . ($info['parent_address'] ?? '-'), 1, 1);
    $pdf->Ln(5);

    // نتيجة التقييم
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '3. Evaluation Results (' . $evaluationType . ')', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(95, 6, 'Score: ' . $finalScore . '%', 1);
    $pdf->Cell(95, 6, 'Status: ' . $statusText, 1, 1);
    $pdf->Ln(5);

    // السجل الطبي والتوصيات
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '4. Medical Notes & Recommendation', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, 'Allergies: ' . ($info['allergies'] ?: 'None'), 1);
    $pdf->MultiCell(0, 6, 'Recorded Conditions: ' . $conditionText, 1);
    $pdf->MultiCell(0, 6, 'Recommendation: ' . $recommendation, 1);
    $pdf->Ln(8);

    // إخلاء المسؤولية
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->MultiCell(0, 4, 'Note: This report was generated automatically based on eye-tracking and visual fixation analysis during platform games. Please use these results as a supporting tool for clinical evaluation.');

    // حفظ الملف
    $pdf->Output('F', $fileName);

    // 5. حفظ السجل في قاعدة البيانات
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