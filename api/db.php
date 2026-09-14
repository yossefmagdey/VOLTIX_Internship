<?php
// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

$host = "localhost";
$db_name = "voltix_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage()); // Log error internally
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection error."]);
    exit();
}

// Role Authorization Helper
function checkAuth($requiredRole = null) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized: Please login first."]);
        exit();
    }
    
    if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Forbidden: Access denied."]);
        exit();
    }
}
?>