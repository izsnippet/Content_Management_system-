<?php
// admin/index.php
require_once 'includes/admin_header.php';

$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$banned_users = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'banned'")->fetch_assoc()['total'] ?? 0;
$total_cats = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'] ?? 0;
$total_comments = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'] ?? 0;

$recent_users = $conn->query("
    SELECT id, username, email, role, status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
");
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <div>
        <h2 class="h3 fw-bold text-dark mb-1">Administrative Overview</h2>
        <p class="text-muted small mb-0">System health, user accounts, and platform administration.</p>
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
                    <i class="bi bi-tags fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Categories</div>
                    <div class="fs-4 fw-bold text-dark"><?= $total_cats ?></div>
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

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h6 class="fw-bold mb-0">Registered User Directory</h6>
                <a href="users.php" class="btn btn-outline-primary btn-sm">Manage Directory</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>Account Status</th>
                                <th>Role</th>
                                <th>Registration Date</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($u = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="user_detail.php?id=<?= $u['id'] ?>" class="fw-semibold text-primary text-decoration-none"><?= htmlspecialchars($u['username']) ?></a>
                                        <div class="text-muted small"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($u['status'] ?? 'active') === 'banned' ? 'bg-danger' : 'bg-success' ?>">
                                            <?= htmlspecialchars($u['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span>
                                    </td>
                                    <td class="text-muted small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    <td class="text-end pe-3">
                                        <a href="user_detail.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i> Audit
                                        </a>
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
