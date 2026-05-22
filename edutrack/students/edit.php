<?php
require_once __DIR__ . '/../config/database.php';
$conn      = getConnection();
$pageTitle = 'Edit Student';
$errors    = [];
$success   = '';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /edutrack/students/view.php'); exit; }

// Fetch student
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { header('Location: /edutrack/students/view.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $firstname  = trim($_POST['firstname']  ?? '');
    $lastname   = trim($_POST['lastname']   ?? '');
    $course     = trim($_POST['course']     ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $email      = trim($_POST['email']      ?? '');

    if (!$student_id) $errors[] = 'Student ID is required.';
    if (!$firstname)  $errors[] = 'First name is required.';
    if (!$lastname)   $errors[] = 'Last name is required.';
    if (!$course)     $errors[] = 'Course is required.';
    if (!$year_level) $errors[] = 'Year level is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE students SET student_id=?, firstname=?, lastname=?, course=?, year_level=?, email=?
            WHERE id=?
        ");
        $stmt->bind_param('ssssssi', $student_id, $firstname, $lastname, $course, $year_level, $email, $id);
        if ($stmt->execute()) {
            $success = 'Student record updated successfully.';
            $student = array_merge($student, compact('student_id','firstname','lastname','course','year_level','email'));
        } else {
            $errors[] = $conn->errno === 1062 ? 'Student ID already in use.' : 'DB error: ' . $conn->error;
        }
    }
}

$courses = ['BS Computer Science','BS Information Technology','BS Information Systems','BS Data Science','BS Cybersecurity','Other'];
$years   = ['1st Year','2nd Year','3rd Year','4th Year'];

include __DIR__ . '/../includes/header.php';
?>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error">⚠ <?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>
<?php if ($success): ?>
  <div class="alert alert-success">✓ <?= $success ?></div>
<?php endif; ?>

<div style="margin-bottom:16px;">
  <a href="/edutrack/students/view.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">← Back to Profile</a>
</div>

<div class="form-card">
  <h2>Edit — <?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></h2>

  <form method="POST" novalidate>
    <div class="form-row">
      <div class="form-group">
        <label>Student ID *</label>
        <input type="text" name="student_id" value="<?= htmlspecialchars($student['student_id']) ?>" required />
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" placeholder="student@edu.ph" />
      </div>
      <div class="form-group">
        <label>First Name *</label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname']) ?>" required />
      </div>
      <div class="form-group">
        <label>Last Name *</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname']) ?>" required />
      </div>
      <div class="form-group">
        <label>Course *</label>
        <select name="course" required>
          <option value="">— Select —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= $c ?>" <?= $student['course'] === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Year Level *</label>
        <select name="year_level" required>
          <option value="">— Select —</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $student['year_level'] === $y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Student</button>
      <a href="/edutrack/students/view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
