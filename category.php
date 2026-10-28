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

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Category: <span class="text-primary"><?= htmlspecialchars($category['name']) ?></span></h3>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; All Categories</a>
</div>

<?php if ($articles->num_rows > 0): ?>
    <?php while ($row = $articles->fetch_assoc()): ?>
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title fw-bold">
                    <a href="article.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                        <?= htmlspecialchars($row['title']) ?>
                    </a>
                </h4>
                <div class="text-muted small mb-2">
                    By <?= htmlspecialchars($row['username']) ?> &bull; <?= date('M d, Y', strtotime($row['created_at'])) ?>
                </div>
                <p class="card-text text-secondary mb-0">
                    <?= substr(strip_tags($row['content']), 0, 180) ?>...
                </p>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="alert alert-light border">No articles currently published in this category.</div>
<?php endif; ?>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>