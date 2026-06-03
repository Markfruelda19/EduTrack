<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
requireLogin();

$conn      = getConnection();
$pageTitle = 'Change Password';
$errors    = [];
$success   = '';
$me        = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Fetch current hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $me['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current, $row['password']))  $errors[] = 'Current password is incorrect.';
    if (strlen($new) < 8)                              $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $confirm)                             $errors[] = 'New passwords do not match.';
    if ($current === $new)                             $errors[] = 'New password must be different from current.';

    if (empty($errors)) {
        $hash  = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt2->bind_param('si', $hash, $me['id']);
        $stmt2->execute();
        $success = 'Password changed successfully.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success">✓ <?= $success ?></div><?php endif; ?>
<?php foreach ($errors as $e): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>

<div class="form-card">
  <h2>🔒 Change Password</h2>
  <form method="POST" novalidate>
    <div class="form-group">
      <label>Current Password *</label>
      <input type="password" name="current_password" placeholder="••••••••" required />
    </div>
    <div class="form-group">
      <label>New Password * <span style="color:var(--text-3);font-size:.75rem;text-transform:none;">(min 8 chars)</span></label>
      <input type="password" name="new_password" placeholder="••••••••" required />
    </div>
    <div class="form-group">
      <label>Confirm New Password *</label>
      <input type="password" name="confirm_password" placeholder="••••••••" required />
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Password</button>
      <a href="/edutrack/index.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php $conn->close(); include __DIR__ . '/../includes/footer.php'; ?>