<?php
require_once __DIR__ . '/../config/database.php';
$conn      = getConnection();
$pageTitle = 'Edit Grade';
$errors    = [];
$success   = '';

$id         = intval($_GET['id']      ?? 0);
$student_id = intval($_GET['student'] ?? 0);

// Fetch existing grade
$stmt = $conn->prepare("
    SELECT g.*, s.subject_name, s.subject_code
    FROM grades g JOIN subjects s ON s.id = g.subject_id
    WHERE g.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$gradeRow = $stmt->get_result()->fetch_assoc();
if (!$gradeRow) { header("Location: /edutrack/students/profile.php?id={$student_id}"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newGrade = floatval($_POST['grade'] ?? 0);
    if ($newGrade < 0 || $newGrade > 100) {
        $errors[] = 'Grade must be between 0 and 100.';
    } else {
        $stmt2 = $conn->prepare("UPDATE grades SET grade = ? WHERE id = ?");
        $stmt2->bind_param('di', $newGrade, $id);
        $stmt2->execute();
        $success = 'Grade updated successfully.';
        $gradeRow['grade'] = $newGrade;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= $success ?></div><?php endif; ?>
<?php foreach ($errors as $e): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

<div style="margin-bottom:16px;">
  <a href="/edutrack/students/profile.php?id=<?= $student_id ?>" class="btn btn-ghost btn-sm">← Back to Profile</a>
</div>

<div class="form-card">
  <h2>Edit Grade — <?= htmlspecialchars($gradeRow['subject_name']) ?> (<?= htmlspecialchars($gradeRow['subject_code']) ?>)</h2>
  <form method="POST">
    <div class="form-group" style="max-width:220px;">
      <label>Grade (0–100)</label>
      <input type="number" name="grade" min="0" max="100" step="0.01"
             value="<?= htmlspecialchars($gradeRow['grade']) ?>" required />
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Grade</button>
      <a href="/edutrack/students/profile.php?id=<?= $student_id ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
