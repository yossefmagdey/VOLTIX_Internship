<?php
// إعدادات الـ Headers الموحدة لمنع مشاكل CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// إعدادات الـ Session الآمنة
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
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection error."]);
    exit();
}

// دالة التحقق من الصلاحيات (الطريقة القديمة: دور واحد بالظبط - لسه موجودة لأي كود قديم بيستخدمها)
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

// ============================================================
// Role-Based Access Control (RBAC)
// ============================================================
// كل الصلاحيات معرّفة في مكان واحد بس، وأي API بيسأل "المستخدم ده معاه
// الصلاحية دي ولا لأ؟" بدل ما يقارن اسم الـ role نفسه في كل ملف على حدة.
// كده لو ضفنا Role جديد أو غيّرنا صلاحيات Role موجود، التعديل بيحصل هنا بس.
const ROLE_PERMISSIONS = [
    'admin' => [
        'services.view', 'services.manage', 'services.delete',
        'requests.view', 'requests.update_status', 'requests.delete',
        'users.manage_roles',
        'documents.upload', 'documents.view_own', 'documents.delete_own',
    ],
    'employee' => [
        // الموظف العادي: يشوف الخدمات وطلبات العملاء، ويقدر يحدّث حالة الطلب بس.
        // مينفعش يضيف/يعدّل/يحذف خدمة، ولا يحذف طلب، ولا يغيّر صلاحيات حد.
        'services.view',
        'requests.view', 'requests.update_status',
        'documents.upload', 'documents.view_own', 'documents.delete_own',
    ],
    'user' => [
        // العميل العادي: مفيش له صلاحية على لوحات التحكم الداخلية، لكن زي أي
        // مستخدم مسجّل دخول بيقدر يرفع ويدير مستنداته الشخصية بس.
        'documents.upload', 'documents.view_own', 'documents.delete_own',
    ],
];

// بيرجع كل الصلاحيات المسموح بيها لدور معيّن (دور غير معروف = من غير صلاحيات)
function permissionsForRole(string $role): array {
    return ROLE_PERMISSIONS[$role] ?? [];
}

// بيتأكد إن المستخدم الحالي معاه صلاحية معيّنة
function hasPermission(string $permission): bool {
    $role = $_SESSION['user_role'] ?? null;
    if (!$role) return false;
    return in_array($permission, permissionsForRole($role), true);
}

// بوابة الحماية الفعلية: بتتنادى في أول كل عملية (GET/POST/PUT/DELETE) على حدة،
// مش مرة واحدة في أول الملف، عشان كل عملية تتحقق من الصلاحية المناسبة ليها بالظبط.
function requirePermission(string $permission): void {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized: Please login first."]);
        exit();
    }
    if (!hasPermission($permission)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Forbidden: your role does not have the \"$permission\" permission."
        ]);
        exit();
    }
}

// دالة تنظيف المدخلات العامة
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// تنظيف النصوص قبل التخزين: نشيل الـ HTML tags فقط.
// مش بنعمل htmlspecialchars هنا عشان البيانات تتخزن نظيفة وبدون تشفير مزدوج،
// والـ escape بيحصل وقت العرض في الـ Frontend.
function clean_text($data) {
    return trim(strip_tags((string)$data));
}
?>