<?php
// Company Service Management — إدارة طلبات العملاء (Admin only)
require_once 'db.php';

// أي طلب هنا لازم يكون مسجّل دخول وبصلاحية admin، وإلا بيرجع 401/403 ويقف
checkAuth('admin');

// الحالات المسموح بيها بس، أي قيمة تانية بترفض
const ALLOWED_STATUSES = ['new', 'in_progress', 'resolved'];

$method = $_SERVER['REQUEST_METHOD'];

function fail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            // كل الطلبات، الأحدث أولًا. status بترجع مع كل صف عشان الواجهة تلوّن/تفلتر بيها.
            $stmt = $conn->query(
                "SELECT id, name, email, subject, message, status, created_at, updated_at
                 FROM inquiries ORDER BY created_at DESC"
            );
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        case 'PUT':
            // تحديث الحالة فقط. بيانات العميل الأصلية (name/email/message) مش بتتعدل من هنا.
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            $status = $data['status'] ?? '';

            if (!$id) fail(400, "Invalid request ID.");
            if (!in_array($status, ALLOWED_STATUSES, true)) {
                fail(400, "Invalid status value.");
            }

            $check = $conn->prepare("SELECT id FROM inquiries WHERE id = ?");
            $check->execute([$id]);
            if (!$check->fetch()) fail(404, "Request not found.");

            $stmt = $conn->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            echo json_encode(["success" => true, "message" => "Status updated successfully."]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $id = filter_var($data['id'] ?? ($_GET['id'] ?? 0), FILTER_VALIDATE_INT);
            if (!$id) fail(400, "Invalid request ID.");

            $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) fail(404, "Request not found.");

            echo json_encode(["success" => true, "message" => "Request deleted successfully."]);
            break;

        default:
            fail(405, "Method not allowed.");
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail(500, "Database error. Please try again.");
}
?>
