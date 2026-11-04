<?php
// reset_password.php
require_once 'config/db.php';

$token   = trim($_GET['token'] ?? '');
$error   = "";
$success = "";

if (empty($token)) {
    die("Invalid or missing password reset token.");
}

// Verify token
$stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("This password reset token is invalid or has expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h3 class="card-title fw-bold text-center mb-3">Reset Password</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success py-2"><?= $success ?></div>
                <?php else: ?>
                    <form action="reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="6">
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Update Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>