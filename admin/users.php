<?php
// admin/users.php
require_once 'includes/admin_header.php';

$message = "";
$error = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = "Invalid security token. Please reload the page and try again.";
    } else {
        $target_uid = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);

        if ($target_uid === false || $target_uid === null || $target_uid < 1) {
            $error = "Invalid user account.";
        } elseif (isset($_POST['toggle_ban'])) {
            if ($target_uid === (int) $_SESSION['user_id']) {
                $error = "Security Policy: You cannot ban your own administrator account.";
            } else {
                $cur_stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
                $cur_stmt->bind_param("i", $target_uid);
                $cur_stmt->execute();
                $curr = $cur_stmt->get_result()->fetch_assoc();
                $cur_stmt->close();

                if (!$curr) {
                    $error = "User account not found.";
                } else {
                    $new_status = ($curr['status'] ?? 'active') === 'banned' ? 'active' : 'banned';
                    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_status, $target_uid);
                    $stmt->execute();
                    $stmt->close();
                    $message = "User status switched to " . strtoupper($new_status) . ".";
                }
            }
        } elseif (isset($_POST['delete_user'])) {
            if ($target_uid === (int) $_SESSION['user_id']) {
                $error = "Security Policy: You cannot delete your own administrator account.";
            } else {
                $del = $conn->prepare("DELETE FROM users WHERE id = ?");
                $del->bind_param("i", $target_uid);
                $del->execute();
                if ($del->affected_rows > 0) {
                    $message = "Account and all associated records permanently purged.";
                } else {
                    $error = "User account not found.";
                }
                $del->close();
            }
        } elseif (isset($_POST['update_role'])) {
            $new_role = in_array($_POST['role'] ?? '', ['author', 'admin'], true) ? $_POST['role'] : 'author';

            if ($target_uid === (int) $_SESSION['user_id'] && $new_role !== 'admin') {
                $error = "You cannot remove admin privileges from your own active account.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $target_uid);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $message = "User role updated successfully.";
                } else {
                    $error = "User account was not found or the role is unchanged.";
                }
                $stmt->close();
            }
        }
    }
}

$users = $conn->query("
    SELECT u.id, u.username, u.email, u.role, u.status, u.created_at, COUNT(a.id) AS article_count
    FROM users u
    LEFT JOIN articles a ON u.id = a.user_id
    GROUP BY u.id, u.username, u.email, u.role, u.status, u.created_at
    ORDER BY u.created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">User Directory & Access Control</h2>
        <p class="text-muted small mb-0">Suspend malicious accounts or permanently remove users.</p>
    </div>
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
                        <th class="ps-3">User Details</th>
                        <th>Account Status</th>
                        <th>Role</th>
                        <th>Articles Authored</th>
                        <th>Registered Date</th>
                        <th class="text-end pe-3">Control Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <?php $is_self = (int) $u['id'] === (int) $_SESSION['user_id']; ?>
                        <?php $status = $u['status'] ?? 'active'; ?>
                        <tr>
                            <td class="ps-3">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($u['username']) ?></span>
                                <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                            </td>
                            <td>
                                <?php if ($status === 'banned'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i> Banned / Suspended</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="users.php" method="POST" class="d-inline-flex align-items-center gap-1">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <select name="role" class="form-select form-select-sm w-auto" aria-label="Role for <?= htmlspecialchars($u['username']) ?>">
                                        <option value="author" <?= $u['role'] === 'author' ? 'selected' : '' ?>>Author</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-outline-secondary btn-sm" title="Save Role">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= (int) $u['article_count'] ?></span></td>
                            <td class="text-muted small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-3">
                                <?php if (!$is_self): ?>
                                    <form action="users.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" name="toggle_ban" class="btn btn-sm <?= $status === 'banned' ? 'btn-outline-success' : 'btn-outline-warning text-dark' ?>" title="<?= $status === 'banned' ? 'Restore Account' : 'Suspend Account' ?>" <?= $status === 'banned' ? '' : 'onclick="return confirm(\'Suspend this user account? They will be locked out of logging in.\');"' ?>>
                                            <i class="bi <?= $status === 'banned' ? 'bi-arrow-counterclockwise' : 'bi-slash-circle' ?>"></i>
                                            <?= $status === 'banned' ? 'Unban' : 'Ban Account' ?>
                                        </button>
                                    </form>
                                    <form action="users.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently purge this user account? This cannot be undone.');" title="Purge Account">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-dark">Root Account</span>
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
