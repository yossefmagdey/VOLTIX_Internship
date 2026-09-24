<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // clean_text بيشيل الـ HTML tags بس، من غير ما يشفّر النص وقت التخزين
    // (الـ escape بيحصل وقت العرض في لوحة تحكم الطلبات)
    $name = clean_text($data['name'] ?? '');
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $subject = clean_text($data['subject'] ?? 'General Inquiry') ?: 'General Inquiry';
    $message = clean_text($data['message'] ?? '');

    if (empty($name) || !$email || empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All required fields must be filled correctly."]);
        exit();
    }
    if (mb_strlen($name) > 150 || mb_strlen($subject) > 200 || mb_strlen($message) > 3000) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "One of the fields is too long."]);
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
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
}
?>