<?php
// students/search.php — JSON endpoint for AJAX live search
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$conn = getConnection();
$q    = '%' . trim($_GET['q'] ?? '') . '%';

$stmt = $conn->prepare("
    SELECT s.id, s.student_id, s.firstname, s.lastname, s.course, s.year_level,
           COALESCE(AVG(g.grade), NULL) AS average
    FROM students s
    LEFT JOIN grades g ON g.student_id = s.id
    WHERE s.student_id LIKE ?
       OR s.firstname  LIKE ?
       OR s.lastname   LIKE ?
       OR s.course     LIKE ?
    GROUP BY s.id
    ORDER BY s.lastname, s.firstname
    LIMIT 50
");
$stmt->bind_param('ssss', $q, $q, $q, $q);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
$conn->close();
