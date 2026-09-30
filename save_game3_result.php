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

    $raw_input = file_get_contents("php://input");
    $input = json_decode($raw_input, true);

    $child_id = isset($input['child_id']) ? intval($input['child_id']) : 0;
    $score = isset($input['score']) ? floatval($input['score']) : 0.0;
    $details = isset($input['details']) ? json_encode($input['details'], JSON_UNESCAPED_UNICODE) : null;

    if ($child_id <= 0) {
        echo json_encode(["status" => "error", "message" => "معرف الطفل غير صالح"]);
        exit();
    }

    $game_id = 2; // معرف اللعبة الثالثة في جدول games_and_tests

    // 1. الحفظ في الجدول الرئيسي لتضمينه تلقائياً في حساب التقرير الـ PDF
    $stmt1 = $conn->prepare("
        INSERT INTO game_results (child_id, game_id, social_preference_score, created_at)
        VALUES (:child_id, :game_id, :score, NOW())
    ");
    $stmt1->execute([
        ':child_id' => $child_id,
        ':game_id'  => $game_id,
        ':score'    => $score
    ]);

    // 2. الحفظ في جدول اللعبة الثالثة المخصص للتفاصيل
    $stmt2 = $conn->prepare("
        INSERT INTO game3_results (child_id, game_id, score, details_json, created_at)
        VALUES (:child_id, :game_id, :score, :details_json, NOW())
    ");
    $stmt2->execute([
        ':child_id'     => $child_id,
        ':game_id'      => $game_id,
        ':score'        => $score,
        ':details_json' => $details
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "تم حفظ نتيجة اللعبة الثالثة وتضمينها في التقرير بنجاح",
        "child_id" => $child_id,
        "score" => $score
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>