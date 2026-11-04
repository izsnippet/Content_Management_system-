<?php
// admin/comments.php
require_once 'includes/admin_header.php';

$message = "";

// Admin Delete Any Comment
if (isset($_GET['delete'])) {
    $del_cid = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $del_cid);
    if ($stmt->execute()) {
        $message = "Comment permanently deleted by Admin.";
    }
    $stmt->close();
}

$comments = $conn->query("
    SELECT c.id, c.comment_text, c.created_at, u.username, a.id AS article_id, a.title AS article_title
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN articles a ON c.article_id = a.id
    ORDER BY c.created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="h3 fw-bold mb-0">Comment Moderation</h2>
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
                        <th class="ps-3">Comment Text</th>
                        <th>Author</th>
                        <th>Article</th>
                        <th>Posted Date</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($comments && $comments->num_rows > 0): ?>
                        <?php while ($comm = $comments->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 text-break" style="max-width: 320px;">
                                    <?= nl2br(htmlspecialchars($comm['comment_text'])) ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($comm['username']) ?></td>
                                <td>
                                    <a href="../article.php?id=<?= $comm['article_id'] ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars(mb_strimwidth($comm['article_title'], 0, 28, '...')) ?>
                                    </a>
                                </td>
                                <td class="text-muted small"><?= date('M d, Y - H:i', strtotime($comm['created_at'])) ?></td>
                                <td class="text-end pe-3">
                                    <a href="comments.php?delete=<?= $comm['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Admin: Permanently delete this comment?');">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No comments have been posted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>