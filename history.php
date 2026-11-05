<?php
// history.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);
$current_user_id = intval($_SESSION['user_id'] ?? 0);
$current_user_role = $_SESSION['role'] ?? '';

// Check 1: User must be logged in
if ($current_user_id <= 0) {
    header("Location: login.php");
    exit();
}

// Fetch article to verify author ownership
$stmt = $conn->prepare("SELECT a.id, a.title, a.user_id, u.username FROM articles a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    die("Article not found.");
}

// Check 2: STRICT AUTHOR ONLY — Admins and other users are blocked
if ($current_user_id !== intval($article['user_id']) || $current_user_role === 'admin') {
    die("<div style='padding:40px; font-family:sans-serif; text-align:center;'>
            <h3 style='color:#dc3545;'>Access Denied</h3>
            <p>Only the original author who wrote this article has permission to view its revision history.</p>
            <a href='article.php?id=" . $article_id . "' style='color:#0d6efd;'>Return to Article</a>
         </div>");
}

// Fetch all revisions using created_at (matches schema)
$rev_stmt = $conn->prepare("
    SELECT r.id, r.article_id, r.old_title, r.edit_summary, r.created_at, u.username
    FROM article_revisions r
    JOIN users u ON r.edited_by_user_id = u.id
    WHERE r.article_id = ?
    ORDER BY r.created_at DESC
");
$rev_stmt->bind_param("i", $article_id);
$rev_stmt->execute();
$revisions = $rev_stmt->get_result();

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-1">Revision History</h3>
                <p class="text-muted small mb-0">Article: <strong><?= htmlspecialchars($article['title']) ?></strong> (Author-Only Access)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="article.php?id=<?= $article_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Article</a>
                <a href="edit_article.php?id=<?= $article_id ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit Article</a>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-clock-history me-1 text-primary"></i> Saved Historical Revisions (<?= $revisions->num_rows ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Revision ID</th>
                                <th>Timestamp</th>
                                <th>Edited By</th>
                                <th>Edit Summary</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($revisions && $revisions->num_rows > 0): ?>
                                <?php while ($row = $revisions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-secondary">#<?= $row['id'] ?></td>
                                        <td class="text-muted small"><?= date('M d, Y - H:i', strtotime($row['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($row['edit_summary'] ?: 'No summary provided') ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="revision.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View Snapshot
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No prior revisions found. Revisions are automatically saved every time you edit this article.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$rev_stmt->close();
require_once 'includes/footer.php'; 
?>