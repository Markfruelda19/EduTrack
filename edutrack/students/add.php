<?php
require_once __DIR__ . '/../config/database.php';
$conn      = getConnection();
$pageTitle = 'Add Student';
$errors    = [];
$success   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $firstname  = trim($_POST['firstname']  ?? '');
    $lastname   = trim($_POST['lastname']   ?? '');
    $course     = trim($_POST['course']     ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $email      = trim($_POST['email']      ?? '');

    // Validate
    if (!$student_id) $errors[] = 'Student ID is required.';
    if (!$firstname)  $errors[] = 'First name is required.';
    if (!$lastname)   $errors[] = 'Last name is required.';
    if (!$course)     $errors[] = 'Course is required.';
    if (!$year_level) $errors[] = 'Year level is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO students (student_id, firstname, lastname, course, year_level, email)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('ssssss', $student_id, $firstname, $lastname, $course, $year_level, $email);

        if ($stmt->execute()) {
            $newId   = $conn->insert_id;
            $success = "Student <strong>" . htmlspecialchars("$firstname $lastname") . "</strong> added successfully!";
            // Reset form
            $student_id = $firstname = $lastname = $course = $year_level = $email = '';
        } else {
            if ($conn->errno === 1062) {
                $errors[] = 'Student ID already exists. Please use a unique ID.';
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
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

<div class="form-card">
  <h2>⊕ New Student</h2>

  <form method="POST" novalidate>
    <div class="form-row">
      <div class="form-group">
        <label for="student_id">Student ID *</label>
        <input type="text" id="student_id" name="student_id"
               value="<?= htmlspecialchars($student_id ?? '') ?>"
               placeholder="e.g. 2024-0001" required />
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>"
               placeholder="student@edu.ph" />
      </div>
      <div class="form-group">
        <label for="firstname">First Name *</label>
        <input type="text" id="firstname" name="firstname"
               value="<?= htmlspecialchars($firstname ?? '') ?>"
               placeholder="Maria" required />
      </div>
      <div class="form-group">
        <label for="lastname">Last Name *</label>
        <input type="text" id="lastname" name="lastname"
               value="<?= htmlspecialchars($lastname ?? '') ?>"
               placeholder="Santos" required />
      </div>
      <div class="form-group">
        <label for="course">Course *</label>
        <select id="course" name="course" required>
          <option value="">— Select course —</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= ($course ?? '') === $c ? 'selected' : '' ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="year_level">Year Level *</label>
        <select id="year_level" name="year_level" required>
          <option value="">— Select year —</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= ($year_level ?? '') === $y ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Student</button>
      <a href="/edutrack/students/view.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
