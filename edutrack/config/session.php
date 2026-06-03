<?php
// config/session.php
// Include this at the very top of every protected page BEFORE any output.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // expires when browser closes
        'path'     => '/',
        'secure'   => false,       // set true on HTTPS
        'httponly' => true,        // no JS access to cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

/**
 * requireLogin()
 * Call this on any page that needs authentication.
 * Redirects to login page if not logged in.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header("Location: /edutrack/auth/login.php?redirect={$redirect}");
        exit;
    }
}

/**
 * requireAdmin()
 * Call this on admin-only pages.
 */
function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: /edutrack/index.php?error=unauthorized');
        exit;
    }
}

/**
 * currentUser()
 * Returns array with id, username, full_name, role.
 */
function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']        ?? null,
        'username'  => $_SESSION['user_username']  ?? '',
        'full_name' => $_SESSION['user_fullname']  ?? '',
        'role'      => $_SESSION['user_role']      ?? '',
    ];
}

/**
 * isAdmin()
 */
function isAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'admin';
}