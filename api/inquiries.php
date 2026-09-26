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

// بيهرّب % و _ عشان لو العميل كتبهم في البحث ميتعاملوش كـ wildcards في LIKE
function escape_like(string $value): string {
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

// تاريخ بصيغة YYYY-MM-DD بس، أي حاجة تانية بترجع null وبتتجاهل
function valid_date(?string $value): ?string {
    if (!$value) return null;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

try {
    switch ($method) {
        case 'GET':
            // Search & Filtering: q (نص حر) + status + مدى تاريخ، وكلهم اختياريين وممكن يتجمعوا مع بعض.
            // البحث والفلترة بيحصلوا في الـ SQL نفسه، مش على بيانات محمّلة مسبقًا في الـ Frontend.
            $q = trim((string)($_GET['q'] ?? ''));
            $status = $_GET['status'] ?? '';
            $from = valid_date($_GET['from'] ?? null);
            $to = valid_date($_GET['to'] ?? null);

            if (mb_strlen($q) > 200) fail(400, "Search term is too long.");

            $where = [];
            $params = [];

            if ($q !== '') {
                $like = '%' . escape_like($q) . '%';
                $where[] = "(name LIKE ? ESCAPE '\\\\' OR email LIKE ? ESCAPE '\\\\'
                             OR subject LIKE ? ESCAPE '\\\\' OR message LIKE ? ESCAPE '\\\\')";
                array_push($params, $like, $like, $like, $like);
            }
            if ($status !== '' && in_array($status, ALLOWED_STATUSES, true)) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            if ($from) {
                $where[] = "created_at >= ?";
                $params[] = $from . " 00:00:00";
            }
            if ($to) {
                $where[] = "created_at <= ?";
                $params[] = $to . " 23:59:59";
            }

            $sql = "SELECT id, name, email, subject, message, status, created_at, updated_at FROM inquiries";
            if ($where) $sql .= " WHERE " . implode(" AND ", $where);
            $sql .= " ORDER BY created_at DESC";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
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
