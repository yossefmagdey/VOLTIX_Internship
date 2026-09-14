<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $name = filter_var(trim($data['name'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = filter_var(trim($data['subject'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_var(trim($data['message'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if (!$name || !$email || !$message) {
        echo json_encode(["success" => false, "message" => "Please fill in all required fields correctly."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $subject, $message])) {
        echo json_encode(["success" => true, "message" => "Inquiry submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to submit inquiry."]);
    }
}
?>