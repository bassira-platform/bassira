<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. استدعاء مكتبة mPDF
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/mpdf/vendor/autoload.php')) {
    require_once __DIR__ . '/mpdf/vendor/autoload.php';
} else {
    echo json_encode(["status" => "error", "message" => "لم يتم العثور على مجلد vendor/autoload.php"]);
    exit();
}

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

    // 2. جلب بيانات الطفل وولي الأمر
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

    // 3. جلب أحدث نتيجة مسجلة لكل لعبة من الألعاب الثلاث (game_id = 1, 2, 3)
    $stmtScores = $conn->prepare("
        SELECT gr.game_id, gr.social_preference_score, gr.created_at
        FROM game_results gr
        INNER JOIN (
            SELECT game_id, MAX(id) AS max_id
            FROM game_results
            WHERE child_id = :child_id
            GROUP BY game_id
        ) latest ON gr.id = latest.max_id
        ORDER BY gr.game_id ASC
    ");
    $stmtScores->execute([':child_id' => $child_id]);
    $results = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

    $totalGames = count($results);
    $finalScore = 0;
    $evaluationType = "";

    // خريطة مسميات الألعاب
    $gameNames = [
        1 => "اللعبة الأولى (التتبع البصري)",
        3 => "اللعبة الثانية (التثبيت والتفاعل)",
        2 => "اللعبة الثالثة (مغامرة الحروف)"
    ];

    if ($totalGames === 0) {
        throw new Exception("لا توجد نتائج ألعاب مسجلة لهذا الطفل بعد.");
    } elseif ($totalGames === 1) {
        $singleGameId = intval($results[0]['game_id']);
        $gameLabel = isset($gameNames[$singleGameId]) ? $gameNames[$singleGameId] : "اللعبة " . $singleGameId;
        
        $finalScore = round(floatval($results[0]['social_preference_score']) * 100, 2);
        $evaluationType = "تقييم تشخيصي مبدئي (" . $gameLabel . ")";
    } else {
        $total = 0;
        foreach ($results as $r) {
            $total += floatval($r['social_preference_score']);
        }
        $finalScore = round(($total / $totalGames) * 100, 2);
        $evaluationType = "تقييم تراكمي (متوسط " . $totalGames . " ألعاب مختلفة)";
    }

    // تحديد حالة التقييم
    if ($finalScore >= 70) {
        $statusText = "طبيعي (خطر منخفض)";
        $conditionText = "استجابة بصرية ونمائية طبيعية عبر اختبارات المنصة";
        $recommendation = "متابعة الأداء الدوري عبر ألعاب المنصة للحفاظ على التطور الطبيعي.";
    } else {
        $statusText = "اشتباه (مؤشر مرتفع)";
        $conditionText = "اشتباه بناءً على نتائج التتبع والتثبيت والتفاعل في الألعاب";
        $recommendation = "يوصى بعرض الطفل على أخصائي معتمد كأداة مساعدة في التقييم الكلينيكي.";
    }

    // 4. إعداد الملف وتحديد النسخة
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

    // 5. تهيئة mPDF مع خط Tajawal
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/fonts',
        ]),
        'fontdata' => $fontData + [
            'tajawal' => [
                'R' => 'Tajawal-Regular.ttf',
                'useOTL' => 0x00,
            ]
        ],
        'default_font' => 'tajawal',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
    ]);

    // محتوى التقرير
    $html = '
    <div dir="rtl" style="font-family: tajawal; text-align: right; direction: rtl; unicode-bidi: embed;">
        <h2 style="text-align: center; margin-bottom: 5px;">منصة بصيرة - تقرير التقييم البصري والنمائي</h2>
        <p style="text-align: center; font-size: 12px; color: #666; margin-top: 0;">
            تاريخ الإصدار: ' . date('Y-m-d') . ' | الإصدار: ' . $version . '
        </p>
        <hr style="border: 0.5px solid #ccc; margin-bottom: 15px;">

        <h3 style="color: #2c3e50; font-size: 14px; margin-bottom: 5px;">1. معلومات الطفل</h3>
        <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; border-color: #ddd;">
            <tr>
                <td width="50%"><strong>الاسم:</strong> ' . htmlspecialchars($info['child_name'] ?? '-') . '</td>
                <td width="50%"><strong>المعرف UID:</strong> ' . htmlspecialchars($info['uid'] ?? '-') . '</td>
            </tr>
            <tr>
                <td><strong>تاريخ الميلاد:</strong> ' . htmlspecialchars($info['birth_date'] ?? '-') . '</td>
                <td><strong>فصيلة الدم:</strong> ' . htmlspecialchars($info['blood_type'] ?? 'غير محددة') . '</td>
            </tr>
        </table>

        <h3 style="color: #2c3e50; font-size: 14px; margin-top: 15px; margin-bottom: 5px;">2. معلومات ولي الأمر</h3>
        <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; border-color: #ddd;">
            <tr>
                <td width="50%"><strong>ولي الأمر:</strong> ' . htmlspecialchars($info['parent_name'] ?? '-') . '</td>
                <td width="50%"><strong>الهاتف:</strong> ' . htmlspecialchars($info['parent_phone'] ?? '-') . '</td>
            </tr>
            <tr>
                <td><strong>البريد الإلكتروني:</strong> ' . htmlspecialchars($info['parent_email'] ?? '-') . '</td>
                <td><strong>العنوان:</strong> ' . htmlspecialchars($info['parent_address'] ?? '-') . '</td>
            </tr>
        </table>

        <h3 style="color: #2c3e50; font-size: 14px; margin-top: 15px; margin-bottom: 5px;">3. نتائج التقييم (' . htmlspecialchars($evaluationType) . ')</h3>
        <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 12px; border-color: #ddd;">
            <tr>
                <td width="50%"><strong>النتيجة التراكمية:</strong> ' . $finalScore . '%</td>
                <td width="50%"><strong>الحالة التشخيصية:</strong> ' . htmlspecialchars($statusText) . '</td>
            </tr>
        </table>

        <h3 style="color: #2c3e50; font-size: 14px; margin-top: 15px; margin-bottom: 5px;">4. الملاحظات الطبية والتوصيات</h3>
        <div style="border: 1px solid #ddd; padding: 8px; font-size: 12px; line-height: 1.6;">
            <p style="margin: 3px 0;"><strong>الحساسية:</strong> ' . htmlspecialchars($info['allergies'] ?: 'لا يوجد') . '</p>
            <p style="margin: 3px 0;"><strong>الحالة المسجلة:</strong> ' . htmlspecialchars($conditionText) . '</p>
            <p style="margin: 3px 0;"><strong>التوصية:</strong> ' . htmlspecialchars($recommendation) . '</p>
        </div>

        <p style="font-size: 9px; color: #777; margin-top: 25px; line-height: 1.4;">
            ملاحظة: تم إنشاء هذا التقرير تلقائياً بناءً على تحليل الأداء البصري والنمائي أثناء ألعاب المنصة. يرجى استخدام هذه النتائج كأداة مساعدة للتقييم الإكلينيكي لدى المختصين.
        </p>
    </div>
    ';

    $mpdf->WriteHTML($html);
    $mpdf->Output($fileName, 'F');

    // 6. حفظ التقرير في قاعدة البيانات
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