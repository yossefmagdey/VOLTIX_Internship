<?php
// إدارة المستندات: أي مستخدم مسجّل دخول (عميل/موظف/أدمن) يرفع ويشوف ويحذف
// ملفاته هو بس. الملكية (ownership) بتتفحص بالإضافة للصلاحية في كل عملية.
require_once 'db.php';

// امتدادات مسموح بيها بس (Whitelist)، أي حاجة تانية بترفض فورًا
const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt', 'csv'];

// حد أقصى لحجم الملف: 5 ميجا
const MAX_FILE_SIZE = 5 * 1024 * 1024;

// توقيعات MIME خطيرة بترفض حتى لو الامتداد نفسه كان في الـ Whitelist فوق
// (مثال: حد يسمي ملف PHP باسم "report.pdf" - بنفحص المحتوى الحقيقي مش بس الاسم)
const BLOCKED_MIME_PATTERNS = ['php', 'x-httpd', 'x-executable', 'x-sh', 'x-msdownload', 'javascript'];

const UPLOAD_DIR = __DIR__ . '/uploads/documents/';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function fail(int $code, string $message): void {
    http_response_code($code);
    echo json_encode(["success" => false, "message" => $message]);
    exit();
}

function human_size(int $bytes): string {
    if ($bytes >= 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// بيتأكد إن المستند ده فعلًا ملك المستخدم الحالي، مش بس إن المستخدم مسجّل دخول
function fetchOwnedDocument(PDO $conn, int $id, int $userId): ?array {
    $stmt = $conn->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $doc = $stmt->fetch();
    return $doc ?: null;
}

try {
    if ($method === 'POST' && $action === 'upload') {
        requirePermission('documents.upload');
        $userId = (int)$_SESSION['user_id'];

        // 1. التعامل مع أخطاء الرفع المعروفة برسالة واضحة لكل حالة
        if (!isset($_FILES['file'])) fail(400, "Please choose a file to upload.");
        $file = $_FILES['file'];

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                fail(400, "Please choose a file to upload.");
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                fail(400, "File is too large. Maximum size is 5 MB.");
            case UPLOAD_ERR_PARTIAL:
                fail(400, "The file was only partially uploaded. Please try again.");
            default:
                fail(400, "Upload failed. Please try again.");
        }

        // 2. الحجم (تاني فحص مستقل عن php.ini، عشان نضمن الحد اللي احنا حددناه)
        if ($file['size'] <= 0) fail(400, "The uploaded file is empty.");
        if ($file['size'] > MAX_FILE_SIZE) {
            fail(400, "File is too large. Maximum size is 5 MB.");
        }

        // 3. الامتداد لازم يكون من ضمن القائمة المسموحة (بناخد آخر امتداد بس،
        // عشان ملف زي "invoice.php.pdf" يتفحص كـ pdf مش يتقبل غلط كـ php)
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!$ext || !in_array($ext, ALLOWED_EXTENSIONS, true)) {
            fail(400, "File type not allowed. Allowed types: " . implode(', ', ALLOWED_EXTENSIONS) . ".");
        }

        // 4. فحص المحتوى الحقيقي للملف (مش بس اسمه)، عشان نمنع ملف تنفيذي متنكر
        // في امتداد بريء
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        foreach (BLOCKED_MIME_PATTERNS as $pattern) {
            if ($realMime && stripos($realMime, $pattern) !== false) {
                fail(400, "This file's content is not allowed for security reasons.");
            }
        }

        // 5. التخزين: اسم عشوائي على الديسك (مش اسم المستخدم) عشان نمنع
        // الـ Path Traversal وتضارب الأسماء وتخمين ملفات حد تاني
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = UPLOAD_DIR . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            fail(500, "Could not save the file. Please try again.");
        }

        // 6. تسجيل بيانات الملف في قاعدة البيانات
        $stmt = $conn->prepare(
            "INSERT INTO documents (user_id, original_name, stored_name, mime_type, size_bytes)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, clean_text($originalName), $storedName, $realMime, $file['size']]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "File uploaded successfully.",
            "document" => [
                "id" => (int)$conn->lastInsertId(),
                "original_name" => clean_text($originalName),
                "mime_type" => $realMime,
                "size_bytes" => (int)$file['size'],
                "size_label" => human_size((int)$file['size']),
            ]
        ]);

    } elseif ($method === 'GET' && $action === 'list') {
        requirePermission('documents.view_own');
        $userId = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare(
            "SELECT id, original_name, mime_type, size_bytes, uploaded_at
             FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['size_label'] = human_size((int)$row['size_bytes']);
        }
        echo json_encode(["success" => true, "data" => $rows]);

    } elseif ($method === 'GET' && $action === 'download') {
        requirePermission('documents.view_own');
        $userId = (int)$_SESSION['user_id'];
        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) fail(400, "Invalid document ID.");

        // لازم يكون الملف ده ملك المستخدم الحالي بالظبط، مش أي ملف برقم id صحيح
        $doc = fetchOwnedDocument($conn, $id, $userId);
        if (!$doc) fail(404, "Document not found.");

        $path = UPLOAD_DIR . $doc['stored_name'];
        if (!is_file($path)) fail(404, "File is missing from storage.");

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit();

    } elseif ($method === 'DELETE') {
        requirePermission('documents.delete_own');
        $userId = (int)$_SESSION['user_id'];

        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) fail(400, "Invalid document ID.");

        $doc = fetchOwnedDocument($conn, $id, $userId);
        if (!$doc) fail(404, "Document not found.");

        $stmt = $conn->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        $path = UPLOAD_DIR . $doc['stored_name'];
        if (is_file($path)) @unlink($path);

        echo json_encode(["success" => true, "message" => "Document deleted successfully."]);

    } else {
        fail(405, "Method not allowed.");
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    fail(500, "Database error. Please try again.");
}
?>
