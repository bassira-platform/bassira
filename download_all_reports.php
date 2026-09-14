<?php
$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

if ($child_id <= 0) {
    die("معرف الطفل غير صحيح");
}

$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560";
$password = "Bassira2026";

$conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);

$stmt = $conn->prepare("SELECT file_path, version FROM diagnosis_reports WHERE child_id = :child_id");
$stmt->bindParam(":child_id", $child_id);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($reports)) {
    die("لا توجد تقارير مخزنة لهذا الطفل");
}

$zip = new ZipArchive();
$zipName = "archive_reports_child_" . $child_id . ".zip";

if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    foreach ($reports as $rep) {
        if (file_exists($rep['file_path'])) {
            $zip->addFile($rep['file_path'], basename($rep['file_path']));
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipName));
    readfile($zipName);
    unlink($zipName); // حذف ملف ZIP المؤقت من السيرفر بعد التحميل
    exit();
} else {
    echo "فشل إنشاء ملف ZIP";
}
?>