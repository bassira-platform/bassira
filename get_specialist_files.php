<?php
// get_specialist_files.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_code'])) {
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح بالوصول']);
    exit();
}

try {
    $child_id = $_GET['child_id'] ?? null;

    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE user_code = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_code']]);
    $specRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$specRow) {
        echo json_encode(['status' => 'error', 'message' => 'حساب الأخصائي غير موجود']);
        exit();
    }

    // جلب المستندات الطبية المحفوظة برقم الطفل المتابَع لدى هذا الأخصائي
    $sql = "SELECT mf.id, mf.child_id, mf.file_title, mf.file_path, mf.file_type, mf.created_at,
                   c.full_name AS child_name
            FROM medical_files mf
            INNER JOIN children c ON mf.child_id = c.id
            INNER JOIN appointments a ON c.id = a.child_id
            WHERE a.specialist_id = ?";

    $params = [$specRow['id']];

    if ($child_id && $child_id !== 'all') {
        $sql .= " AND mf.child_id = ?";
        $params[] = $child_id;
    }

    $sql .= " GROUP BY mf.id ORDER BY mf.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $files]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()]);
}
?>