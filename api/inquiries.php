<?php
require_once 'db.php';

// التحقق من أن المستخدم مسجل ومصرح له كـ admin
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
?>