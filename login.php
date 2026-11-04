<?php
// login.php
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = $user['role'];

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials. Please check your password.";
            }
        } else {
            $error = "No account found with that username or email.";
        }
        $stmt->close();
    }
}

$page_title = 'Sign In';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:480px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,var(--accent) 0%,#6366f1 100%);border-radius:12px;display:grid;place-items:center;color:#fff;font-size:1.35rem;margin:0 auto .85rem;">
                <i class="bi bi-journal-richtext"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Welcome Back</h3>
            <p class="text-muted small">Sign in to your author account to continue writing</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="identifier" class="form-label">Username or Email</label>
                <div style="position:relative;">
                    <i class="bi bi-person" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="text" name="identifier" id="identifier" class="form-control"
                           style="padding-left:2.5rem;"
                           value="<?= htmlspecialchars($identifier ?? '') ?>"
                           placeholder="your_username or email@example.com"
                           required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="forgot_password.php" style="font-size:.8rem;color:var(--accent);text-decoration:none;font-weight:600;">Forgot password?</a>
                </div>
                <div style="position:relative;">
                    <i class="bi bi-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="password" name="password" id="password" class="form-control"
                           style="padding-left:2.5rem;"
                           placeholder="Enter your password"
                           required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <div class="wk-auth-footer mt-4 pt-3 border-top">
            Don't have an account? <a href="register.php">Create one free &rarr;</a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
