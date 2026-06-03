<?php
require_once __DIR__ . '/../config/session.php';
requireLogin();
// grades/add_grade.php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$student_id = intval($_POST['student_id'] ?? 0);
$subject_id = intval($_POST['subject_id'] ?? 0);
$grade      = floatval($_POST['grade']     ?? 0);

if ($student_id && $subject_id && $grade >= 0 && $grade <= 100) {
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, subject_id, grade) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE grade = VALUES(grade)
    ");
    $stmt->bind_param('iid', $student_id, $subject_id, $grade);
    $stmt->execute();
}

$conn->close();
header("Location: /edutrack/students/profile.php?id={$student_id}&msg=grade_added");
exit;
