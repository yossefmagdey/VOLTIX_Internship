<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    // دعم استقبال المفتاح سواء كان 'name' أو 'full_name' من الـ Frontend
    $name = sanitize_input($data['name'] ?? ($data['full_name'] ?? ''));
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';
    $role = sanitize_input($data['role'] ?? 'user');

    if (!$name || !$email || strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Please complete all fields with valid data (password min 6 chars)."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email is already registered."]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // الإدخال المباشر في العمود الصحيح full_name الموجود في قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $hashed_password, $role])) {
        $userId = $conn->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        echo json_encode([
            "success" => true,
            "message" => "Account created successfully.",
            "user" => ["id" => $userId, "name" => $name, "email" => $email, "role" => $role]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to create account."]);
    }
} elseif ($action === 'login') {
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Please enter email and password."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email']; 
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "user" => ["id" => $user['id'], "name" => $user['full_name'], "email" => $user['email'], "role" => $user['role']]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }

} elseif ($action === 'check' || $action === 'get_user') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $_SESSION['user_id'],
                "name" => $_SESSION['user_name'] ?? '',
                "email" => $_SESSION['user_email'] ?? '',
                "role" => $_SESSION['user_role'] ?? 'user'
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Not authenticated."]);
    }

} elseif ($action === 'logout') {
    $_SESSION = array();
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid action."]);
}
?>