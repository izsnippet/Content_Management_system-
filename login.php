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

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle display-4 text-primary"></i>
                    <h3 class="card-title fw-bold mt-2">Sign In</h3>
                    <p class="text-muted small">Access your author account and articles</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Username or Email</label>
                        <input type="text" name="identifier" id="identifier" class="form-control" value="<?= htmlspecialchars($identifier ?? '') ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-2">Log In</button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none">Sign up</a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>