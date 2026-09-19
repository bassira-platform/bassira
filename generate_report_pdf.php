<?php
// إيقاف إخراج الأخطاء النصية لمنع إفساد صيغة JSON
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

try {
    // 1. استدعاء مكتبة tFPDF بالمسار المطلق
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

    // 3. استلام child_id بكافة الطرق الممكنة (POST / GET / JSON Body)
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

    // 4. جلب بيانات الطفل وولي الأمر
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
        throw new Exception("لم يتم العثور على بيانات هذا الطفل في قاعدة البيانات.");
    }

    // 5. جلب نتائج الألعاب وتحسّب الدرجة التراكمية
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
        $finalScore = 50;
        $evaluationType = "تقييم أولي (لا توجد ألعاب متراكمة)";
    } elseif ($totalGames === 1) {
        $finalScore = round(floatval($results[0]['social_preference_score']) * 100, 2);
        $evaluationType = "تقييم تشخيصي مبدئي (الجلسة الأولى)";
    } else {
        $total = 0;
        foreach ($results as $r) {
            $total += floatval($r['social_preference_score']);
        }
        $finalScore = round(($total / $totalGames) * 100, 2);
        $evaluationType = "تقييم تراكمي (متوسط " . $totalGames . " جلسات)";
    }

    $statusText = ($finalScore >= 70) ? "طبيعي (خطر منخفض)" : "اشتباه (مؤشر مرتفع)";

    // 6. تجهيز المجلد والمسارات للـ PDF
    $dir = __DIR__ . '/reports/';
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception("تعذر إنشاء مجلد الحفظ reports/");
        }
    }

    // تحديد رقم الإصدار بناءً على التقارير السابقة
    $stmtVer = $conn->prepare("SELECT COUNT(*) FROM diagnosis_reports WHERE child_id = :child_id");
    $stmtVer->execute([':child_id' => $child_id]);
    $count = $stmtVer->fetchColumn();

    $version = "v-1-" . $count;
    $fileTitle = "تقرير تقييم الطفل - " . $version;
    $fileNameOnly = "report_child_" . $child_id . "_" . time() . ".pdf";
    $fullPath = $dir . $fileNameOnly;
    $relativePath = "reports/" . $fileNameOnly;

    // 7. ضبط مسار الخطوط وإنشاء ملف PDF
    define('FPDF_FONTPATH', __DIR__ . '/tfpdf/font/unifont/');
    $pdf = new tFPDF();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 15);

    $fontFile = __DIR__ . '/tfpdf/font/unifont/DejaVuSans.ttf';
    if (file_exists($fontFile)) {
        // نمرر اسم الملف فقط بدون اسم المجلد لتفادي تكرار unifont/unifont
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        if (file_exists(__DIR__ . '/tfpdf/font/unifont/DejaVuSans-Bold.ttf')) {
            $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        }
        $pdf->SetFont('DejaVu', 'B', 16);
    } else {
        $pdf->SetFont('Arial', 'B', 16);
    }

    // كتابة محتوى الـ PDF
    $pdf->Cell(0, 10, 'منصة بصيرة - تقرير التقييم البصري والنمائي', 0, 1, 'C');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(0, 6, 'تاريخ الإصدار: ' . date('Y-m-d') . ' | الإصدار: ' . $version, 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '1. معلومات الطفل', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(95, 8, 'الاسم: ' . ($info['child_name'] ?? '-'), 1, 0, 'R');
    $pdf->Cell(95, 8, 'المعرف UID: ' . ($info['uid'] ?? '-'), 1, 1, 'R');
    $pdf->Ln(5);

    $pdf->SetFont('DejaVu', 'B', 12);
    $pdf->Cell(0, 8, '2. نتائج التقييم (' . $evaluationType . ')', 0, 1, 'R');
    $pdf->SetFont('DejaVu', '', 10);
    $pdf->Cell(95, 8, 'النتيجة: ' . $finalScore . '%', 1, 0, 'R');
    $pdf->Cell(95, 8, 'الحالة: ' . $statusText, 1, 1, 'R');
    $pdf->Ln(5);

    // حفظ الملف على السيرفر
    $pdf->Output('F', $fullPath);

    // 8. تسجيل التقرير في قاعدة البيانات
    $stmtInsert = $conn->prepare("
        INSERT INTO diagnosis_reports (child_id, file_title, file_path, version, moyenne_score, created_at)
        VALUES (:child_id, :file_title, :file_path, :version, :moyenne_score, NOW())
    ");
    $stmtInsert->execute([
        ':child_id' => $child_id,
        ':file_title' => $fileTitle,
        ':file_path' => $relativePath,
        ':version' => $version,
        ':moyenne_score' => $finalScore
    ]);

    // تنظيف البافر وإرجاع استجابة JSON ناجحة
    ob_end_clean();
    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء التقرير بنجاح",
        "version" => $version,
        "moyenne_score" => $finalScore,
        "file_path" => $relativePath
    ]);

} catch (Exception $e) {
    ob_end_clean();
    // إرجاع كود 200 مع تفاصيل الخطأ في JSON لكي لا يتوقف الفيتش في المتصفح
    http_response_code(200);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>