<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// معلومات الاتصال بقاعدة البيانات
$host = "sql213.infinityfree.com";
$db_name = "if0_42720560_bassira";
$username = "if0_42720560"; // اسم المستخدم الخاص بـ InfinityFree
$password = "Bassira2026"; 

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo json_encode(["status" => "error", "message" => "Connection error: " . $exception->getMessage()]);
    exit();
}

// استقبال البيانات إلكترونياً
$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['child_id']) && !empty($data['game_id'])) {
    
    $query = "INSERT INTO game_results 
                (child_id, game_id, social_preference_score, fixation_duration_ms, saccade_velocity, asd_indicator, sld_indicator, raw_gaze_data) 
              VALUES 
                (:child_id, :game_id, :social_score, :fixation, :saccade, :asd, :sld, :gaze_data)";

    $stmt = $conn->prepare($query);

    // ربط الحقول
    $stmt->bindParam(":child_id", $data['child_id']);
    $stmt->bindParam(":game_id", $data['game_id']);
    $stmt->bindParam(":social_score", $data['social_preference_score']);
    $stmt->bindParam(":fixation", $data['fixation_duration_ms']);
    $stmt->bindParam(":saccade", $data['saccade_velocity']);
    $stmt->bindParam(":asd", $data['asd_indicator']);
    $stmt->bindParam(":sld", $data['sld_indicator']);
    
    $json_gaze = json_encode($data['raw_gaze_data']);
    $stmt->bindParam(":gaze_data", $json_gaze);

    if($stmt->execute()) {
        
        // تحديث خفيف وتلقائي على السجل الصحي للطفل في جدول health_records
        if ($data['asd_indicator'] === 'HIGH_RISK' || $data['sld_indicator'] === 'HIGH_RISK') {
            $updateHealth = "UPDATE health_records 
                            SET medical_conditions = CONCAT(IFNULL(medical_conditions, ''), ' | اشتباه بناءً على اختبار تتبع العين') 
                            WHERE child_id = :child_id";
            $stmtHealth = $conn->prepare($updateHealth);
            $stmtHealth->bindParam(":child_id", $data['child_id']);
            $stmtHealth->execute();
        }

        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "تم الحفظ بنجاح"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "فشل حفظ البيانات في قاعدة البيانات"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "بيانات غير مكتملة"]);
}
?>