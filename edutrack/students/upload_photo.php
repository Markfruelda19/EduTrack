<?php
// students/upload_photo.php
// Handles profile picture upload for a student.
// Called via AJAX (fetch) from profile.php.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

header('Content-Type: application/json');

$id = intval($_POST['student_id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid student ID.']);
    exit;
}

// ── Validate file ──────────────────────────
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File too large.',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
    ];
    $code = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode(['error' => $uploadErrors[$code] ?? 'Upload failed.']);
    exit;
}

$file     = $_FILES['photo'];
$maxSize  = 3 * 1024 * 1024;   // 3 MB
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// Size check
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 3 MB.']);
    exit;
}

// MIME type check — use finfo for real detection, not just extension
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, WebP, or GIF allowed.']);
    exit;
}

// ── Get old photo to delete later ─────────
$conn   = getConnection();
$stmt   = $conn->prepare("SELECT photo FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$old    = $stmt->get_result()->fetch_assoc();
$oldPhoto = $old['photo'] ?? null;

// ── Save new file ──────────────────────────
$ext      = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => 'jpg',
};
$filename  = 'student_' . $id . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../assets/uploads/students/';
$destPath  = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save file. Check folder permissions.']);
    $conn->close();
    exit;
}

// ── Update DB ──────────────────────────────
$stmt2 = $conn->prepare("UPDATE students SET photo = ? WHERE id = ?");
$stmt2->bind_param('si', $filename, $id);
$stmt2->execute();

// ── Delete old photo if exists ─────────────
if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
    @unlink($uploadDir . $oldPhoto);
}

$conn->close();
echo json_encode([
    'success' => true,
    'photo'   => '/edutrack/assets/uploads/students/' . $filename,
    'message' => 'Profile photo updated.',
]);