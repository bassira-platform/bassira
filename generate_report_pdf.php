
<?php
// إيقاف طباعة الأخطاء النصية المباشرة حتى لا تفسد استجابة الـ JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

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

    // 2. تحدد إصدار التقرير الجديد (v-1-0, v-1-1...)
    $stmtVer = $conn->prepare("SELECT COUNT(*) FROM diagnosis_reports WHERE child_id = :child_id");
    $stmtVer->execute([':child_id' => $child_id]);
    $count = $stmtVer->fetchColumn();

    $version = "v-1-" . $count;
    $fileTitle = "تقرير تقييم الطفل - إصدار " . $version;
    $fileName = "reports/report_child_" . $child_id . "_" . time() . ".pdf";

    // 3. التأكد من وجود مجلد التقارير
    if (!file_exists('reports')) {
        mkdir('reports', 0777, true);
    }

    // 4. حفظ بيانات التقرير في قاعدة البيانات
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

    // إرجاع استجابة نجاح سليمة بصيغة JSON
    echo json_encode([
        "status" => "success",
        "message" => "تم إنشاء التقرير بنجاح",
        "version" => $version,
        "moyenne_score" => $avgScore,
        "file_path" => $fileName
    ]);

} catch (Exception $e) {
    // إرجاع استجابة الخطأ بصيغة JSON لمنع SyntaxError في الفرونت إند
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>