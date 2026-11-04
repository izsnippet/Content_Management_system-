<?php
// reset_password.php
require_once 'config/db.php';

$token   = trim($_GET['token'] ?? '');
$error   = "";
$success = "";
$user    = null;

if (empty($token)) {
    $error = "Invalid or missing password reset token.";
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = "This password reset token is invalid or has expired.";
    }
}

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL WHERE id = ?");
        $up->bind_param("si", $new_hash, $user['id']);

        if ($up->execute()) {
            $success = "Your password has been updated! You can now <a href='login.php' class='alert-link'>sign in</a>.";
        } else {
            $error = "Failed to update password. Try again.";
        }
        $up->close();
    }
}

$page_title = 'Set New Password';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:480px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:var(--accent-light);border-radius:12px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.35rem;color:var(--accent);">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Set New Password</h3>
            <p class="text-muted small">Create a secure password for your account</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Go to Sign In
                </a>
            </div>
        <?php elseif ($user): ?>
            <!-- Form -->
            <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div style="position:relative;">
                        <i class="bi bi-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                        <input type="password" name="password" id="password" class="form-control"
                               style="padding-left:2.5rem;"
                               placeholder="Minimum 6 characters"
                               required minlength="6">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div style="position:relative;">
                        <i class="bi bi-shield-check" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                               style="padding-left:2.5rem;"
                               placeholder="Re-enter your new password"
                               required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                    <i class="bi bi-check2-circle me-1"></i> Update Password
                </button>
            </form>
        <?php else: ?>
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="btn btn-outline-primary btn-sm">
                    Request a New Reset Link
                </a>
            </div>
        <?php endif; ?>

        <div class="wk-auth-footer mt-4 pt-3 border-top">
            <a href="login.php" style="color:var(--muted);text-decoration:none;font-weight:500;">
                <i class="bi bi-arrow-left me-1"></i> Back to Sign In
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
