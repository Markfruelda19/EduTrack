<?php
require_once __DIR__ . '/../config/database.php';
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /edutrack/students/view.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { header('Location: /edutrack/students/view.php'); exit; }

$pageTitle = $student['firstname'] . ' ' . $student['lastname'];

$stmt2 = $conn->prepare("
    SELECT g.id, g.grade, s.subject_code, s.subject_name
    FROM grades g JOIN subjects s ON s.id = g.subject_id
    WHERE g.student_id = ? ORDER BY s.subject_name
");
$stmt2->bind_param('i', $id);
$stmt2->execute();
$grades = $stmt2->get_result();

$stmtAvg = $conn->prepare("SELECT AVG(grade) AS avg, COUNT(*) AS cnt FROM grades WHERE student_id = ?");
$stmtAvg->bind_param('i', $id);
$stmtAvg->execute();
$avgRow  = $stmtAvg->get_result()->fetch_assoc();
$average = $avgRow['avg'];

$stmtSub = $conn->prepare("
    SELECT * FROM subjects
    WHERE id NOT IN (SELECT subject_id FROM grades WHERE student_id = ?)
    ORDER BY subject_name
");
$stmtSub->bind_param('i', $id);
$stmtSub->execute();
$availSubs = $stmtSub->get_result();

include __DIR__ . '/../includes/header.php';

$initials  = strtoupper(substr($student['firstname'],0,1) . substr($student['lastname'],0,1));
$cls       = $average === null ? '' : ($average >= 85 ? 'grade-pass' : ($average >= 75 ? 'grade-average' : 'grade-fail'));
$lbl       = $average === null ? 'No grades' : ($average >= 85 ? 'Excellent' : ($average >= 75 ? 'Passing' : 'Failing'));
$photoUrl  = $student['photo']
    ? '/edutrack/assets/uploads/students/' . htmlspecialchars($student['photo'])
    : null;
?>

<!-- Profile Header -->
<div class="profile-header">

  <!-- ── Avatar with upload overlay ───────── -->
  <div class="avatar-upload-wrap" id="avatarWrap">

    <?php if ($photoUrl): ?>
      <img src="<?= $photoUrl ?>" alt="Profile photo" class="avatar-img" id="avatarImg" />
    <?php else: ?>
      <div class="avatar avatar-lg" id="avatarInitials"><?= $initials ?></div>
    <?php endif; ?>

    <!-- Hover overlay -->
    <label for="photoInput" class="avatar-overlay" title="Upload photo">
      <span class="overlay-icon">📷</span>
      <span class="overlay-text">Change Photo</span>
    </label>

    <!-- Hidden file input -->
    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp,image/gif"
           style="display:none;" />

    <!-- Remove button (only if photo exists) -->
    <?php if ($photoUrl): ?>
      <button class="avatar-remove-btn" id="removePhotoBtn" title="Remove photo">✕</button>
    <?php endif; ?>

    <!-- Upload progress ring -->
    <div class="upload-spinner" id="uploadSpinner" style="display:none;">
      <div class="spinner-ring"></div>
    </div>
  </div>

  <!-- ── Student info ───────────────────────── -->
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
    <!-- Upload feedback message -->
    <div id="photoMsg" class="photo-msg" style="display:none;"></div>
  </div>

  <!-- ── Average + actions ─────────────────── -->
  <div style="margin-left:auto;display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
    <?php if ($average !== null): ?>
      <div style="text-align:right;">
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;"><?= number_format($average,2) ?></div>
        <span class="grade-badge <?= $cls ?>"><?= $lbl ?></span>
      </div>
    <?php endif; ?>
    <div class="action-group">
      <a href="/edutrack/students/edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm">Edit</a>
      <a href="/edutrack/grades/export_pdf.php?id=<?= $id ?>" class="btn btn-success btn-sm" target="_blank">⬇ PDF</a>
      <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $id ?>, '<?= htmlspecialchars(addslashes($student['firstname'].' '.$student['lastname'])) ?>')">Delete</button>
    </div>
  </div>
</div>

<!-- Grades Table -->
<div class="table-card">
  <div class="table-header">
    <h2>Grades</h2>
    <span style="color:var(--text-3);font-size:.85rem;"><?= $avgRow['cnt'] ?> subject(s)</span>
  </div>
  <table>
    <thead>
      <tr><th>Code</th><th>Subject</th><th>Grade</th><th>Remarks</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if ($grades->num_rows === 0): ?>
      <tr><td colspan="5" class="no-data">No grades recorded yet.</td></tr>
    <?php else: while ($g = $grades->fetch_assoc()):
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
    <?php endwhile; endif; ?>
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

<!-- ── Photo Upload JS ───────────────────────── -->
<script>
(function() {
  const studentId   = <?= $id ?>;
  const photoInput  = document.getElementById('photoInput');
  const avatarWrap  = document.getElementById('avatarWrap');
  const spinner     = document.getElementById('uploadSpinner');
  const msgEl       = document.getElementById('photoMsg');
  const removeBtn   = document.getElementById('removePhotoBtn');

  function showMsg(text, type) {
    msgEl.textContent = text;
    msgEl.className   = 'photo-msg photo-msg-' + type;
    msgEl.style.display = 'block';
    setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
  }

  function setAvatar(url) {
    // Replace initials div or update existing img
    let img = document.getElementById('avatarImg');
    const initials = document.getElementById('avatarInitials');
    if (initials) initials.remove();
    if (!img) {
      img = document.createElement('img');
      img.id        = 'avatarImg';
      img.className = 'avatar-img';
      img.alt       = 'Profile photo';
      avatarWrap.insertBefore(img, avatarWrap.firstChild);
    }
    img.src = url + '?t=' + Date.now();   // cache-bust

    // Show remove button
    if (!document.getElementById('removePhotoBtn')) {
      const btn = document.createElement('button');
      btn.id        = 'removePhotoBtn';
      btn.className = 'avatar-remove-btn';
      btn.title     = 'Remove photo';
      btn.textContent = '✕';
      avatarWrap.appendChild(btn);
      btn.addEventListener('click', handleRemove);
    }
  }

  // ── Upload on file select ─────────────────
  photoInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    // Client-side checks
    const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!allowed.includes(file.type)) {
      showMsg('Only JPG, PNG, WebP or GIF allowed.', 'error');
      return;
    }
    if (file.size > 3 * 1024 * 1024) {
      showMsg('File too large. Max 3 MB.', 'error');
      return;
    }

    // Show spinner
    spinner.style.display = 'flex';

    const fd = new FormData();
    fd.append('student_id', studentId);
    fd.append('photo', file);

    fetch('/edutrack/students/upload_photo.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        spinner.style.display = 'none';
        if (data.success) {
          setAvatar(data.photo);
          showMsg('✓ Photo updated!', 'success');
        } else {
          showMsg('⚠ ' + (data.error ?? 'Upload failed.'), 'error');
        }
      })
      .catch(() => {
        spinner.style.display = 'none';
        showMsg('⚠ Upload failed. Please try again.', 'error');
      });

    this.value = '';  // reset input
  });

  // ── Remove photo ──────────────────────────
  function handleRemove() {
    if (!confirm('Remove profile photo?')) return;

    const fd = new FormData();
    fd.append('student_id', studentId);

    fetch('/edutrack/students/remove_photo.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const img = document.getElementById('avatarImg');
          if (img) img.remove();
          this.remove();

          // Restore initials avatar
          const initDiv = document.createElement('div');
          initDiv.id        = 'avatarInitials';
          initDiv.className = 'avatar avatar-lg';
          initDiv.textContent = '<?= $initials ?>';
          avatarWrap.insertBefore(initDiv, avatarWrap.firstChild);

          showMsg('Photo removed.', 'success');
        }
      });
  }

  if (removeBtn) removeBtn.addEventListener('click', handleRemove);

  // ── Drag-and-drop onto avatar ─────────────
  avatarWrap.addEventListener('dragover', e => { e.preventDefault(); avatarWrap.classList.add('drag-over'); });
  avatarWrap.addEventListener('dragleave', () => avatarWrap.classList.remove('drag-over'));
  avatarWrap.addEventListener('drop', e => {
    e.preventDefault();
    avatarWrap.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
      const dt = new DataTransfer();
      dt.items.add(file);
      photoInput.files = dt.files;
      photoInput.dispatchEvent(new Event('change'));
    }
  });
})();
</script>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>
