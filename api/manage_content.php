<?php
// Admin API: CRUD كامل على جدول services (Create / Read / Update / Delete)
require_once 'db.php';

// أي طلب لازم يكون مسجّل دخول وبصلاحية admin، وإلا بيرجع 401 أو 403 ويقف هنا
checkAuth('admin');

const MAX_CATEGORY = 100;
const MAX_TITLE = 150;
const MAX_DESCRIPTION = 2000;

$method = $_SERVER['REQUEST_METHOD'];
$data = [];

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
}

// بنقرأ الحقول ونتحقق منها في مكان واحد (مستخدمة في POST و PUT)
function readServiceFields(array $data): array {
    $category = clean_text($data['category'] ?? '') ?: 'General';
    $title = clean_text($data['title'] ?? '');
    $description = clean_text($data['description'] ?? '');

    if ($title === '' || $description === '') {
        fail(400, "Title and description are required.");
    }
    if (mb_strlen($category) > MAX_CATEGORY || mb_strlen($title) > MAX_TITLE || mb_strlen($description) > MAX_DESCRIPTION) {
        fail(400, "One of the fields is too long.");
    }
    return [$category, $title, $description];
}

function fail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query("SELECT id, category, title, description FROM services ORDER BY id DESC");
            echo json_encode(["success" => true, "data" => $stmt->fetchAll()]);
            break;

        case 'POST':
            [$category, $title, $description] = readServiceFields($data);
            $stmt = $conn->prepare("INSERT INTO services (category, title, description) VALUES (?, ?, ?)");
            $stmt->execute([$category, $title, $description]);

            http_response_code(201);
            echo json_encode([
                "success" => true,
                "message" => "Service added successfully.",
                "id" => (int)$conn->lastInsertId()
            ]);
            break;

        case 'PUT':
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) fail(400, "Invalid ID provided.");
            [$category, $title, $description] = readServiceFields($data);

            // بنتأكد إن الخدمة موجودة الأول (rowCount بيرجع 0 لو ما اتغيرش أي حاجة، فمش مناسب للتحقق)
            $check = $conn->prepare("SELECT id FROM services WHERE id = ?");
            $check->execute([$id]);
            if (!$check->fetch()) fail(404, "Service not found.");

            $stmt = $conn->prepare("UPDATE services SET category = ?, title = ?, description = ? WHERE id = ?");
            $stmt->execute([$category, $title, $description, $id]);
            echo json_encode(["success" => true, "message" => "Service updated successfully."]);
            break;

        case 'DELETE':
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) fail(400, "Invalid ID provided.");

            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) fail(404, "Service not found.");

            echo json_encode(["success" => true, "message" => "Service deleted successfully."]);
            break;

        default:
            fail(405, "Method not allowed.");
    }
} catch (PDOException $e) {
    // التفاصيل في الـ log بس، مش بنعرضها للمستخدم
    error_log($e->getMessage());
    fail(500, "Database error. Please try again.");
}
?>
