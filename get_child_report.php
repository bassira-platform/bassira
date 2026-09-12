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

$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

if ($child_id <= 0) {
    echo json_encode(["status" => "error", "message" => "معرف الطفل غير صحيح"]);
    exit();
}

// 1. جلب بيانات الطفل وولي الأمر
$stmtChild = $conn->prepare("
    SELECT c.id AS child_id, c.uid, c.full_name AS child_name, c.birth_date, c.gender,
           u.full_name AS parent_name, u.phone AS parent_phone, u.email AS parent_email, u.address AS parent_address
    FROM children c
    JOIN users u ON c.parent_id = u.id
    WHERE c.id = :child_id
");
$stmtChild->bindParam(":child_id", $child_id);
$stmtChild->execute();
$childInfo = $stmtChild->fetch(PDO::FETCH_ASSOC);

if (!$childInfo) {
    echo json_encode(["status" => "error", "message" => "لم يتم العثور على بيانات الطفل"]);
    exit();
}

// 2. جلب السجل الطبي
$stmtHealth = $conn->prepare("SELECT blood_type, allergies, medical_conditions FROM health_records WHERE child_id = :child_id");
$stmtHealth->bindParam(":child_id", $child_id);
$stmtHealth->execute();
$healthInfo = $stmtHealth->fetch(PDO::FETCH_ASSOC) ?: [
    "blood_type" => "غير محدد", "allergies" => "لا يوجد", "medical_conditions" => "لا يوجد"
];

// 3. جلب نتائج الألعاب والتقييم الحركي البصري
$stmtResults = $conn->prepare("
    SELECT asd_indicator, sld_indicator, social_preference_score, fixation_duration_ms, saccade_velocity, created_at
    FROM game_results 
    WHERE child_id = :child_id
    ORDER BY created_at DESC
");
$stmtResults->bindParam(":child_id", $child_id);
$stmtResults->execute();
$allResults = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

// حساب حالات الخطر العامة
$asd_status = "LOW_RISK";
$sld_status = "LOW_RISK";

foreach ($allResults as $res) {
    if ($res['asd_indicator'] === 'HIGH_RISK') $asd_status = 'HIGH_RISK';
    elseif ($res['asd_indicator'] === 'MODERATE_RISK' && $asd_status !== 'HIGH_RISK') $asd_status = 'MODERATE_RISK';

    if ($res['sld_indicator'] === 'HIGH_RISK') $sld_status = 'HIGH_RISK';
    elseif ($res['sld_indicator'] === 'MODERATE_RISK' && $sld_status !== 'HIGH_RISK') $sld_status = 'MODERATE_RISK';
}

// 4. تسجبل التقرير في جدول diagnosis_reports
$file_title = "تقرير تشخيص - " . $childInfo['child_name'] . " - " . date("Y-m-d");
$file_path = "reports/report_child_" . $child_id . "_" . time() . ".pdf";

$stmtSaveReport = $conn->prepare("
    INSERT INTO diagnosis_reports (child_id, file_title, file_path, file_type) 
    VALUES (:child_id, :file_title, :file_path, 'PDF')
");
$stmtSaveReport->bindParam(":child_id", $child_id);
$stmtSaveReport->bindParam(":file_title", $file_title);
$stmtSaveReport->bindParam(":file_path", $file_path);
$stmtSaveReport->execute();

// إرجاع البيانات البرمجية الكاملة
echo json_encode([
    "status" => "success",
    "report_id" => $conn->lastInsertId(),
    "child" => $childInfo,
    "health" => $healthInfo,
    "assessment" => [
        "asd_status" => $asd_status,
        "sld_status" => $sld_status,
        "total_sessions" => count($allResults),
        "game_logs" => $allResults
    ]
]);
?>