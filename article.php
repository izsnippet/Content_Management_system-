<?php
// article.php
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);

$query = "
    SELECT a.*, u.username, c.name AS category_name 
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.id = ? AND a.status = 'published'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    die("Article not found or not published.");
}

// Comment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    $comment_text = trim($_POST['comment_text'] ?? '');
    if (!empty($comment_text)) {
        $c_stmt = $conn->prepare("INSERT INTO comments (article_id, user_id, comment_text) VALUES (?, ?, ?)");
        $c_stmt->bind_param("iis", $article_id, $_SESSION['user_id'], $comment_text);
        $c_stmt->execute();
        $c_stmt->close();
        header("Location: article.php?id=" . $article_id);
        exit();
    }
}

// Fetch Comments
$c_query = "
    SELECT c.comment_text, c.created_at, u.username 
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ?
    ORDER BY c.created_at DESC
";
$c_stmt = $conn->prepare($c_query);
$c_stmt->bind_param("i", $article_id);
$c_stmt->execute();
$comments = $c_stmt->get_result();

// Wikipedia [[Keyword]] auto-linker
$rendered_content = preg_replace('/\[\[(.*?)\]\]/', '<a href="search.php?q=$1" class="text-primary text-decoration-underline">$1</a>', $article['content']);

require_once 'includes/header.php';
?>

<!-- Wikipedia Top Navigation Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link active fw-bold" href="article.php?id=<?= $article_id ?>"><i class="bi bi-book"></i> Read</a>
    </li>
    <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="edit_article.php?id=<?= $article_id ?>"><i class="bi bi-pencil"></i> Edit Source</a>
        </li>
    <?php endif; ?>
    <li class="nav-item">
        <a class="nav-link" href="history.php?id=<?= $article_id ?>"><i class="bi bi-clock-history"></i> View History</a>
    </li>
</ul>

<div class="row">
    <div class="col-lg-9">
        <h1 class="display-5 fw-bold"><?= htmlspecialchars($article['title']) ?></h1>
        <div class="text-muted small pb-3 mb-4 border-bottom">
            Authored by <strong><?= htmlspecialchars($article['username']) ?></strong> &bull;
            Category: <a href="category.php?id=<?= $article['category_id'] ?>" class="badge bg-secondary text-decoration-none"><?= htmlspecialchars($article['category_name']) ?></a> &bull;
            Last revised: <?= date('M d, Y - H:i', strtotime($article['updated_at'])) ?>
        </div>

        <?php if (!empty($article['featured_image'])): ?>
            <img src="uploads/images/<?= htmlspecialchars($article['featured_image']) ?>" class="img-fluid rounded mb-4" alt="Featured Image">
        <?php endif; ?>

        <!-- Wikipedia Auto-Generated Table of Contents -->
        <div id="wiki-toc" class="p-3 bg-light border rounded mb-4 d-none" style="max-width: 380px;">
            <div class="fw-bold mb-2 border-bottom pb-1"><i class="bi bi-list-nested"></i> Contents</div>
            <ul id="toc-list" class="list-unstyled mb-0 small ps-2"></ul>
        </div>

        <div class="article-body fs-5 lh-lg mb-5">
            <?= $rendered_content ?>
        </div>

        <?php if (!empty($article['tags'])): ?>
            <div class="mb-5">
                <span class="fw-bold me-2"><i class="bi bi-tags"></i> Tags:</span>
                <?php foreach (explode(',', $article['tags']) as $tag): ?>
                    <a href="search.php?q=<?= urlencode(trim($tag)) ?>" class="badge bg-light text-dark border text-decoration-none me-1"><?= htmlspecialchars(trim($tag)) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Comments -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 fw-bold">Discussion & Comments (<?= $comments->num_rows ?>)</div>
            <div class="card-body">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="article.php?id=<?= $article_id ?>" method="POST" class="mb-4">
                        <div class="mb-3">
                            <textarea name="comment_text" class="form-control" rows="3" placeholder="Leave a constructive comment..." required></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn btn-primary btn-sm">Post Comment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border small">
                        Please <a href="login.php">log in</a> to participate in the discussion.
                    </div>
                <?php endif; ?>

                <?php if ($comments->num_rows > 0): ?>
                    <?php while ($comm = $comments->fetch_assoc()): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($comm['username']) ?></span>
                                <small class="text-muted"><?= date('M d, Y', strtotime($comm['created_at'])) ?></small>
                            </div>
                            <p class="mb-0 text-secondary"><?= nl2br(htmlspecialchars($comm['comment_text'])) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted small mb-0">No comments yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const headings = document.querySelectorAll(".article-body h2, .article-body h3");
        const toc = document.getElementById("wiki-toc");
        const tocList = document.getElementById("toc-list");

        if (headings.length > 0) {
            toc.classList.remove("d-none");
            headings.forEach((heading, index) => {
                const id = "section-" + index;
                heading.setAttribute("id", id);
                
                const li = document.createElement("li");
                li.className = heading.tagName === "H3" ? "ms-3 my-1" : "my-1 fw-semibold";
                
                const link = document.createElement("a");
                link.href = "#" + id;
                link.className = "text-decoration-none text-secondary";
                link.textContent = heading.textContent;
                
                li.appendChild(link);
                tocList.appendChild(li);
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>