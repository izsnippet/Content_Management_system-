<?php
// revision.php
require_once 'config/db.php';

$rev_id = intval($_GET['rev_id'] ?? 0);

$query = "
    SELECT r.*, u.username 
    FROM article_revisions r
    JOIN users u ON r.edited_by_user_id = u.id
    WHERE r.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $rev_id);
$stmt->execute();
$rev = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rev) {
    die("Historical snapshot not found.");
}

require_once 'includes/header.php';
?>

<div class="alert alert-warning border shadow-sm mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Archived Snapshot:</strong> You are viewing an older version of this article saved on 
            <strong><?= date('M d, Y - H:i', strtotime($rev['edited_at'])) ?></strong> by 
            <strong><?= htmlspecialchars($rev['username']) ?></strong>.
        </div>
        <a href="article.php?id=<?= $rev['article_id'] ?>" class="btn btn-warning btn-sm fw-semibold">
            &larr; Back to Current Version
        </a>
    </div>
    <div class="small mt-1 text-muted">
        Edit Summary recorded: <em>"<?= htmlspecialchars($rev['edit_summary']) ?>"</em>
    </div>
</div>

<div class="card shadow-sm border-0 p-4">
    <h1 class="display-5 fw-bold"><?= htmlspecialchars($rev['old_title']) ?></h1>
    <hr>
    <div class="article-body fs-5 lh-lg">
        <?= $rev['old_content'] ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>