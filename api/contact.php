<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// الاتصال بقاعدة البيانات
$host = "localhost";
$db_name = "voltix_db";
$username = "root";
$password = ""; // الباسورد بتاعك في XAMPP/WAMP

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    echo json_encode(["success" => false, "message" => "Database connection error."]);
    exit();
}

// استقبال البيانات
$data = json_decode(file_get_contents("php://input"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($data->name ?? '');
    $email = trim($data->email ?? '');
    $subject = trim($data->subject ?? '');
    $message = trim($data->message ?? '');

    // 1. Validation: التأكد من الخانات المطلوبة
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit();
    }

    // 2. Validation: التأكد من صيغة الإيميل
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid email address format."]);
        exit();
    }

    // 3. الحفظ في قاعدة البيانات
    $query = "INSERT INTO inquiries (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
    $stmt = $conn->prepare($query);

    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":subject", $subject);
    $stmt->bindParam(":message", $message);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Inquiry sent successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save inquiry."]);
    }
}
?>