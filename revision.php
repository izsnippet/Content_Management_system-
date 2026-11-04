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

$page_title = 'Archived Snapshot: ' . $rev['old_title'];
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1100px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- Archived Snapshot Banner -->
    <div class="wk-revision-banner animate-fade-in">
        <div class="banner-info">
            <i class="bi bi-exclamation-triangle-fill banner-icon"></i>
            <div>
                <div class="banner-text">
                    <strong>Archived Snapshot:</strong> You are viewing an older historical version of this article saved on 
                    <strong><?= date('M d, Y · H:i', strtotime($rev['edited_at'])) ?></strong> by 
                    <strong><?= htmlspecialchars($rev['username']) ?></strong>.
                </div>
                <div class="banner-meta">
                    <i class="bi bi-chat-left-quote me-1"></i> Edit Summary recorded: 
                    &ldquo;<?= htmlspecialchars($rev['edit_summary']) ?>&rdquo;
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="history.php?id=<?= $rev['article_id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clock-history me-1"></i> All Revisions
            </a>
            <a href="article.php?id=<?= $rev['article_id'] ?>" class="btn btn-warning btn-sm fw-semibold">
                &larr; Back to Current Version
            </a>
        </div>
    </div>

    <!-- Article Content Card -->
    <div class="wk-card wk-card-static p-4 p-md-5 animate-slide-up">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-warning text-dark border">
                <i class="bi bi-archive-fill me-1"></i> Historical Archive
            </span>
            <span class="text-muted small">
                Saved on <?= date('F d, Y \a\t H:i', strtotime($rev['edited_at'])) ?>
            </span>
        </div>

        <h1 class="display-6 fw-bold mb-3" style="letter-spacing:-.03em;color:var(--primary);">
            <?= htmlspecialchars($rev['old_title']) ?>
        </h1>
        
        <div class="text-muted small pb-3 mb-4 border-bottom d-flex align-items-center gap-2 flex-wrap">
            <span><i class="bi bi-person me-1"></i> Editor: <strong><?= htmlspecialchars($rev['username']) ?></strong></span>
            <span class="text-muted-light">&bull;</span>
            <span><i class="bi bi-tag me-1"></i> Summary: &ldquo;<?= htmlspecialchars($rev['edit_summary']) ?>&rdquo;</span>
        </div>

        <div class="article-body">
            <?= $rev['old_content'] ?>
        </div>

        <div class="mt-5 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="history.php?id=<?= $rev['article_id'] ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Return to Revision History
            </a>
            <a href="article.php?id=<?= $rev['article_id'] ?>" class="btn btn-primary btn-sm">
                View Live Published Version &rarr;
            </a>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
