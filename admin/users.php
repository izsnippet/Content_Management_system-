<?php
// admin/users.php
require_once 'includes/admin_header.php';

$message = "";
$error   = "";

// Change Role Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $target_uid = intval($_POST['user_id']);
    $new_role   = in_array($_POST['role'], ['author', 'admin']) ? $_POST['role'] : 'author';

    // Guard against demoting self
    if ($target_uid === $_SESSION['user_id'] && $new_role !== 'admin') {
        $error = "You cannot remove admin privileges from your own active account.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $target_uid);
        if ($stmt->execute()) {
            $message = "User role updated successfully.";
        }
        $stmt->close();
    }
}

// Delete User Action
if (isset($_GET['delete'])) {
    $target_uid = intval($_GET['delete']);
    if ($target_uid === $_SESSION['user_id']) {
        $error = "You cannot delete your own active administrator account.";
    } else {
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param("i", $target_uid);
        if ($del->execute()) {
            $message = "User deleted successfully.";
        }
        $del->close();
    }
}

$users = $conn->query("
    SELECT u.id, u.username, u.email, u.role, u.created_at, COUNT(a.id) AS article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.user_id 
    GROUP BY u.id, u.username, u.email, u.role, u.created_at 
    ORDER BY u.created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="h3 fw-bold mb-0">User Management</h2>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">User</th>
                        <th>Role</th>
                        <th>Articles Authored</th>
                        <th>Joined Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['username']) ?></span>
                                <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td>
                                <form action="users.php" method="POST" class="d-inline-flex align-items-center gap-1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm w-auto">
                                        <option value="author" <?= $u['role'] === 'author' ? 'selected' : '' ?>>Author</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-outline-secondary btn-sm" title="Save Role">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $u['article_count'] ?></span></td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-3">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this user and all associated content?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Current Account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>