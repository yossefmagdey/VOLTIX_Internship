<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = sanitize_input($data['name'] ?? '');
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = sanitize_input($data['subject'] ?? 'General Inquiry');
    $message = sanitize_input($data['message'] ?? '');

    if (empty($name) || !$email || empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All required fields must be filled correctly."]);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);

        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Inquiry sent successfully!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save inquiry."]);
    }
}
?>