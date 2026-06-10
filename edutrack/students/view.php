<?php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();
$pageTitle = 'Students';

$students = $conn->query("
    SELECT s.*, COALESCE(AVG(g.grade), NULL) AS average
    FROM students s
    LEFT JOIN grades g ON g.student_id = s.id
    GROUP BY s.id
    ORDER BY s.lastname, s.firstname
");

include __DIR__ . '/../includes/header.php';
?>

<div class="table-card">
  <div class="table-header">
    <h2>All Students</h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div class="search-box">
        <span class="search-icon">⌕</span>
        <input type="text" id="studentSearch" placeholder="Search by name, ID, or course…" autocomplete="off" />
      </div>
      <a href="/edutrack/grades/export_all_pdf.php" class="btn btn-success" target="_blank">⬇ Export All PDF</a>
      <a href="/edutrack/students/add.php" class="btn btn-primary">⊕ Add Student</a>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:44px;"></th>
        <th>Student ID</th>
        <th>Full Name</th>
        <th>Course</th>
        <th>Year</th>
        <th>Average</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="studentTableBody">
    <?php if ($students->num_rows === 0): ?>
      <tr><td colspan="7" class="no-data">No students found.</td></tr>
    <?php else: ?>
      <?php while ($s = $students->fetch_assoc()):
        $avg      = $s['average'];
        $cls      = $avg === null ? '' : ($avg >= 85 ? 'grade-pass' : ($avg >= 75 ? 'grade-average' : 'grade-fail'));
        $lbl      = $avg === null ? '' : ($avg >= 85 ? 'Excellent' : ($avg >= 75 ? 'Passing' : 'Failing'));
        $initials = strtoupper(substr($s['firstname'],0,1) . substr($s['lastname'],0,1));
        $photoUrl = $s['photo']
            ? '/edutrack/assets/uploads/students/' . htmlspecialchars($s['photo'])
            : null;
      ?>
      <tr>
        <td style="text-align:center;padding:8px 12px;">
          <?php if ($photoUrl): ?>
            <img src="<?= $photoUrl ?>" class="student-thumb" alt="<?= htmlspecialchars($s['firstname']) ?>" />
          <?php else: ?>
            <span class="student-thumb-placeholder"><?= $initials ?></span>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['student_id']) ?></td>
        <td><?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname']) ?></td>
        <td><?= htmlspecialchars($s['course']) ?></td>
        <td><?= htmlspecialchars($s['year_level']) ?></td>
        <td>
          <?php if ($avg !== null): ?>
            <span class="grade-number"><?= number_format($avg, 2) ?></span>
            <span class="grade-badge <?= $cls ?>"><?= $lbl ?></span>
          <?php else: ?>
            <span style="color:var(--text-3)">—</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="action-group">
            <a href="/edutrack/students/profile.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">View</a>
            <a href="/edutrack/students/edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
            <button class="btn btn-danger btn-sm"
              onclick="confirmDelete(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['firstname'] . ' ' . $s['lastname'])) ?>')">
              Delete
            </button>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
