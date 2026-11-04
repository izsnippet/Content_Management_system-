<?php
// history.php
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);

// Get current article info
$stmt = $conn->prepare("SELECT title FROM articles WHERE id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    die("Article not found.");
}

// Fetch historical edits
$rev_query = "
    SELECT r.id, r.edit_summary, r.edited_at, u.username 
    FROM article_revisions r
    JOIN users u ON r.edited_by_user_id = u.id
    WHERE r.article_id = ?
    ORDER BY r.edited_at DESC
";
$rev_stmt = $conn->prepare($rev_query);
$rev_stmt->bind_param("i", $article_id);
$rev_stmt->execute();
$revisions = $rev_stmt->get_result();

$page_title = 'History: ' . $article['title'];
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1300px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- Article Tabs -->
    <div class="wk-article-tabs">
        <a href="article.php?id=<?= $article_id ?>">
            <i class="bi bi-book"></i> Read
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="edit_article.php?id=<?= $article_id ?>">
                <i class="bi bi-pencil"></i> Edit Source
            </a>
        <?php endif; ?>
        <a href="history.php?id=<?= $article_id ?>" class="active">
            <i class="bi bi-clock-history"></i> View History
        </a>
    </div>

    <!-- Page Header -->
    <div class="d-flex align-items-flex-start justify-content-between mb-3 flex-wrap gap-2 animate-fade-in">
        <div>
            <h2 style="font-size:1.3rem;font-weight:800;letter-spacing:-.02em;margin-bottom:.25rem;">
                <i class="bi bi-clock-history text-accent me-2"></i>Revision History
            </h2>
            <p style="font-size:.88rem;color:var(--muted);margin:0;">
                <?= htmlspecialchars($article['title']) ?> &mdash; <?= $revisions->num_rows ?> revision<?= $revisions->num_rows !== 1 ? 's' : '' ?> recorded
            </p>
        </div>
        <a href="article.php?id=<?= $article_id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Article
        </a>
    </div>

    <!-- Revision Timeline -->
    <?php if ($revisions->num_rows > 0): ?>
        <div class="wk-history-timeline">
            <?php while ($rev = $revisions->fetch_assoc()): ?>
                <div class="wk-revision-item reveal">
                    <div class="wk-revision-info">
                        <div class="wk-revision-date">
                            <i class="bi bi-clock"></i>
                            <?= date('M d, Y · H:i', strtotime($rev['edited_at'])) ?>
                        </div>
                        <div class="wk-revision-contributor">
                            <i class="bi bi-person-fill" style="color:var(--accent);font-size:.85rem;"></i>
                            <?= htmlspecialchars($rev['username']) ?>
                        </div>
                        <div class="wk-revision-summary">
                            <span class="summary-label">Edit summary:</span>
                            <span class="summary-text"><?= htmlspecialchars($rev['edit_summary']) ?></span>
                        </div>
                    </div>
                    <a href="revision.php?rev_id=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-primary" style="white-space:nowrap;flex-shrink:0;">
                        <i class="bi bi-eye me-1"></i> View Snapshot
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="wk-card wk-card-static">
            <div class="wk-empty">
                <i class="bi bi-clock-history"></i>
                <h5>No revision history</h5>
                <p>No past revisions found for this article. You are currently viewing the original version.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php 
$rev_stmt->close();
require_once 'includes/footer.php'; 
?>
