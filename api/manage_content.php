<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

// Restrict access to admin users only
checkAuth('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $services = $conn->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll();
    $inquiries = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll();

    echo json_encode([
        "success" => true,
        "services" => $services,
        "inquiries" => $inquiries
    ]);
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $_GET['action'] ?? '';

    if ($action === 'add_service') {
        $title = filter_var(trim($data['title'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $description = filter_var(trim($data['description'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $category = filter_var(trim($data['category'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$title || !$description) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO services (title, description, category) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $description, $category])) {
            echo json_encode(["success" => true, "message" => "Service added successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to add service."]);
        }
    } elseif ($action === 'delete_inquiry') {
        $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["success" => true, "message" => "Inquiry deleted successfully."]);
        }
    }
}
?>