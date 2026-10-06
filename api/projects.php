<?php
// api/projects.php — Client Project Management API
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized. Please log in."]);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'user';
$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function fail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit();
}

const ALLOWED_STATUSES = ['not_started', 'in_progress', 'completed', 'on_hold'];

try {
    switch ($method) {
        case 'GET':
            // لو أدمن يعرض كل المشاريع، لو عضو فريق يعرض المشاريع المرتبط بها فقط
            if ($userRole === 'admin') {
                $stmt = $conn->prepare("SELECT * FROM projects ORDER BY created_at DESC");
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("
                    SELECT p.* FROM projects p
                    JOIN project_assignments pa ON p.id = pa.project_id
                    WHERE pa.user_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$userId]);
            }
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // إحصائيات بسيطة للـ Dashboard
            $total = count($projects);
            $in_progress = count(array_filter($projects, fn($p) => $p['status'] === 'in_progress'));
            $completed = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));

            echo json_encode([
                "success" => true,
                "stats" => [
                    "total" => $total,
                    "in_progress" => $in_progress,
                    "completed" => $completed
                ],
                "data" => $projects
            ]);
            break;

        case 'POST':
            // إنشاء مشروع جديد (مقتصر على الـ Admin)
            if ($userRole !== 'admin') {
                fail(403, "Forbidden. Only admins can create projects.");
            }

            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $client_name = trim($data['client_name'] ?? '');
            $status = $data['status'] ?? 'not_started';
            $assigned_members = $data['assigned_members'] ?? []; // مصفوفة IDs للأعضاء

            if (!$name || !$client_name) {
                fail(400, "Project name and client name are required.");
            }

            if (!in_array($status, ALLOWED_STATUSES, true)) {
                $status = 'not_started';
            }

            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO projects (name, description, client_name, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $client_name, $status]);
            $projectId = $conn->lastInsertId();

            // ربط المشروع بأعضاء الفريق
            if (!empty($assigned_members) && is_array($assigned_members)) {
                $assignStmt = $conn->prepare("INSERT INTO project_assignments (project_id, user_id) VALUES (?, ?)");
                foreach ($assigned_members as $memberId) {
                    $assignStmt->execute([$projectId, $memberId]);
                }
            }

            $conn->commit();
            echo json_encode(["success" => true, "message" => "Project created successfully.", "project_id" => $projectId]);
            break;

        case 'PUT':
            // تعديل بيانات أو حالة المشروع
            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

            if (!$id) fail(400, "Invalid project ID.");

            // لو الـ User مش أدمن، نتأكد إنه مسند ليه المشروع لو حابب يغيّر حالته مثلاً
            if ($userRole !== 'admin') {
                $checkAssign = $conn->prepare("SELECT * FROM project_assignments WHERE project_id = ? AND user_id = ?");
                $checkAssign->execute([$id, $userId]);
                if (!$checkAssign->fetch()) {
                    fail(403, "Forbidden. You are not assigned to this project.");
                }
            }

            $status = $data['status'] ?? null;
            if ($status && !in_array($status, ALLOWED_STATUSES, true)) {
                fail(400, "Invalid status value.");
            }

            if ($userRole === 'admin') {
                $name = trim($data['name'] ?? '');
                $description = trim($data['description'] ?? '');
                $client_name = trim($data['client_name'] ?? '');
                
                $stmt = $conn->prepare("UPDATE projects SET name = ?, description = ?, client_name = ?, status = COALESCE(?, status) WHERE id = ?");
                $stmt->execute([$name, $description, $client_name, $status, $id]);
            } else {
                // الموظف يقدر يعدل الحالة بس لو مسموح له
                $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
            }

            echo json_encode(["success" => true, "message" => "Project updated successfully."]);
            break;

        case 'DELETE':
            // حذف المشروع (مقتصر على الـ Admin فقط)
            if ($userRole !== 'admin') {
                fail(403, "Forbidden. Only admins can delete projects.");
            }

            $data = json_decode(file_get_contents("php://input"), true) ?? [];
            $id = filter_var($data['id'] ?? ($_GET['id'] ?? 0), FILTER_VALIDATE_INT);
            if (!$id) fail(400, "Invalid project ID.");

            $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                fail(404, "Project not found.");
            }

            echo json_encode(["success" => true, "message" => "Project deleted successfully."]);
            break;

        default:
            fail(405, "Method not allowed.");
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log($e->getMessage());
    fail(500, "Database error. Please try again.");
}
?>