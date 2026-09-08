<?php
// get_specialists.php
ob_start();
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'db.php';

ob_clean();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'جلسة العمل انتهت، يرجى إعادة تسجيل الدخول']);
    exit();
}

try {
    // 1. جلب العناوين والولايات المتاحة ديناميكياً لربطها بقائمة الفلترة
    $stmtAddresses = $pdo->query("SELECT DISTINCT address FROM users WHERE user_type = 'SPECIALIST' AND address IS NOT NULL AND address != ''");
    $addresses = $stmtAddresses->fetchAll(PDO::FETCH_COLUMN);

    // 2. جلب التخصصات المتاحة
    $stmtSpecialties = $pdo->query("SELECT DISTINCT specialist_type FROM users WHERE user_type = 'SPECIALIST' AND specialist_type IS NOT NULL AND specialist_type != ''");
    $rawSpecialties = $stmtSpecialties->fetchAll(PDO::FETCH_COLUMN);

    // قاموس ترجمة رموز ENUM للعربية
    $specialtyMap = [
        'PSYCHOLOGIST' => '🧠 أخصائي نفساني',
        'ORTHOPHONIST' => '🗣️ أخصائي أرطوفوني (تخاطب)',
        'OPHTHALMOLOG' => '👁️ أخصائي عيون'
    ];

    $specialtiesFormatted = [];
    foreach ($rawSpecialties as $spec) {
        $specialtiesFormatted[] = [
            'value' => $spec,
            'label' => $specialtyMap[$spec] ?? $spec
        ];
    }

    // 3. بناء الاستعلام الديناميكي بناءً على الفلاتر المرسلة
    $specialtyFilter = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
    $addressFilter   = isset($_GET['address']) ? trim($_GET['address']) : '';
    $sortOrder       = isset($_GET['sort']) && strtolower($_GET['sort']) === 'desc' ? 'DESC' : 'ASC';

    $sql = "SELECT 
                id, 
                user_code, 
                full_name, 
                email, 
                COALESCE(phone, '') AS phone, 
                COALESCE(address, '') AS address, 
                COALESCE(specialist_type, '') AS specialist_type, 
                COALESCE(avatar, '') AS avatar 
            FROM users 
            WHERE user_type = 'SPECIALIST'";

    $params = [];

    if (!empty($specialtyFilter)) {
        $sql .= " AND specialist_type = ?";
        $params[] = $specialtyFilter;
    }

    if (!empty($addressFilter)) {
        $sql .= " AND address = ?";
        $params[] = $addressFilter;
    }

    $sql .= " ORDER BY full_name " . $sortOrder;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $specialists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تجهيز مسارات الصور والترجمة للأخصائيين
    $uploadDir = 'uploads/';
    foreach ($specialists as &$spec) {
        $spec['specialist_type_label'] = $specialtyMap[$spec['specialist_type']] ?? $spec['specialist_type'];
        if (!empty($spec['avatar']) && file_exists($uploadDir . $spec['avatar'])) {
            $spec['avatar_url'] = $uploadDir . $spec['avatar'];
        } else {
            $spec['avatar_url'] = 'assets/default-avatar.png'; // صورة افتراضية
        }
    }

    echo json_encode([
        'status'      => 'success',
        'specialists' => $specialists,
        'filters'     => [
            'addresses'   => $addresses,
            'specialties' => $specialtiesFormatted
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
exit();
?>