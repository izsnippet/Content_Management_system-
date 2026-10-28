<?php
// dashboard.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Published count
$pub_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'published'");
$pub_stmt->bind_param("i", $user_id);
$pub_stmt->execute();
$pub_stmt->bind_result($total_published);
$pub_stmt->fetch();
$pub_stmt->close();

// Drafts count
$draft_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'draft'");
$draft_stmt->bind_param("i", $user_id);
$draft_stmt->execute();
$draft_stmt->bind_result($total_drafts);
$draft_stmt->fetch();
$draft_stmt->close();

// Revisions count
$rev_stmt = $conn->prepare("SELECT COUNT(*) FROM article_revisions WHERE edited_by_user_id = ?");
$rev_stmt->bind_param("i", $user_id);
$rev_stmt->execute();
$rev_stmt->bind_result($total_edits);
$rev_stmt->fetch();
$rev_stmt->close();

// Fetch articles list
$query = "
    SELECT a.id, a.title, a.status, a.created_at, a.updated_at, c.name AS category_name 
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.updated_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$articles = $stmt->get_result();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1">Author Dashboard</h2>
        <p class="text-muted mb-0">Welcome back, <strong><?= htmlspecialchars($username) ?></strong>!</p>
    </div>
    <a href="create_article.php" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i> Write New Article
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Published</div>
                        <div class="fs-3 fw-bold text-dark"><?= $total_published ?></div>
                    </div>
                    <i class="bi bi-check-circle fs-1 text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Drafts</div>
                        <div class="fs-3 fw-bold text-dark"><?= $total_drafts ?></div>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 text-warning opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Revisions Contributed</div>
                        <div class="fs-3 fw-bold text-dark"><?= $total_edits ?></div>
                    </div>
                    <i class="bi bi-clock-history fs-1 text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="card-title fw-bold mb-0">Your Articles</h5>
    </div>
    <div class="card-body p-0">
        <?php if ($articles->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $articles->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="article.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_name']) ?></span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php elseif ($row['status'] === 'draft'): ?>
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Archived</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('M d, Y', strtotime($row['updated_at'])) ?>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="edit_article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="delete_article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this article?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-plus display-3 text-muted"></i>
                <h5 class="fw-bold mt-3">No articles written yet</h5>
                <p class="text-muted small">Start by creating your first article or saving a draft.</p>
                <a href="create_article.php" class="btn btn-outline-primary btn-sm">Create Article</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>