<?php
// register.php
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "Username or Email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'author')");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($insert_stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php' class='alert-link'>log in here</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

$page_title = 'Create Account';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:500px;padding-top:2.5rem;padding-bottom:3.5rem;">
    <div class="wk-auth-card animate-slide-up mx-auto">

        <!-- Logo / Icon -->
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,var(--accent) 0%,#6366f1 100%);border-radius:12px;display:grid;place-items:center;color:#fff;font-size:1.35rem;margin:0 auto .85rem;">
                <i class="bi bi-stars"></i>
            </div>
            <h3 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.03em;">Create Your Account</h3>
            <p class="text-muted small">Join the community of knowledge contributors</p>
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
        <?php endif; ?>

        <!-- Form -->
        <form action="register.php" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div style="position:relative;">
                    <i class="bi bi-at" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="text" name="username" id="username" class="form-control"
                           style="padding-left:2.5rem;"
                           value="<?= htmlspecialchars($username ?? '') ?>"
                           placeholder="Choose a username"
                           required>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div style="position:relative;">
                    <i class="bi bi-envelope" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="email" name="email" id="email" class="form-control"
                           style="padding-left:2.5rem;"
                           value="<?= htmlspecialchars($email ?? '') ?>"
                           placeholder="name@example.com"
                           required>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div style="position:relative;">
                    <i class="bi bi-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="password" name="password" id="password" class="form-control"
                           style="padding-left:2.5rem;"
                           placeholder="At least 6 characters"
                           required minlength="6">
                </div>
                <div class="form-text">Must be at least 6 characters.</div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div style="position:relative;">
                    <i class="bi bi-shield-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.95rem;pointer-events:none;"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                           style="padding-left:2.5rem;"
                           placeholder="Repeat your password"
                           required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;">
                <i class="bi bi-person-plus me-1"></i> Register Free
            </button>
        </form>

        <div class="wk-auth-footer mt-4 pt-3 border-top">
            Already have an account? <a href="login.php">Sign in &rarr;</a>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
