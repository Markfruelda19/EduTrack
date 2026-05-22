<?php
// students/delete.php — Deletes a student (cascade removes grades via FK)
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

$conn->close();
header('Location: /edutrack/students/view.php?msg=deleted');
exit;
