<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "فشل الاتصال: " . $e->getMessage()]);
    exit();
}

$child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : (isset($_GET['child_id']) ? intval($_GET['child_id']) : 0);

if ($child_id <= 0) {
    echo json_encode(["status" => "error", "message" => "معرف الطفل غير صحيح"]);
    exit();
}

// 1. حساب رقم الإصدار الجديد (Versioning Logic)
$stmtVersion = $conn->prepare("SELECT version FROM diagnosis_reports WHERE child_id = :child_id ORDER BY id DESC LIMIT 1");
$stmtVersion->bindParam(":child_id", $child_id);
$stmtVersion->execute();
$lastReport = $stmtVersion->fetch(PDO::FETCH_ASSOC);

if (!$lastReport) {
    $newVersion = "v-1-0";
} else {
    // استخراج الرقم الأخير وزيادته بمقدار 1 (مثال: v-1-0 -> v-1-1)
    $parts = explode('-', $lastReport['version']);
    $subVersion = intval(end($parts)) + 1;
    $newVersion = "v-1-" . $subVersion;
}

// 2. حساب المتوسط التراكمي (Moyenne) لكل الجلسات
$stmtResults = $conn->prepare("SELECT asd_indicator, sld_indicator FROM game_results WHERE child_id = :child_id");
$stmtResults->bindParam(":child_id", $child_id);
$stmtResults->execute();
$allResults = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

$totalSessions = count($allResults);
$scoreSum = 0;

foreach ($allResults as $res) {
    // تحويل مؤشرات الخطر إلى نقاط لحساب المتوسط (HIGH=3, MODERATE=2, LOW=1)
    $val = 1;
    if ($res['asd_indicator'] === 'HIGH_RISK' || $res['sld_indicator'] === 'HIGH_RISK') {
        $val = 3;
    } elseif ($res['asd_indicator'] === 'MODERATE_RISK' || $res['sld_indicator'] === 'MODERATE_RISK') {
        $val = 2;
    }
    $scoreSum += $val;
}

$moyenne = $totalSessions > 0 ? round($scoreSum / $totalSessions, 2) : 1.0;

// 3. إنشاء مسار وحفظ بيانات التقرير في قاعدة البيانات
$stmtChild = $conn->prepare("SELECT full_name FROM children WHERE id = :child_id");
$stmtChild->bindParam(":child_id", $child_id);
$stmtChild->execute();
$child = $stmtChild->fetch(PDO::FETCH_ASSOC);

$childName = $child ? $child['full_name'] : "child";
$fileName = "report_" . $child_id . "_" . $newVersion . ".pdf";
$filePath = "reports/" . $fileName;
$fileTitle = "تقرير تشخيص - " . $childName . " - " . $newVersion;

// إنشاء مجلد reports إذا لم يكن موجوداً
if (!file_exists('reports')) {
    mkdir('reports', 0777, true);
}

$stmtSave = $conn->prepare("
    INSERT INTO diagnosis_reports (child_id, file_title, file_path, file_type, version, moyenne_score) 
    VALUES (:child_id, :file_title, :file_path, 'PDF', :version, :moyenne)
");
$stmtSave->bindParam(":child_id", $child_id);
$stmtSave->bindParam(":file_title", $file_title);
$stmtSave->bindParam(":file_path", $filePath);
$stmtSave->bindParam(":version", $newVersion);
$stmtSave->bindParam(":moyenne", $moyenne);
$stmtSave->execute();

echo json_encode([
    "status" => "success",
    "message" => "تم إنشاء إصدار التقرير بنجاح",
    "version" => $newVersion,
    "file_path" => $filePath,
    "moyenne" => $moyenne
]);
?>