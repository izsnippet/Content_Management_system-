<?php
// admin/index.php
require_once 'includes/admin_header.php';

$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$banned_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'banned'")->fetch_assoc()['total'] ?? 0;
$total_articles = $conn->query("SELECT COUNT(*) AS total FROM articles")->fetch_assoc()['total'] ?? 0;
$total_comments = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'] ?? 0;

$recent_users = $conn->query("
    SELECT id, username, email, role, status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 6
");

$flagged_articles = $conn->query("
    SELECT a.id, a.title, a.status, a.created_at, u.username, c.name AS category_name
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    ORDER BY a.updated_at DESC
    LIMIT 6
");
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Administrative Overview</h2>
        <p class="text-muted small mb-0">System health, platform monitoring, and moderation queue.</p>
    </div>
    <span class="badge bg-primary px-3 py-2"><i class="bi bi-shield-lock-fill me-1"></i> Root Administrator</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Accounts</div>
                    <div class="fs-4 fw-bold text-dark"><?= $total_users ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 me-3">
                    <i class="bi bi-slash-circle fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Banned Accounts</div>
                    <div class="fs-4 fw-bold text-dark"><?= $banned_users ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                    <i class="bi bi-journals fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Articles</div>
                    <div class="fs-4 fw-bold text-dark"><?= $total_articles ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 p-3">
            <div class="d-flex align-items-center">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 me-3">
                    <i class="bi bi-chat-left-quote fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Comments</div>
                    <div class="fs-4 fw-bold text-dark"><?= $total_comments ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0">Recent User Content</h6>
                <a href="articles.php" class="btn btn-outline-primary btn-sm">Moderation Center</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($flagged_articles && $flagged_articles->num_rows > 0): ?>
                                <?php while ($art = $flagged_articles->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <?= htmlspecialchars(mb_strimwidth($art['title'], 0, 32, '...')) ?>
                                        </td>
                                        <td><?= htmlspecialchars($art['username']) ?></td>
                                        <td>
                                            <span class="badge <?= $art['status'] === 'published' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= htmlspecialchars($art['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 text-muted small"><?= date('M d, Y', strtotime($art['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No content recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0">Registered Users</h6>
                <a href="users.php" class="btn btn-outline-primary btn-sm">Manage Accounts</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($u['username']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($u['status'] ?? 'active') === 'banned' ? 'bg-danger' : 'bg-success' ?>">
                                            <?= htmlspecialchars($u['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
