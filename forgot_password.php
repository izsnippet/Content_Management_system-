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

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h3 class="card-title fw-bold text-center mb-3">Forgot Password</h3>
                <p class="text-muted small text-center mb-4">Enter your registered email address to receive your password reset token.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($message) ?></div>
                    <div class="card bg-light border p-3 mb-4">
                        <span class="small fw-bold text-secondary mb-1">Simulated Email Reset Link (Development Mode):</span>
                        <a href="<?= $reset_link ?>" class="small text-break fw-semibold text-primary"><?= $reset_link ?></a>
                    </div>
                <?php endif; ?>

                <form action="forgot_password.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="name@example.com">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Send Reset Link</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none small text-muted">&larr; Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>