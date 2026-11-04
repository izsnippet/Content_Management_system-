<?php
// forgot_password.php
require_once 'config/db.php';

$message    = "";
$error      = "";
$reset_link = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            // Generate a secure 32-byte random hex token
            $token = bin2hex(random_bytes(32));
            // Set expiry to 1 hour from current time (server-aligned timestamp)
            $exp_res = $conn->query("SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) AS exp")->fetch_assoc();
            $expires_at = $exp_res['exp'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));

            $up = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $up->bind_param("ssi", $token, $expires_at, $user['id']);
            
            if ($up->execute()) {
                $reset_link = "reset_password.php?token=" . urlencode($token);
                $message = "A password reset link has been successfully generated for <strong>" . htmlspecialchars($user['username']) . "</strong>.";
            } else {
                $error = "Failed to create reset token. Please try again.";
            }
            $up->close();
        } else {
            $error = "No registered account found with that email address.";
        }
        $stmt->close();
    }
}

$page_title = 'Forgot Password';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:500px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:var(--warning-bg);border-radius:12px;display:grid;place-items:center;margin:0 auto .85rem;font-size:1.35rem;color:var(--warning);">
                <i class="bi bi-key"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Forgot Password</h3>
            <p class="text-muted small">Enter your email address to generate a secure password reset link</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Success & Development Mode Link -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $message ?>
            </div>
            <div class="alert alert-info border shadow-sm p-3 mb-4" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-terminal-fill me-2 text-info"></i>
                    <strong class="text-dark small text-uppercase">Development Reset Link (Expires in 1 hour):</strong>
                </div>
                <div class="bg-white p-2 rounded border small text-break">
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="text-primary fw-semibold text-decoration-underline">
                        <?= htmlspecialchars($reset_link) ?>
                    </a>
                </div>
                <div class="mt-2 text-muted" style="font-size: .8rem;">
                    Click the link above to proceed directly to the password reset page.
                </div>
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
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                <i class="bi bi-send me-1"></i> Generate Reset Link
            </button>
        </form>

        <div class="wk-auth-footer mt-4 pt-3 border-top text-center">
            <a href="login.php" style="color:var(--muted);text-decoration:none;font-weight:500;">
                <i class="bi bi-arrow-left me-1"></i> Back to Sign In
            </a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
