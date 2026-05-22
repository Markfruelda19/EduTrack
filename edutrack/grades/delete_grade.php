<?php
// grades/delete_grade.php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);
$student_id = 0;

if ($id) {
    // Get student_id for redirect before deleting
    $row = $conn->query("SELECT student_id FROM grades WHERE id = $id")->fetch_assoc();
    $student_id = $row['student_id'] ?? 0;

    $stmt = $conn->prepare("DELETE FROM grades WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

$conn->close();
header("Location: /edutrack/students/profile.php?id={$student_id}&msg=grade_deleted");
exit;
