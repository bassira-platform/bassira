<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// تضمين مكتبة FPDF لتوليد الملفات الفعلية
require_once('fpdf/fpdf.php');

$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
    if ($child_id <= 0) {
        throw new Exception("لم يتم استلام معرف الطفل بشكل صحيح.");
    }

    // 1. قراءة البيانات وحساب متوسط النقاط (moyenne_score)
    $stmtScores = $conn->prepare("
        SELECT social_preference_score, fixation_duration_ms 
        FROM game_results 
        WHERE child_id = :child_id 
        ORDER BY id DESC LIMIT 5
    ");
    $stmtScores->execute([':child_id' => $child_id]);
    $results = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

    $avgScore = 0;
    if (count($results) > 0) {
        $total = 0;
        foreach ($results as $r) {
            $total += floatval($r['social_preference_score']);
        }
        $avgScore = round(($total / count($results)) * 100, 2);
    }

    // 2. تحديد إصدار التقرير
    $stmtVer = $conn->prepare("SELECT COUNT(*) FROM diagnosis_reports WHERE child_id = :child_id");
    $stmtVer->execute([':child_id' => $child_id]);
    $count = $stmtVer->fetchColumn();

    $version = "v-1-" . $count;
    $fileTitle = "تقرير تقييم الطفل - إصدار " . $version;
    
    // التأكد من وجود المجلد
    $dir = 'reports/';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $fileName = $dir . "report_child_" . $child_id . "_" . time() . ".pdf";

    // 3. بناء وتوليد ملف الـ PDF وحفظه في المجلد
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Bassira Evaluation Report');
    $pdf->Ln(15);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 10, 'Child ID: ' . $child_id);
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Report Version: ' . $version);
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Average Score: ' . $avgScore . '%');
    $pdf->Ln(10);
    $pdf->Cell(40, 10, 'Date: ' . date('Y-m-d H:i:s'));

    // الأمر 'F' يقوم بحفظ الملف المباشر داخل المسار المحدد على المجلد
    $pdf->Output('F', $fileName);

    // 4. تسجيل بيانات التقرير في قاعدة البيانات بعد التأكد من حفظه
    $stmtInsert = $conn->prepare("
        INSERT INTO diagnosis_reports (child_id, file_title, file_path, version, moyenne_score, created_at)
        VALUES (:child_id, :file_title, :file_path, :version, :moyenne_score, NOW())
    ");
    $stmtInsert->execute([
        ':child_id' => $child_id,
        ':file_title' => $fileTitle,
        ':file_path' => $fileName,
        ':version' => $version,
        ':moyenne_score' => $avgScore
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء الملف وتخزينه بنجاح",
        "version" => $version,
        "moyenne_score" => $avgScore,
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