<?php
// تفعيل الـ Session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// إعدادات الـ Headers ورسائل الـ JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");

// استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db.php';

// استقبال بيانات الـ JSON القادمة من المتصفح
$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    // تم التعديل إلى full_name للربط مع الجدول
    $name = filter_var(trim($data['name'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';
    
    // تم التعديل إلى user بدلاً من client ليطابق الـ enum في الجدول
    $role = in_array($data['role'] ?? '', ['admin', 'user']) ? $data['role'] : 'user';

    if (!$name || !$email || strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Please complete all fields with valid data."]);
        exit();
    }

    // التحقق من تكرار البريد
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Email is already registered."]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // استخدام full_name داخل أمر الـ INSERT
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $hashed_password, $role])) {
        $userId = $conn->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;

        echo json_encode([
            "success" => true,
            "message" => "Account created successfully.",
            "user" => [
                "id" => $userId,
                "name" => $name,
                "email" => $email,
                "role" => $role
            ]
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
        $_SESSION['user_name'] = $user['full_name']; // استخدام full_name
        $_SESSION['user_role'] = $user['role'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "user" => [
                "id" => $user['id'],
                "name" => $user['full_name'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    }

} elseif ($action === 'logout') {
    $_SESSION = array();
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid action."]);
}
?>