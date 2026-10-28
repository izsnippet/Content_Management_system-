<?php
// profile.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$message = "";
$error   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');

    $up_stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $up_stmt->bind_param("si", $bio, $user_id);
    if ($up_stmt->execute()) {
        $message = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile.";
    }
    $up_stmt->close();
}

$stmt = $conn->prepare("SELECT username, email, bio, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 my-3">
            <div class="card-header bg-white py-3">
                <h4 class="fw-bold mb-0">Author Profile</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success py-2"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Role</label>
                        <input type="text" class="form-control" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label fw-semibold">Biography</label>
                        <textarea name="bio" id="bio" rows="4" class="form-control" placeholder="Write a short bio about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <span class="text-muted small">Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>