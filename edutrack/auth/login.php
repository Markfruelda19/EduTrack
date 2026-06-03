<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Auto-setup redirect: if no users exist, go to seed page
$conn = getConnection();
$userCount = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$conn->close();
if ($userCount == 0) {
    header("Location: /edutrack/auth/seed_users.php");
    exit;
}

// Already logged in → redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: /edutrack/index.php');
    exit;
}

$error     = '';
$username  = '';

// ── CSRF token ──────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Rate limiting (5 attempts per 10 min per IP) ────
$ip           = $_SERVER['REMOTE_ADDR'];
$attemptKey   = 'login_attempts_' . md5($ip);
$lockoutKey   = 'login_lockout_'  . md5($ip);

if (!isset($_SESSION[$attemptKey]))  $_SESSION[$attemptKey] = 0;
if (!isset($_SESSION[$lockoutKey]))  $_SESSION[$lockoutKey] = 0;

$isLockedOut  = $_SESSION[$lockoutKey] > time();
$attemptsLeft = max(0, 5 - $_SESSION[$attemptKey]);

// ── Handle POST ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';

    } elseif ($isLockedOut) {
        $wait  = ceil(($_SESSION[$lockoutKey] - time()) / 60);
        $error = "Too many failed attempts. Try again in {$wait} minute(s).";

    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'Please enter both username and password.';
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $conn->close();

            if ($user && password_verify($password, $user['password'])) {
                // ✅ Successful login
                $_SESSION[$attemptKey] = 0;
                $_SESSION[$lockoutKey] = 0;

                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_role']     = $user['role'];

                // Update last_login
                $conn2 = getConnection();
                $upd   = $conn2->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $upd->bind_param('i', $user['id']);
                $upd->execute();
                $conn2->close();

                $redirect = $_GET['redirect'] ?? '/edutrack/index.php';
                // Basic open-redirect protection
                if (!str_starts_with($redirect, '/edutrack/')) $redirect = '/edutrack/index.php';
                header("Location: {$redirect}");
                exit;

            } else {
                // ❌ Failed
                $_SESSION[$attemptKey]++;
                if ($_SESSION[$attemptKey] >= 5) {
                    $_SESSION[$lockoutKey] = time() + 600; // 10-min lockout
                    $error = 'Too many failed attempts. Account locked for 10 minutes.';
                } else {
                    $remaining = 5 - $_SESSION[$attemptKey];
                    $error     = "Invalid username or password. {$remaining} attempt(s) remaining.";
                }
            }
        }
    }
}

// Refresh lockout status after POST
$isLockedOut = $_SESSION[$lockoutKey] > time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — EduTrack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/edutrack/assets/css/style.css" />
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: var(--bg);
      padding: 24px;
    }

    .login-wrap {
      width: 100%;
      max-width: 420px;
      display: flex;
      flex-direction: column;
      gap: 28px;
    }

    .login-brand {
      text-align: center;
    }

    .login-brand .brand-icon {
      font-size: 2.5rem;
      color: var(--accent);
      display: block;
      margin-bottom: 8px;
    }

    .login-brand h1 {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.8rem;
      letter-spacing: -0.03em;
    }

    .login-brand p {
      color: var(--text-3);
      font-size: .875rem;
      margin-top: 6px;
    }

    .login-card {
      background: var(--bg-2);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
    }

    .login-card h2 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: 24px;
      color: var(--text-2);
      text-transform: uppercase;
      letter-spacing: .06em;
    }

    .login-card .form-group { margin-bottom: 18px; }

    .input-icon-wrap {
      position: relative;
    }

    .input-icon-wrap input {
      padding-left: 40px;
    }

    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-3);
      font-size: .95rem;
      pointer-events: none;
    }

    .btn-login {
      width: 100%;
      justify-content: center;
      padding: 12px;
      font-size: .95rem;
      border-radius: 9px;
      margin-top: 4px;
    }

    .demo-creds {
      background: var(--bg-3);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 14px 16px;
      font-size: .8rem;
      color: var(--text-2);
    }

    .demo-creds strong { color: var(--text-1); display: block; margin-bottom: 6px; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; }
    .demo-creds code {
      background: rgba(91,141,255,.1);
      color: var(--accent);
      padding: 2px 7px;
      border-radius: 4px;
      font-size: .82rem;
    }

    .divider { border: none; border-top: 1px solid var(--border); margin: 4px 0 16px; }

    .login-footer {
      text-align: center;
      font-size: .78rem;
      color: var(--text-3);
    }

    .shake { animation: shake .4s ease; }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%,60%  { transform: translateX(-6px); }
      40%,80%  { transform: translateX(6px); }
    }
  </style>
</head>
<body>

<div class="login-wrap">

  <div class="login-brand">
    <span class="brand-icon">⬡</span>
    <h1>EduTrack</h1>
    <p>Student Record Management System</p>
  </div>

  <div class="login-card <?= $error ? 'shake' : '' ?>">
    <h2>Sign In</h2>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:18px;">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($isLockedOut): ?>
      <div class="alert alert-error">🔒 Account temporarily locked. Please wait before trying again.</div>
    <?php else: ?>

    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <div class="form-group">
        <label for="username">Username or Email</label>
        <div class="input-icon-wrap">
          <span class="input-icon">◈</span>
          <input type="text" id="username" name="username"
                 value="<?= htmlspecialchars($username) ?>"
                 placeholder="admin" autocomplete="username"
                 autofocus required />
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-icon-wrap">
          <span class="input-icon">◉</span>
          <input type="password" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" required />
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-login">Sign In →</button>
    </form>

    <?php endif; ?>
  </div>

  <!-- Demo credentials hint (remove in production) -->
  <div class="demo-creds">
    <strong>🔑 Demo Credentials</strong>
    <hr class="divider">
    <div style="display:flex;flex-direction:column;gap:6px;">
      <div>Admin &nbsp;&nbsp;→ <code>admin</code> / <code>Admin@1234</code></div>
      <div>Teacher → <code>teacher1</code> / <code>Teacher@1234</code></div>
    </div>
  </div>

  <div class="login-footer">
    EduTrack v1.0 &nbsp;·&nbsp; Naga College
  </div>

</div>

</body>
</html>