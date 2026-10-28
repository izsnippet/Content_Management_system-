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

require_once 'includes/header.php';
?>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link" href="article.php?id=<?= $article_id ?>"><i class="bi bi-book"></i> Read</a>
    </li>
    <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="edit_article.php?id=<?= $article_id ?>"><i class="bi bi-pencil"></i> Edit Source</a>
        </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link active fw-bold" href="history.php?id=<?= $article_id ?>"><i class="bi bi-clock-history"></i> View History</a>
    </li>
</ul>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h4 class="fw-bold mb-0">Revision History: <?= htmlspecialchars($article['title']) ?></h4>
    </div>
    <div class="card-body p-0">
        <?php if ($revisions->num_rows > 0): ?>
            <div class="list-group list-group-flush">
                <?php while ($rev = $revisions->fetch_assoc()): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div>
                            <span class="text-muted small me-2"><i class="bi bi-clock"></i> <?= date('M d, Y - H:i', strtotime($rev['edited_at'])) ?></span>
                            <span class="fw-semibold text-dark me-2">&bull; Contributor: <?= htmlspecialchars($rev['username']) ?></span>
                            <span class="badge bg-light text-dark border">Summary: "<?= htmlspecialchars($rev['edit_summary']) ?>"</span>
                        </div>
                        <a href="revision.php?rev_id=<?= $rev['id'] ?>" class="btn btn-sm btn-outline-primary">
                            View Snapshot
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                No past revisions found for this article. You are currently viewing the original version.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$rev_stmt->close();
require_once 'includes/footer.php'; 
?>