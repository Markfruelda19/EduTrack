<?php
// includes/header.php
// Usage: include at the top of every page.
// $pageTitle — optional, set before including this file.
$pageTitle = $pageTitle ?? 'EduTrack';
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
    <li><a href="/edutrack/index.php"         class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
      <span class="nav-icon">▦</span> Dashboard
    </a></li>
    <li><a href="/edutrack/students/view.php" class="<?= basename($_SERVER['PHP_SELF']) === 'view.php' ? 'active' : '' ?>">
      <span class="nav-icon">◈</span> Students
    </a></li>
    <li><a href="/edutrack/students/add.php"  class="<?= basename($_SERVER['PHP_SELF']) === 'add.php' ? 'active' : '' ?>">
      <span class="nav-icon">⊕</span> Add Student
    </a></li>
  </ul>
  <div class="sidebar-footer">
    <small>v1.0.0 &nbsp;·&nbsp; EduTrack</small>
  </div>
</nav>

<div class="main-wrapper">
  <header class="topbar">
    <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
    <div class="topbar-right">
      <span class="badge-school">BatStateU</span>
    </div>
  </header>
  <main class="content">
