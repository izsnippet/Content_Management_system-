<?php
// admin/articles.php
require_once 'includes/admin_header.php';

$message = "";
$error = "";
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

// Restore the latest saved title/content snapshot. Save the current state first so rollback is reversible.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rollback_article'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = 'Invalid security token. Reload and try again.';
    } else {
        $art_id = intval($_POST['article_id'] ?? 0);
        $conn->begin_transaction();
        try {
            $cur = $conn->prepare('SELECT title, content FROM articles WHERE id = ? FOR UPDATE');
            $cur->bind_param('i', $art_id); $cur->execute(); $current = $cur->get_result()->fetch_assoc(); $cur->close();
            $rev = $conn->prepare('SELECT old_title, old_content FROM article_revisions WHERE article_id = ? ORDER BY edited_at DESC, id DESC LIMIT 1');
            $rev->bind_param('i', $art_id); $rev->execute(); $snapshot = $rev->get_result()->fetch_assoc(); $rev->close();
            if (!$current || !$snapshot) throw new RuntimeException('No saved revision is available to restore.');
            $admin_id = (int) $_SESSION['user_id'];
            $summary = 'Pre-rollback snapshot saved by administrator';
            $save = $conn->prepare('INSERT INTO article_revisions (article_id, edited_by_user_id, old_title, old_content, edit_summary) VALUES (?, ?, ?, ?, ?)');
            $save->bind_param('iisss', $art_id, $admin_id, $current['title'], $current['content'], $summary);
            if (!$save->execute()) throw new RuntimeException('Could not preserve current article revision.');
            $save->close();
            $update = $conn->prepare('UPDATE articles SET title = ?, content = ? WHERE id = ?');
            $update->bind_param('ssi', $snapshot['old_title'], $snapshot['old_content'], $art_id);
            if (!$update->execute()) throw new RuntimeException('Could not restore the article.');
            $update->close();
            $conn->commit();
            $message = 'Article rolled back to its latest saved revision.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Admin Delete Action
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $message = "Article removed by administrative action.";
    }
    $stmt->close();
}

// Admin Status Override
if (isset($_GET['status']) && isset($_GET['id'])) {
    $art_id     = intval($_GET['id']);
    $new_status = in_array($_GET['status'], ['published', 'draft', 'archived']) ? $_GET['status'] : 'draft';
    $stmt = $conn->prepare("UPDATE articles SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $art_id);
    if ($stmt->execute()) {
        $message = "Status updated to " . htmlspecialchars($new_status) . ".";
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
    <div>
        <h2 class="h3 fw-bold mb-1">Global Content Moderation</h2>
        <p class="text-muted small mb-0">Oversee user articles, change visibility status, or delete offensive posts.</p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Article Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Last Modified</th>
                        <th class="text-end pe-3">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articles && $articles->num_rows > 0): ?>
                        <?php while ($row = $articles->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">
                                    <?= htmlspecialchars($row['title']) ?>
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
                                    <a href="revisions.php?article_id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="View Version Snapshots">
                                        <i class="bi bi-clock-history"></i> History
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Restore the latest saved revision for this article?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="article_id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" name="rollback_article" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-arrow-counterclockwise"></i> Rollback</button>
                                    </form>
                                    <a href="articles.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Administrative Action: Permanently delete this article?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No content found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
