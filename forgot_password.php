<?php
// forgot_password.php
require_once 'config/db.php';

$message = "";
$error   = "";
$reset_link = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            $token = bin2hex(random_bytes(32));
            $up = $conn->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
            $up->bind_param("si", $token, $user['id']);
            $up->execute();
            $up->close();

            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            $message = "A reset token has been generated successfully.";
        } else {
            $error = "No user found with that email address.";
        }
        $stmt->close();
    }
}

$page_title = 'Forgot Password';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:480px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:var(--warning-bg);border-radius:12px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.35rem;color:var(--warning);">
                <i class="bi bi-key"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Reset Password</h3>
            <p class="text-muted small">Enter your email to receive a recovery token</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            </div>
            <div style="background:var(--warning-bg);border:1.5px solid #fde68a;border-radius:var(--radius-sm);padding:1rem 1.1rem;margin-bottom:1.25rem;">
                <div style="font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#92400e;margin-bottom:.4rem;">
                    <i class="bi bi-info-circle me-1"></i> Simulated Link (Development Mode)
                </div>
                <a href="<?= $reset_link ?>" style="font-size:.82rem;color:var(--accent);word-break:break-all;font-weight:600;"><?= $reset_link ?></a>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="forgot_password.php" method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <div style="position:relative;">
                    <i class="bi bi-envelope" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="email" name="email" id="email" class="form-control"
                           style="padding-left:2.5rem;"
                           placeholder="name@example.com"
                           required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                <i class="bi bi-send me-1"></i> Send Reset Link
            </button>
        </form>

        <div class="wk-auth-footer mt-4 pt-3 border-top">
            <a href="login.php" style="color:var(--muted);text-decoration:none;font-weight:500;">
                <i class="bi bi-arrow-left me-1"></i> Back to Sign In
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
