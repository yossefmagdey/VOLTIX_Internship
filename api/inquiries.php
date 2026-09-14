<?php
$allowed_origin = "http://localhost";
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed_origin) {
    header("Access-Control-Allow-Origin: $allowed_origin");
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

// Check if user is admin
checkAuth('admin');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
            $inquiries = $stmt->fetchAll();
            echo json_encode(["success" => true, "data" => $inquiries]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error."]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['name']) && !empty($data['email']) && !empty($data['message'])) {
            try {
                $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
                $stmt->execute([
                    ':name' => sanitize_input($data['name']),
                    ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                    ':subject' => sanitize_input($data['subject'] ?? 'Direct Entry'),
                    ':message' => sanitize_input($data['message'])
                ]);
                echo json_encode(["success" => true, "message" => "Item created successfully!", "id" => $conn->lastInsertId()]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to add item."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Please fill in required fields."]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data['id']) && !empty($data['name']) && !empty($data['email']) && !empty($data['message'])) {
            try {
                $stmt = $conn->prepare("UPDATE inquiries SET name = :name, email = :email, subject = :subject, message = :message WHERE id = :id");
                $stmt->execute([
                    ':id' => intval($data['id']),
                    ':name' => sanitize_input($data['name']),
                    ':email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                    ':subject' => sanitize_input($data['subject'] ?? ''),
                    ':message' => sanitize_input($data['message'])
                ]);
                echo json_encode(["success" => true, "message" => "Item updated successfully!"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to update item."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Missing required data for update."]);
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'] ?? ($_GET['id'] ?? null);

        if ($id) {
            try {
                $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = :id");
                $stmt->execute([':id' => intval($id)]);
                echo json_encode(["success" => true, "message" => "Item deleted successfully!"]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Failed to delete item."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Item ID is required."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
        break;
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>