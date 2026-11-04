<?php
// category.php
require_once 'config/db.php';

$category_id = intval($_GET['id'] ?? 0);

$cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
$cat_stmt->bind_param("i", $category_id);
$cat_stmt->execute();
$category = $cat_stmt->get_result()->fetch_assoc();
$cat_stmt->close();

if (!$category) {
    die("Category does not exist.");
}

$query = "
    SELECT a.id, a.title, a.content, a.created_at, u.username 
    FROM articles a
    JOIN users u ON a.user_id = u.id
    WHERE a.category_id = ? AND a.status = 'published'
    ORDER BY a.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$articles = $stmt->get_result();

$page_title = 'Category: ' . $category['name'];
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1300px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- Category Banner -->
    <div class="wk-cat-banner animate-fade-in">
        <div class="wk-cat-banner-info">
            <div class="wk-cat-icon">
                <i class="bi bi-folder2-open"></i>
            </div>
            <div>
                <h3 class="wk-cat-title"><?= htmlspecialchars($category['name']) ?></h3>
                <p class="wk-cat-subtitle">
                    <i class="bi bi-file-text me-1"></i>
                    <?= $articles->num_rows ?> published article<?= $articles->num_rows !== 1 ? 's' : '' ?> in this category
                </p>
            </div>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> All Categories
        </a>
    </div>

    <!-- Article List -->
    <?php if ($articles->num_rows > 0): ?>
        <?php while ($row = $articles->fetch_assoc()): ?>
            <article class="wk-article-card mb-3 reveal">
                <div class="card-body">
                    <div class="wk-article-meta">
                        <span class="badge badge-category">
                            <i class="bi bi-folder2" style="font-size:.65rem;"></i>
                            <?= htmlspecialchars($category['name']) ?>
                        </span>
                        <span class="meta-dot">·</span>
                        <i class="bi bi-person-fill" style="font-size:.8rem;"></i>
                        <?= htmlspecialchars($row['username']) ?>
                        <span class="meta-dot">·</span>
                        <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                    </div>
                    <a href="article.php?id=<?= $row['id'] ?>" class="wk-article-title">
                        <?= htmlspecialchars($row['title']) ?>
                    </a>
                    <p class="wk-article-excerpt">
                        <?= htmlspecialchars(substr(strip_tags($row['content']), 0, 220)) ?>…
                    </p>
                    <a href="article.php?id=<?= $row['id'] ?>" class="wk-read-more">
                        Read article <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="wk-card wk-card-static">
            <div class="wk-empty">
                <i class="bi bi-folder2"></i>
                <h5>No articles in this category yet</h5>
                <p>Be the first to publish an article in <strong><?= htmlspecialchars($category['name']) ?></strong>.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="create_article.php" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-pencil-square me-1"></i> Write an Article
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
