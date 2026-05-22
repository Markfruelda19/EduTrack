<?php
require_once __DIR__ . '/config/database.php';
$conn = getConnection();
$pageTitle = 'Dashboard';

// ── Stats ────────────────────────────────────
$total     = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$passing   = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS c FROM (
        SELECT student_id, AVG(grade) AS avg
        FROM grades GROUP BY student_id HAVING avg >= 75
    ) t")->fetch_assoc()['c'];
$failing = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS c FROM (
        SELECT student_id, AVG(grade) AS avg
        FROM grades GROUP BY student_id HAVING avg < 75
    ) t")->fetch_assoc()['c'];
$topRow = $conn->query("
    SELECT s.firstname, s.lastname, AVG(g.grade) AS avg
    FROM grades g JOIN students s ON s.id = g.student_id
    GROUP BY g.student_id ORDER BY avg DESC LIMIT 1
")->fetch_assoc();

// ── Recent Students ───────────────────────────
$recent = $conn->query("
    SELECT s.*, COALESCE(AVG(g.grade),NULL) AS average
    FROM students s
    LEFT JOIN grades g ON g.student_id = s.id
    GROUP BY s.id
    ORDER BY s.created_at DESC LIMIT 8
");

include __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card accent-blue">
    <span class="stat-label">Total Students</span>
    <span class="stat-value"><?= $total ?></span>
    <span class="stat-sub">All enrolled</span>
  </div>
  <div class="stat-card accent-green">
    <span class="stat-label">Passing</span>
    <span class="stat-value"><?= $passing ?></span>
    <span class="stat-sub">Average ≥ 75</span>
  </div>
  <div class="stat-card accent-danger">
    <span class="stat-label">Failing</span>
    <span class="stat-value"><?= $failing ?></span>
    <span class="stat-sub">Average &lt; 75</span>
  </div>
  <div class="stat-card accent-warn">
    <span class="stat-label">Top Student</span>
    <span class="stat-value" style="font-size:1.1rem;margin-top:4px;">
      <?= $topRow ? htmlspecialchars($topRow['firstname'] . ' ' . $topRow['lastname']) : '—' ?>
    </span>
    <span class="stat-sub">
      <?= $topRow ? number_format($topRow['avg'], 2) . ' average' : 'No grades yet' ?>
    </span>
  </div>
</div>

<!-- Recent Students Table -->
<div class="table-card">
  <div class="table-header">
    <h2>Recent Students</h2>
    <a href="/edutrack/students/add.php" class="btn btn-primary">⊕ Add Student</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Course</th>
        <th>Year</th>
        <th>Average</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($recent->num_rows === 0): ?>
      <tr><td colspan="7" class="no-data">No students yet. <a href="/edutrack/students/add.php" style="color:var(--accent)">Add one →</a></td></tr>
    <?php else: ?>
      <?php while ($s = $recent->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($s['student_id']) ?></td>
        <td><?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname']) ?></td>
        <td><?= htmlspecialchars($s['course']) ?></td>
        <td><?= htmlspecialchars($s['year_level']) ?></td>
        <td>
          <?php if ($s['average'] !== null): ?>
            <span class="grade-number"><?= number_format($s['average'], 2) ?></span>
          <?php else: ?>
            <span style="color:var(--text-3)">—</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($s['average'] !== null):
            $cls = $s['average'] >= 85 ? 'grade-pass' : ($s['average'] >= 75 ? 'grade-average' : 'grade-fail');
            $lbl = $s['average'] >= 85 ? 'Excellent' : ($s['average'] >= 75 ? 'Passing' : 'Failing');
          ?>
            <span class="grade-badge <?= $cls ?>"><?= $lbl ?></span>
          <?php else: ?>
            <span class="grade-badge" style="color:var(--text-3)">No grades</span>
          <?php endif; ?>
        </td>
        <td class="action-group">
          <a href="/edutrack/students/profile.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">View</a>
          <a href="/edutrack/students/edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
        </td>
      </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div style="margin-top:16px;">
  <a href="/edutrack/students/view.php" class="btn btn-ghost">View all students →</a>
</div>

<?php $conn->close(); include __DIR__ . '/includes/footer.php'; ?>