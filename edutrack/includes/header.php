<?php
// includes/header.php
require_once __DIR__ . '/../config/session.php';
requireLogin();

$pageTitle = $pageTitle ?? 'EduTrack';
$user      = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — EduTrack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/edutrack/assets/css/style.css" />
</head>
<body>

<nav class="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">⬡</span>
    <span class="brand-name">EduTrack</span>
  </div>

  <ul class="nav-links">
    <li><a href="/edutrack/index.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') === false ? 'active' : '' ?>">
      <span class="nav-icon">▦</span> Dashboard
    </a></li>
    <li><a href="/edutrack/students/view.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'view.php' ? 'active' : '' ?>">
      <span class="nav-icon">◈</span> Students
    </a></li>
    <li><a href="/edutrack/students/add.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'add.php' ? 'active' : '' ?>">
      <span class="nav-icon">⊕</span> Add Student
    </a></li>

    <?php if (isAdmin()): ?>
    <li style="margin-top:12px;padding: 6px 14px;">
      <span style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);font-weight:600;">Admin</span>
    </li>
    <li><a href="/edutrack/admin/users.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
      <span class="nav-icon">⊞</span> Users
    </a></li>
    <?php endif; ?>
  </ul>

  <!-- Logged-in user block -->
  <div class="sidebar-user">
    <div class="user-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
    <div class="user-info">
      <span class="user-name"><?= htmlspecialchars($user['full_name']) ?></span>
      <span class="user-role"><?= ucfirst($user['role']) ?></span>
    </div>
  </div>

  <div class="sidebar-footer">
    <a href="/edutrack/auth/change_password.php" class="footer-link">Change Password</a>
    <a href="/edutrack/auth/logout.php" class="footer-link logout-link"
       onclick="return confirm('Sign out of EduTrack?')">Sign Out</a>
    <small style="color:var(--text-3);display:block;margin-top:8px;">v1.0.0 &nbsp;·&nbsp; EduTrack</small>
  </div>
</nav>

<div class="main-wrapper">
  <header class="topbar">
    <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="topbar-right">
      <?php if (isAdmin()): ?>
        <span class="badge-school" style="background:rgba(56,217,169,.12);color:#38d9a9;border-color:rgba(56,217,169,.3);">Admin</span>
      <?php else: ?>
        <span class="badge-school">Teacher</span>
      <?php endif; ?>
      <span class="badge-school" style="margin-left:8px;">BATSATEU</span>
    </div>
  </header>
  <main class="content">
