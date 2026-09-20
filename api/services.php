<?php
// Public endpoint: بيرجّع الخدمات لموقع الـ Public (قراءة فقط).
// مفيش فيه أي إضافة/تعديل/حذف، وده اللي بيخليه آمن إنه يبقى بدون تسجيل دخول.
require_once 'db.php';

// نمنع الكاش عشان أي تعديل من لوحة التحكم يظهر فورًا
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit();
}

try {
    $stmt = $conn->query("SELECT id, category, title, description FROM services ORDER BY id ASC");
    echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not load services."]);
}
?>
