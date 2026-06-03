<?php
/**
 * auth/seed_users.php
 * ─────────────────────────────────────────────
 * Run this ONCE in your browser after importing database.sql
 * to create the default admin and teacher accounts.
 *
 * URL: http://localhost/edutrack/auth/seed_users.php
 *
 * DELETE this file from your server after running it!
 */

require_once __DIR__ . '/../config/database.php';

$users = [
    [
        'username'  => 'admin',
        'full_name' => 'System Administrator',
        'email'     => 'admin@edutrack.ph',
        'password'  => 'Admin@1234',
        'role'      => 'admin',
    ],
    [
        'username'  => 'teacher1',
        'full_name' => 'Juan dela Cruz',
        'email'     => 'teacher1@edutrack.ph',
        'password'  => 'Teacher@1234',
        'role'      => 'teacher',
    ],
];

$conn   = getConnection();
$created = [];
$skipped = [];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $conn->prepare("
        INSERT IGNORE INTO users (username, full_name, email, password, role)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $u['username'], $u['full_name'], $u['email'], $hash, $u['role']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $created[] = $u['username'];
    } else {
        $skipped[] = $u['username'];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>EduTrack — Seed Users</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root { --bg:#0d0f14;--bg2:#13161e;--border:#252a38;--accent:#5b8dff;--green:#38d9a9;--red:#ff5f6d;--t1:#e8ecf5;--t2:#8b93a8;--t3:#555e74; }
    body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--t1); display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; }
    .card { background:var(--bg2); border:1px solid var(--border); border-radius:14px; padding:36px; max-width:480px; width:100%; }
    h1 { font-family:'Syne',sans-serif; font-weight:800; font-size:1.4rem; margin-bottom:6px; }
    p  { color:var(--t2); font-size:.9rem; margin-bottom:24px; }
    .row { padding:12px 16px; border-radius:8px; margin-bottom:8px; font-size:.875rem; display:flex; align-items:center; gap:10px; }
    .ok   { background:rgba(56,217,169,.1); border:1px solid rgba(56,217,169,.25); color:var(--green); }
    .skip { background:rgba(255,95,109,.1); border:1px solid rgba(255,95,109,.25); color:var(--red); }
    .warn { background:rgba(245,166,35,.1); border:1px solid rgba(245,166,35,.25); color:#f5a623; border-radius:8px; padding:14px 16px; margin-top:20px; font-size:.82rem; }
    a.btn { display:inline-block; margin-top:20px; background:var(--accent); color:#fff; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:500; font-size:.875rem; }
  </style>
</head>
<body>
<div class="card">
  <h1>⬡ EduTrack — User Seed</h1>
  <p>Setting up default accounts with bcrypt-hashed passwords.</p>

  <?php foreach ($created as $u): ?>
    <div class="row ok">✓ Created: <strong><?= htmlspecialchars($u) ?></strong></div>
  <?php endforeach; ?>
  <?php foreach ($skipped as $u): ?>
    <div class="row skip">— Already exists: <strong><?= htmlspecialchars($u) ?></strong></div>
  <?php endforeach; ?>

  <div class="warn">
    ⚠ <strong>Security:</strong> Delete or rename <code>auth/seed_users.php</code> from your server now that accounts are created.
  </div>

  <a class="btn" href="/edutrack/auth/login.php">Go to Login →</a>
</div>
</body>
</html>