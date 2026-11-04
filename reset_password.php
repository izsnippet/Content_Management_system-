<?php
// reset_password.php
require_once 'config/db.php';

$token     = trim($_GET['token'] ?? '');
$error     = "";
$success   = "";
$user      = null;
$token_err = "";

if (empty($token)) {
    $token_err = "No password reset token was provided. Please check the link or request a new one.";
} else {
    // Verify token exists and has not expired
    $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE reset_token = ? AND reset_expires_at > NOW() LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $token_err = "This password reset link is invalid or has expired. Password reset links are valid for 1 hour.";
    }
}

// Handle password update POST
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please verify your entries.";
    } else {
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        $up->bind_param("si", $new_hash, $user['id']);

        if ($up->execute()) {
            $success = "Your password has been successfully updated! You can now <a href='login.php' class='alert-link fw-bold'>sign in</a> with your new credentials.";
            $user = null; // Hide the form on success
        } else {
            $error = "Failed to update your password. Please try again.";
        }
        $up->close();
    }
}

$page_title = 'Set New Password';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:500px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:var(--accent-light);border-radius:12px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.35rem;color:var(--accent);">
                <i class="bi bi-shield-lock"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Set New Password</h3>
            <p class="text-muted small">Choose a strong, secure password for your account</p>
        </div>

        <!-- Token Invalid or Expired Alert -->
        <?php if (!empty($token_err)): ?>
            <div class="alert alert-danger" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <strong>Reset Link Expired or Invalid</strong>
                </div>
                <p class="small mb-3"><?= htmlspecialchars($token_err) ?></p>
                <a href="forgot_password.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Request a New Reset Link
                </a>
            </div>
        <?php endif; ?>

        <!-- Form Validation Error -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success p-3 text-center" role="alert">
                <i class="bi bi-check-circle-fill fs-3 text-success d-block mb-2"></i>
                <div><?= $success ?></div>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Sign In
                </a>
            </div>
        <?php elseif ($user): ?>
            <!-- Password Reset Form -->
            <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div style="position:relative;">
                        <i class="bi bi-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                        <input type="password" name="password" id="password" class="form-control"
                               style="padding-left:2.5rem;"
                               placeholder="Minimum 6 characters"
                               required minlength="6" autofocus>
                    </div>
                    <div class="form-text">Must be at least 6 characters long.</div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div style="position:relative;">
                        <i class="bi bi-shield-check" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                               style="padding-left:2.5rem;"
                               placeholder="Repeat your new password"
                               required minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                    <i class="bi bi-check2-circle me-1"></i> Update Password
                </button>
            </form>
        <?php endif; ?>

        <div class="wk-auth-footer mt-4 pt-3 border-top text-center">
            <a href="login.php" style="color:var(--muted);text-decoration:none;font-weight:500;">
                <i class="bi bi-arrow-left me-1"></i> Back to Sign In
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
