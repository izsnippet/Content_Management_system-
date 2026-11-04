<?php
// admin/index.php
require_once 'includes/admin_header.php';

// Platform Metrics
$total_users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$total_articles = $conn->query("SELECT COUNT(*) AS total FROM articles")->fetch_assoc()['total'] ?? 0;
$total_categories = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'] ?? 0;
$total_comments = $conn->query("SELECT COUNT(*) AS total FROM comments")->fetch_assoc()['total'] ?? 0;

// Recent Articles
$recent_articles = $conn->query("
    SELECT a.id, a.title, a.status, a.created_at, u.username, c.name AS category_name
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    ORDER BY a.created_at DESC
    LIMIT 5
");

// Recent Registered Users
$recent_users = $conn->query("
    SELECT id, username, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="h3 fw-bold mb-0">System Overview</h2>
    <span class="badge bg-primary">Admin Control Active</span>
</div>

<!-- Metric Counters -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Total Users</div>
                <div class="fs-3 fw-bold text-dark"><?= $total_users ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Total Articles</div>
                <div class="fs-3 fw-bold text-dark"><?= $total_articles ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-warning border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Categories</div>
                <div class="fs-3 fw-bold text-dark"><?= $total_categories ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase">Total Comments</div>
                <div class="fs-3 fw-bold text-dark"><?= $total_comments ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Articles Table -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Recent Articles</h6>
                <a href="articles.php" class="btn btn-outline-primary btn-sm">View All</a>
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
                            <?php if ($recent_articles && $recent_articles->num_rows > 0): ?>
                                <?php while ($art = $recent_articles->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <a href="../article.php?id=<?= $art['id'] ?>" target="_blank" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars(mb_strimwidth($art['title'], 0, 30, '...')) ?>
                                            </a>
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
                                <tr><td colspan="4" class="text-center py-3 text-muted">No articles found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Recent Users</h6>
                <a href="users.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Username</th>
                                <th>Role</th>
                                <th class="text-end pe-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                                <?php while ($usr = $recent_users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold">
                                            <?= htmlspecialchars($usr['username']) ?>
                                            <div class="text-muted small"><?= htmlspecialchars($usr['email']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?= $usr['role'] === 'admin' ? 'bg-danger' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($usr['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3 text-muted small"><?= date('M d, Y', strtotime($usr['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>