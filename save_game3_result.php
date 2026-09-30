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

    $input = json_decode(file_get_contents("php://input"), true);

    $child_id = isset($input['child_id']) ? intval($input['child_id']) : 0;
    $score = isset($input['score']) ? floatval($input['score']) : 0.0;

    if ($child_id <= 0) {
        echo json_encode(["status" => "error", "message" => "معرف الطفل غير صالح"]);
        exit();
    }

    // تخزين النتيجة مباشرة في الجدول التراكمي
    $stmt = $conn->prepare("
        INSERT INTO game_results (child_id, social_preference_score, created_at)
        VALUES (:child_id, :score, NOW())
    ");
    $stmt->execute([
        ':child_id' => $child_id,
        ':score' => $score
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "تم حفظ نتيجة اللعبة الثالثة بنجاح",
        "child_id" => $child_id,
        "score" => $score
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>