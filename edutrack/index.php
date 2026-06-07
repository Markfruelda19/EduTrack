<?php
require_once __DIR__ . '/config/database.php';
$conn = getConnection();
$pageTitle = 'Dashboard';

// ── Core stats ────────────────────────────────
$total    = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$passing  = $conn->query("
    SELECT COUNT(*) AS c FROM (
        SELECT student_id FROM grades GROUP BY student_id HAVING AVG(grade) >= 75
    ) t")->fetch_assoc()['c'];
$failing  = $conn->query("
    SELECT COUNT(*) AS c FROM (
        SELECT student_id FROM grades GROUP BY student_id HAVING AVG(grade) < 75
    ) t")->fetch_assoc()['c'];
$noGrades = $conn->query("
    SELECT COUNT(*) AS c FROM students s
    WHERE NOT EXISTS (SELECT 1 FROM grades g WHERE g.student_id = s.id)
")->fetch_assoc()['c'];
$classAvg = $conn->query("SELECT AVG(grade) AS avg FROM grades")->fetch_assoc()['avg'];
$totalSubjects = $conn->query("SELECT COUNT(*) AS c FROM subjects")->fetch_assoc()['c'];

// ── Top 5 students ────────────────────────────
$topStudents = $conn->query("
    SELECT s.firstname, s.lastname, s.course, s.year_level, AVG(g.grade) AS avg
    FROM grades g JOIN students s ON s.id = g.student_id
    GROUP BY g.student_id ORDER BY avg DESC LIMIT 5
");

// ── Grade distribution (buckets) ─────────────
$distribution = $conn->query("
    SELECT
        SUM(CASE WHEN avg >= 96 THEN 1 ELSE 0 END) AS excellent,
        SUM(CASE WHEN avg >= 90 AND avg < 96 THEN 1 ELSE 0 END) AS very_good,
        SUM(CASE WHEN avg >= 85 AND avg < 90 THEN 1 ELSE 0 END) AS good,
        SUM(CASE WHEN avg >= 80 AND avg < 85 THEN 1 ELSE 0 END) AS satisfactory,
        SUM(CASE WHEN avg >= 75 AND avg < 80 THEN 1 ELSE 0 END) AS passing,
        SUM(CASE WHEN avg < 75 THEN 1 ELSE 0 END) AS failed
    FROM (
        SELECT student_id, AVG(grade) AS avg FROM grades GROUP BY student_id
    ) t
")->fetch_assoc();

// ── Students per course ───────────────────────
$byCourse = $conn->query("
    SELECT course, COUNT(*) AS cnt FROM students GROUP BY course ORDER BY cnt DESC
");
$courseLabels = []; $courseCounts = [];
while ($r = $byCourse->fetch_assoc()) {
    $courseLabels[] = $r['course'];
    $courseCounts[] = (int)$r['cnt'];
}

// ── Students per year level ───────────────────
$byYear = $conn->query("
    SELECT year_level, COUNT(*) AS cnt FROM students GROUP BY year_level ORDER BY year_level
");
$yearLabels = []; $yearCounts = [];
while ($r = $byYear->fetch_assoc()) {
    $yearLabels[] = $r['year_level'];
    $yearCounts[] = (int)$r['cnt'];
}

// ── Average grade per subject ─────────────────
$bySubject = $conn->query("
    SELECT s.subject_name, AVG(g.grade) AS avg, COUNT(g.id) AS cnt
    FROM grades g JOIN subjects s ON s.id = g.subject_id
    GROUP BY g.subject_id ORDER BY avg DESC
");
$subjLabels = []; $subjAvgs = [];
while ($r = $bySubject->fetch_assoc()) {
    $subjLabels[] = $r['subject_name'];
    $subjAvgs[]   = round((float)$r['avg'], 2);
}

// ── Pass rate per course ──────────────────────
$passByCourse = $conn->query("
    SELECT s.course,
        COUNT(DISTINCT s.id) AS total,
        SUM(CASE WHEN ca.avg >= 75 THEN 1 ELSE 0 END) AS passing
    FROM students s
    LEFT JOIN (
        SELECT student_id, AVG(grade) AS avg FROM grades GROUP BY student_id
    ) ca ON ca.student_id = s.id
    GROUP BY s.course ORDER BY s.course
");
$prCourseLabels = []; $prPassRates = [];
while ($r = $passByCourse->fetch_assoc()) {
    $prCourseLabels[] = $r['course'];
    $prPassRates[]    = $r['total'] > 0 ? round($r['passing'] / $r['total'] * 100, 1) : 0;
}

// ── Recent students ───────────────────────────
$recent = $conn->query("
    SELECT s.*, COALESCE(AVG(g.grade), NULL) AS average
    FROM students s
    LEFT JOIN grades g ON g.student_id = s.id
    GROUP BY s.id ORDER BY s.created_at DESC LIMIT 6
");

include __DIR__ . '/includes/header.php';
?>

<!-- ── Stat Cards ─────────────────────────────── -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
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
    <span class="stat-label">No Grades</span>
    <span class="stat-value"><?= $noGrades ?></span>
    <span class="stat-sub">Not yet graded</span>
  </div>
  <div class="stat-card" style="border-left:3px solid #a855f7;">
    <span class="stat-label">Class Average</span>
    <span class="stat-value"><?= $classAvg !== null ? number_format($classAvg, 2) : '—' ?></span>
    <span class="stat-sub">Overall GPA</span>
  </div>
  <div class="stat-card" style="border-left:3px solid #06b6d4;">
    <span class="stat-label">Subjects</span>
    <span class="stat-value"><?= $totalSubjects ?></span>
    <span class="stat-sub">Active subjects</span>
  </div>
</div>

<!-- ── Row 1: Grade Distribution + Pass/Fail Donut ── -->
<div class="analytics-row">

  <!-- Grade Distribution Bar Chart -->
  <div class="chart-card wide">
    <div class="chart-header">
      <h2>Grade Distribution</h2>
      <span class="chart-sub">Students by performance bracket</span>
    </div>
    <div class="chart-wrap" style="height:220px;">
      <canvas id="distChart"></canvas>
    </div>
  </div>

  <!-- Pass / Fail / No Grades Donut -->
  <div class="chart-card">
    <div class="chart-header">
      <h2>Pass / Fail Rate</h2>
      <span class="chart-sub">Based on 75 threshold</span>
    </div>
    <div class="chart-wrap" style="height:220px;">
      <canvas id="donutChart"></canvas>
    </div>
    <div class="donut-legend">
      <span class="leg-item"><span class="leg-dot" style="background:#38d9a9"></span>Passing (<?= $passing ?>)</span>
      <span class="leg-item"><span class="leg-dot" style="background:#ff5f6d"></span>Failing (<?= $failing ?>)</span>
      <span class="leg-item"><span class="leg-dot" style="background:#555e74"></span>No Grades (<?= $noGrades ?>)</span>
    </div>
  </div>
</div>

<!-- ── Row 2: Subject Averages + Students by Course ── -->
<div class="analytics-row">

  <!-- Average by Subject horizontal bar -->
  <div class="chart-card wide">
    <div class="chart-header">
      <h2>Average Grade by Subject</h2>
      <span class="chart-sub">Mean score across all students</span>
    </div>
    <div class="chart-wrap" style="height:<?= max(180, count($subjLabels) * 38) ?>px;">
      <canvas id="subjectChart"></canvas>
    </div>
  </div>

  <!-- Students by Year Level -->
  <div class="chart-card">
    <div class="chart-header">
      <h2>Students by Year</h2>
      <span class="chart-sub">Enrollment per year level</span>
    </div>
    <div class="chart-wrap" style="height:220px;">
      <canvas id="yearChart"></canvas>
    </div>
  </div>
</div>

<!-- ── Row 3: Pass Rate by Course + Enrollment by Course ── -->
<div class="analytics-row">

  <!-- Pass rate per course -->
  <div class="chart-card wide">
    <div class="chart-header">
      <h2>Pass Rate by Course</h2>
      <span class="chart-sub">% of students passing per program</span>
    </div>
    <div class="chart-wrap" style="height:220px;">
      <canvas id="passRateChart"></canvas>
    </div>
  </div>

  <!-- Enrollment by course doughnut -->
  <div class="chart-card">
    <div class="chart-header">
      <h2>Enrollment by Course</h2>
      <span class="chart-sub">Student count per program</span>
    </div>
    <div class="chart-wrap" style="height:220px;">
      <canvas id="courseChart"></canvas>
    </div>
  </div>
</div>

<!-- ── Top 5 Students ─────────────────────────── -->
<div class="table-card" style="margin-bottom:24px;">
  <div class="table-header">
    <h2>🏆 Top 5 Students</h2>
    <span class="chart-sub">Ranked by overall average</span>
  </div>
  <table>
    <thead>
      <tr><th>#</th><th>Name</th><th>Course</th><th>Year</th><th>Average</th><th>Remarks</th></tr>
    </thead>
    <tbody>
    <?php $rank = 1; while ($s = $topStudents->fetch_assoc()):
      $avg = (float)$s['avg'];
      $cls = $avg >= 85 ? 'grade-pass' : ($avg >= 75 ? 'grade-average' : 'grade-fail');
      $lbl = $avg >= 96 ? 'Excellent' : ($avg >= 90 ? 'Very Good' : ($avg >= 85 ? 'Good' : ($avg >= 75 ? 'Passing' : 'Failing')));
      $medals = ['🥇','🥈','🥉','④','⑤'];
    ?>
    <tr>
      <td style="font-size:1.1rem;"><?= $medals[$rank-1] ?? $rank ?></td>
      <td><?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname']) ?></td>
      <td><?= htmlspecialchars($s['course']) ?></td>
      <td><?= htmlspecialchars($s['year_level']) ?></td>
      <td><span class="grade-number"><?= number_format($avg, 2) ?></span></td>
      <td><span class="grade-badge <?= $cls ?>"><?= $lbl ?></span></td>
    </tr>
    <?php $rank++; endwhile; ?>
    </tbody>
  </table>
</div>

<!-- ── Recent Students ────────────────────────── -->
<div class="table-card">
  <div class="table-header">
    <h2>Recently Added</h2>
    <div style="display:flex;gap:8px;">
      <a href="/edutrack/grades/export_all_pdf.php" class="btn btn-success btn-sm" target="_blank">⬇ Export PDF</a>
      <a href="/edutrack/students/add.php" class="btn btn-primary btn-sm">⊕ Add Student</a>
    </div>
  </div>
  <table>
    <thead>
      <tr><th>Student ID</th><th>Name</th><th>Course</th><th>Year</th><th>Average</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if ($recent->num_rows === 0): ?>
      <tr><td colspan="7" class="no-data">No students yet. <a href="/edutrack/students/add.php" style="color:var(--accent)">Add one →</a></td></tr>
    <?php else: while ($s = $recent->fetch_assoc()):
        $cls = $s['average'] !== null ? ($s['average'] >= 85 ? 'grade-pass' : ($s['average'] >= 75 ? 'grade-average' : 'grade-fail')) : '';
        $lbl = $s['average'] !== null ? ($s['average'] >= 85 ? 'Excellent' : ($s['average'] >= 75 ? 'Passing' : 'Failing')) : 'No grades';
    ?>
    <tr>
      <td><?= htmlspecialchars($s['student_id']) ?></td>
      <td><?= htmlspecialchars($s['lastname'] . ', ' . $s['firstname']) ?></td>
      <td><?= htmlspecialchars($s['course']) ?></td>
      <td><?= htmlspecialchars($s['year_level']) ?></td>
      <td><?= $s['average'] !== null ? '<span class="grade-number">' . number_format($s['average'], 2) . '</span>' : '<span style="color:var(--text-3)">—</span>' ?></td>
      <td><span class="grade-badge <?= $cls ?>"><?= $lbl ?></span></td>
      <td class="action-group">
        <a href="/edutrack/students/profile.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">View</a>
        <a href="/edutrack/students/edit.php?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
      </td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table>
</div>

<div style="margin-top:16px;">
  <a href="/edutrack/students/view.php" class="btn btn-ghost">View all students →</a>
</div>

<!-- ── Chart.js ───────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// PHP data → JS
const distData    = <?= json_encode(array_values($distribution)) ?>;
const distLabels  = ['Excellent\n(96-100)','Very Good\n(90-95)','Good\n(85-89)','Satisfactory\n(80-84)','Passing\n(75-79)','Failed\n(<75)'];
const distColors  = ['#38d9a9','#5b8dff','#a855f7','#06b6d4','#f5a623','#ff5f6d'];

const subjLabels  = <?= json_encode($subjLabels) ?>;
const subjAvgs    = <?= json_encode($subjAvgs) ?>;

const yearLabels  = <?= json_encode($yearLabels) ?>;
const yearCounts  = <?= json_encode($yearCounts) ?>;

const courseLabels = <?= json_encode($courseLabels) ?>;
const courseCounts = <?= json_encode($courseCounts) ?>;

const prLabels    = <?= json_encode($prCourseLabels) ?>;
const prRates     = <?= json_encode($prPassRates) ?>;

const passing     = <?= (int)$passing ?>;
const failing     = <?= (int)$failing ?>;
const noGrades    = <?= (int)$noGrades ?>;

// ── Shared defaults ─────────────────────────
Chart.defaults.color          = '#8b93a8';
Chart.defaults.borderColor    = '#252a38';
Chart.defaults.font.family    = "'DM Sans', sans-serif";
Chart.defaults.font.size      = 12;

const gridColor  = 'rgba(37,42,56,0.8)';
const tickColor  = '#555e74';

function baseScales(axis = 'y') {
  const cfg = {
    grid: { color: gridColor },
    ticks: { color: tickColor },
    border: { color: 'transparent' },
  };
  return axis === 'xy'
    ? { x: cfg, y: cfg }
    : { [axis]: cfg, [axis === 'y' ? 'x' : 'y']: { ...cfg, grid: { display: false } } };
}

// ── 1. Grade Distribution Bar ───────────────
new Chart(document.getElementById('distChart'), {
  type: 'bar',
  data: {
    labels: distLabels,
    datasets: [{
      data: distData,
      backgroundColor: distColors.map(c => c + '33'),
      borderColor: distColors,
      borderWidth: 2,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} student(s)` } }
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: tickColor } },
      y: { grid: { color: gridColor }, ticks: { color: tickColor, stepSize: 1 },
           border: { color: 'transparent' } }
    }
  }
});

// ── 2. Donut ───────────────────────────────
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Passing', 'Failing', 'No Grades'],
    datasets: [{
      data: [passing, failing, noGrades],
      backgroundColor: ['rgba(56,217,169,.25)', 'rgba(255,95,109,.25)', 'rgba(85,94,116,.2)'],
      borderColor:     ['#38d9a9', '#ff5f6d', '#555e74'],
      borderWidth: 2,
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed} students` } }
    }
  }
});

// ── 3. Subject Average horizontal bar ──────
new Chart(document.getElementById('subjectChart'), {
  type: 'bar',
  data: {
    labels: subjLabels,
    datasets: [{
      label: 'Average',
      data: subjAvgs,
      backgroundColor: subjAvgs.map(v =>
        v >= 85 ? 'rgba(56,217,169,.25)' : v >= 75 ? 'rgba(91,141,255,.25)' : 'rgba(255,95,109,.25)'
      ),
      borderColor: subjAvgs.map(v =>
        v >= 85 ? '#38d9a9' : v >= 75 ? '#5b8dff' : '#ff5f6d'
      ),
      borderWidth: 2,
      borderRadius: 4,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x.toFixed(2)} avg` } }
    },
    scales: {
      x: {
        min: 60, max: 100,
        grid: { color: gridColor }, ticks: { color: tickColor },
        border: { color: 'transparent' }
      },
      y: { grid: { display: false }, ticks: { color: '#e8ecf5' } }
    }
  }
});

// ── 4. Students by Year polar/bar ──────────
const yearPalette = ['#5b8dff','#38d9a9','#f5a623','#ff5f6d','#a855f7'];
new Chart(document.getElementById('yearChart'), {
  type: 'bar',
  data: {
    labels: yearLabels,
    datasets: [{
      data: yearCounts,
      backgroundColor: yearLabels.map((_, i) => yearPalette[i % yearPalette.length] + '33'),
      borderColor:     yearLabels.map((_, i) => yearPalette[i % yearPalette.length]),
      borderWidth: 2,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} student(s)` } }
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: tickColor } },
      y: { grid: { color: gridColor }, ticks: { color: tickColor, stepSize: 1 },
           border: { color: 'transparent' } }
    }
  }
});

// ── 5. Pass Rate by Course ─────────────────
new Chart(document.getElementById('passRateChart'), {
  type: 'bar',
  data: {
    labels: prLabels,
    datasets: [{
      label: 'Pass Rate %',
      data: prRates,
      backgroundColor: prRates.map(v => v >= 75 ? 'rgba(56,217,169,.2)' : 'rgba(255,95,109,.2)'),
      borderColor:     prRates.map(v => v >= 75 ? '#38d9a9' : '#ff5f6d'),
      borderWidth: 2,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y}% pass rate` } }
    },
    scales: {
      x: { grid: { display: false }, ticks: { color: tickColor } },
      y: {
        min: 0, max: 100,
        grid: { color: gridColor }, ticks: { color: tickColor, callback: v => v + '%' },
        border: { color: 'transparent' }
      }
    }
  }
});

// ── 6. Enrollment by Course doughnut ───────
const coursePalette = ['#5b8dff','#38d9a9','#f5a623','#ff5f6d','#a855f7','#06b6d4'];
new Chart(document.getElementById('courseChart'), {
  type: 'doughnut',
  data: {
    labels: courseLabels,
    datasets: [{
      data: courseCounts,
      backgroundColor: courseLabels.map((_, i) => coursePalette[i % coursePalette.length] + '33'),
      borderColor:     courseLabels.map((_, i) => coursePalette[i % coursePalette.length]),
      borderWidth: 2,
      hoverOffset: 6,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '60%',
    plugins: {
      legend: {
        display: true,
        position: 'bottom',
        labels: { color: '#8b93a8', boxWidth: 10, padding: 10, font: { size: 10 } }
      },
      tooltip: { callbacks: { label: ctx => ` ${ctx.parsed} students` } }
    }
  }
});
</script>

<?php $conn->close(); include __DIR__ . '/includes/footer.php'; ?>
