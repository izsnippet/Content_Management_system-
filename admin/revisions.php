<?php
// admin/revisions.php
require_once 'includes/admin_header.php';
$error = '';
$message = '';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rollback_revision'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $error = 'Invalid security token. Reload and try again.';
    } else {
        $revision_id = intval($_POST['revision_id'] ?? 0);
        try {
            $conn->begin_transaction();
            $rev_stmt = $conn->prepare('SELECT article_id, old_title, old_content FROM article_revisions WHERE id = ?');
            $rev_stmt->bind_param('i', $revision_id); $rev_stmt->execute(); $revision = $rev_stmt->get_result()->fetch_assoc(); $rev_stmt->close();
            if (!$revision) throw new RuntimeException('Revision not found.');
            $article_id = (int)$revision['article_id'];
            $article_stmt = $conn->prepare('SELECT title, content FROM articles WHERE id = ? FOR UPDATE');
            $article_stmt->bind_param('i', $article_id); $article_stmt->execute(); $current = $article_stmt->get_result()->fetch_assoc(); $article_stmt->close();
            if (!$current) throw new RuntimeException('The article no longer exists.');
            $admin_id = (int)$_SESSION['user_id'];
            $summary = 'Pre-rollback snapshot saved by administrator';
            $save = $conn->prepare('INSERT INTO article_revisions (article_id, edited_by_user_id, old_title, old_content, edit_summary) VALUES (?, ?, ?, ?, ?)');
            $save->bind_param('iisss', $article_id, $admin_id, $current['title'], $current['content'], $summary);
            if (!$save->execute()) throw new RuntimeException('Could not preserve the current article version.');
            $save->close();
            $update = $conn->prepare('UPDATE articles SET title = ?, content = ? WHERE id = ?');
            $update->bind_param('ssi', $revision['old_title'], $revision['old_content'], $article_id);
            if (!$update->execute()) throw new RuntimeException('Could not restore the selected revision.');
            $update->close();
            $conn->commit();
            $message = 'Article restored to the selected snapshot.';
        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
$article_filter = intval($_GET['article_id'] ?? 0);
$sql = "SELECT r.id, r.article_id, r.old_title, r.edit_summary, r.edited_at, u.username, a.title AS current_title
        FROM article_revisions r JOIN users u ON u.id = r.edited_by_user_id
        JOIN articles a ON a.id = r.article_id";
if ($article_filter > 0) {
    $stmt = $conn->prepare($sql . ' WHERE r.article_id = ? ORDER BY r.edited_at DESC, r.id DESC');
    $stmt->bind_param('i', $article_filter); $stmt->execute(); $revisions = $stmt->get_result();
} else {
    $revisions = $conn->query($sql . ' ORDER BY r.edited_at DESC, r.id DESC LIMIT 500');
}
?>
<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom"><div><h2 class="h3 fw-bold mb-1">Revision Rollback</h2><p class="text-muted small mb-0">Review historical snapshots and restore an article in one action.</p></div><a class="btn btn-outline-secondary btn-sm" href="articles.php">Content Moderation</a></div>
<?php if ($message): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Article</th><th>Snapshot</th><th>Summary</th><th>Saved</th><th>Editor</th><th class="text-end pe-3">Action</th></tr></thead><tbody>
<?php if ($revisions && $revisions->num_rows): while ($rev = $revisions->fetch_assoc()): ?><tr><td class="ps-3"><a href="revisions.php?article_id=<?= (int)$rev['article_id'] ?>"><?= htmlspecialchars($rev['current_title']) ?></a> <span class="text-muted small">#<?= (int)$rev['article_id'] ?></span></td><td><?= htmlspecialchars($rev['old_title']) ?></td><td><?= htmlspecialchars($rev['edit_summary']) ?></td><td class="text-muted small"><?= htmlspecialchars(date('M d, Y H:i', strtotime($rev['edited_at']))) ?></td><td><?= htmlspecialchars($rev['username']) ?></td><td class="text-end pe-3"><form method="POST" class="d-inline" onsubmit="return confirm('Restore this snapshot? The current version will be saved in history first.');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>"><input type="hidden" name="revision_id" value="<?= (int)$rev['id'] ?>"><button type="submit" name="rollback_revision" class="btn btn-sm btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Rollback</button></form></td></tr><?php endwhile; else: ?><tr><td colspan="6" class="text-center py-4 text-muted">No revisions found.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if (isset($stmt)) $stmt->close(); require_once 'includes/admin_footer.php'; ?>
