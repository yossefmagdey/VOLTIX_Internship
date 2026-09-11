<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All required fields must be filled."]);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->execute([
            ':name' => htmlspecialchars(strip_tags($name)),
            ':email' => filter_var($email, FILTER_SANITIZE_EMAIL),
            ':subject' => htmlspecialchars(strip_tags($subject)),
            ':message' => htmlspecialchars(strip_tags($message))
        ]);

        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Inquiry sent successfully!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save inquiry."]);
    }
}
?>