<?php
// students/remove_photo.php
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

$conn  = getConnection();
$stmt  = $conn->prepare("SELECT photo FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row   = $stmt->get_result()->fetch_assoc();

if ($row && $row['photo']) {
    $path = __DIR__ . '/../assets/uploads/students/' . $row['photo'];
    if (file_exists($path)) @unlink($path);

    $stmt2 = $conn->prepare("UPDATE students SET photo = NULL WHERE id = ?");
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
}

$conn->close();
echo json_encode(['success' => true]);