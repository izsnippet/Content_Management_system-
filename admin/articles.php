<?php
// admin/articles.php
require_once 'includes/admin_header.php';

$message = "";

// Global Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $message = "Article removed by Administrator.";
    }
    $stmt->close();
}

// Global Status Switch
if (isset($_GET['status']) && isset($_GET['id'])) {
    $art_id     = intval($_GET['id']);
    $new_status = in_array($_GET['status'], ['published', 'draft', 'archived']) ? $_GET['status'] : 'draft';
    $stmt = $conn->prepare("UPDATE articles SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $art_id);
    if ($stmt->execute()) {
        $message = "Article status changed to " . htmlspecialchars($new_status) . ".";
    }
    $stmt->close();
}

$articles = $conn->query("
    SELECT a.id, a.title, a.status, a.created_at, a.updated_at, u.username, c.name AS category_name
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    ORDER BY a.updated_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="h3 fw-bold mb-0">Article Moderation</h2>
    <a href="../create_article.php" target="_blank" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Article
    </a>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Article</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Last Modified</th>
                        <th class="text-end pe-3">Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articles && $articles->num_rows > 0): ?>
                        <?php while ($row = $articles->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="../article.php?id=<?= $row['id'] ?>" target="_blank" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['category_name']) ?></span></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle <?= $row['status'] === 'published' ? 'btn-success' : ($row['status'] === 'draft' ? 'btn-warning text-dark' : 'btn-secondary') ?>" type="button" data-bs-toggle="dropdown">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="articles.php?id=<?= $row['id'] ?>&status=published">Publish</a></li>
                                            <li><a class="dropdown-item" href="articles.php?id=<?= $row['id'] ?>&status=draft">Draft</a></li>
                                            <li><a class="dropdown-item" href="articles.php?id=<?= $row['id'] ?>&status=archived">Archive</a></li>
                                        </ul>
                                    </div>
                                </td>
                                <td class="text-muted small"><?= date('M d, Y', strtotime($row['updated_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <a href="../history.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="View Revisions">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <a href="articles.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Admin: Permanently delete this article?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No articles created yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>