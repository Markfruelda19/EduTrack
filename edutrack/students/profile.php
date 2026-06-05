<?php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /edutrack/students/view.php'); exit; }

// Fetch student
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { header('Location: /edutrack/students/view.php'); exit; }

$pageTitle = $student['firstname'] . ' ' . $student['lastname'];

// Fetch grades
$stmt2 = $conn->prepare("
    SELECT g.id, g.grade, s.subject_code, s.subject_name
    FROM grades g
    JOIN subjects s ON s.id = g.subject_id
    WHERE g.student_id = ?
    ORDER BY s.subject_name
");
$stmt2->bind_param('i', $id);
$stmt2->execute();
$grades = $stmt2->get_result();

// Compute average
$stmtAvg = $conn->prepare("SELECT AVG(grade) AS avg, COUNT(*) AS cnt FROM grades WHERE student_id = ?");
$stmtAvg->bind_param('i', $id);
$stmtAvg->execute();
$avgRow  = $stmtAvg->get_result()->fetch_assoc();
$average = $avgRow['avg'];

// Available subjects (not yet graded)
$stmtSub = $conn->prepare("
    SELECT * FROM subjects
    WHERE id NOT IN (SELECT subject_id FROM grades WHERE student_id = ?)
    ORDER BY subject_name
");
$stmtSub->bind_param('i', $id);
$stmtSub->execute();
$availSubs = $stmtSub->get_result();

include __DIR__ . '/../includes/header.php';

$initials = strtoupper(substr($student['firstname'],0,1) . substr($student['lastname'],0,1));
$cls = $average === null ? '' : ($average >= 85 ? 'grade-pass' : ($average >= 75 ? 'grade-average' : 'grade-fail'));
$lbl = $average === null ? 'No grades' : ($average >= 85 ? 'Excellent' : ($average >= 75 ? 'Passing' : 'Failing'));
?>

<!-- Profile Header -->
<div class="profile-header">
  <div class="avatar"><?= $initials ?></div>
  <div class="profile-info">
    <h2><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></h2>
    <div class="profile-meta">
      <span class="meta-item"><strong>ID:</strong> <?= htmlspecialchars($student['student_id']) ?></span>
      <span class="meta-item"><strong>Course:</strong> <?= htmlspecialchars($student['course']) ?></span>
      <span class="meta-item"><strong>Year:</strong> <?= htmlspecialchars($student['year_level']) ?></span>
      <?php if ($student['email']): ?>
        <span class="meta-item"><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></span>
      <?php endif; ?>
    </div>
  </div>
  <div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
    <?php if ($average !== null): ?>
      <div style="text-align:right;">
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;"><?= number_format($average,2) ?></div>
        <span class="grade-badge <?= $cls ?>"><?= $lbl ?></span>
      </div>
    <?php endif; ?>
    <div class="action-group">
      <a href="/edutrack/students/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">Edit</a>
        <a href="/edutrack/grades/export_pdf.php?id=<?= $id ?>" class="btn btn-success btn-sm" target="_blank">⬇ Export PDF</a>
      <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $id ?>, '<?= htmlspecialchars(addslashes($student['firstname'].' '.$student['lastname'])) ?>')">Delete</button>
    </div>
  </div>
</div>

<!-- Grades Section -->
<div class="table-card">
  <div class="table-header">
    <h2>Grades</h2>
    <span style="color:var(--text-3);font-size:.85rem;"><?= $avgRow['cnt'] ?> subject(s)</span>
  </div>
  <table>
    <thead>
      <tr>
        <th>Code</th>
        <th>Subject</th>
        <th>Grade</th>
        <th>Remarks</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($grades->num_rows === 0): ?>
      <tr><td colspan="5" class="no-data">No grades recorded yet.</td></tr>
    <?php else: ?>
      <?php while ($g = $grades->fetch_assoc()):
        $gcls = $g['grade'] >= 85 ? 'grade-pass' : ($g['grade'] >= 75 ? 'grade-average' : 'grade-fail');
        $glbl = $g['grade'] >= 85 ? 'Excellent' : ($g['grade'] >= 75 ? 'Passing' : 'Failing');
      ?>
      <tr>
        <td><?= htmlspecialchars($g['subject_code']) ?></td>
        <td class="subject-name"><?= htmlspecialchars($g['subject_name']) ?></td>
        <td><span class="grade-number"><?= number_format($g['grade'],2) ?></span></td>
        <td><span class="grade-badge <?= $gcls ?>"><?= $glbl ?></span></td>
        <td class="action-group">
          <a href="/edutrack/grades/edit_grade.php?id=<?= $g['id'] ?>&student=<?= $id ?>" class="btn btn-warning btn-sm">Edit</a>
          <button class="btn btn-danger btn-sm" onclick="confirmDeleteGrade(<?= $g['id'] ?>)">Remove</button>
        </td>
      </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add Grade Form -->
<?php if ($availSubs->num_rows > 0): ?>
<div class="form-card" style="margin-top:24px;max-width:480px;">
  <h2>⊕ Add Grade</h2>
  <form method="POST" action="/edutrack/grades/add_grade.php">
    <input type="hidden" name="student_id" value="<?= $id ?>" />
    <div class="form-row">
      <div class="form-group">
        <label>Subject</label>
        <select name="subject_id" required>
          <option value="">— Choose subject —</option>
          <?php while ($sub = $availSubs->fetch_assoc()): ?>
            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Grade (0–100)</label>
        <input type="number" name="grade" min="0" max="100" step="0.01" placeholder="e.g. 88.50" required />
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-success">Add Grade</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
