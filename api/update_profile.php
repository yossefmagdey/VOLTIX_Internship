<?php
require_once 'db.php';

// التأكد من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$newName = sanitize_input($input['name'] ?? '');

if (empty($newName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // تحديث الاسم باستخدام العمود الصحيح full_name
    $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
    $stmt->execute([$newName, $userId]);

    // تحديث الـ Session مباشرة
    $_SESSION['user_name'] = $newName;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>