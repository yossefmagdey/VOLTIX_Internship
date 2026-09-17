<?php
require_once 'db.php';

// التأكد من الصلاحيات (أدمن فقط)
checkAuth('admin');

$method = $_SERVER['REQUEST_METHOD'];
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
$data = [];

if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
    } else {
        $data = $_POST;
    }
}

try {
    switch ($method) {
        case 'GET':
            $stmt = $conn->query("SELECT * FROM services ORDER BY id DESC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "success" => true,
                "data" => $rows
            ]);
            break;

        case 'POST':
            $category = sanitize_input($data['category'] ?? '');
            $title = sanitize_input($data['title'] ?? ($data['name'] ?? ''));
            $description = sanitize_input($data['description'] ?? ($data['message'] ?? ''));

            if (empty($title) || empty($description)) {
                echo json_encode(["success" => false, "message" => "Title and description are required."]);
                exit();
            }

            // تم إزالة عمود subject لعدم وجوده في الجدول
            $stmt = $conn->prepare("INSERT INTO services (category, title, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$category, $title, $description])) {
                echo json_encode(["success" => true, "message" => "Content block added successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to add content block."]);
            }
            break;

        case 'PUT':
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            $category = sanitize_input($data['category'] ?? '');
            $title = sanitize_input($data['title'] ?? ($data['name'] ?? ''));
            $description = sanitize_input($data['description'] ?? ($data['message'] ?? ''));

            if (!$id) {
                echo json_encode(["success" => false, "message" => "Invalid ID provided."]);
                exit();
            }

            // تم إزالة عمود subject هنا أيضاً
            $stmt = $conn->prepare("UPDATE services SET category = ?, title = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$category, $title, $description, $id])) {
                echo json_encode(["success" => true, "message" => "Content block updated successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update content block."]);
            }
            break;

        case 'DELETE':
            $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "Invalid ID provided."]);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(["success" => true, "message" => "Block deleted successfully."]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to delete block."]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(["success" => false, "message" => "Method not allowed."]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>