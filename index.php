<?php
// index.php
require_once 'config/db.php';

$query = "
    SELECT a.id, a.title, a.content, a.featured_image, a.created_at, u.username, c.name AS category_name 
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published'
    ORDER BY a.created_at DESC
";
$articles = $conn->query($query);
// Fetch categories along with the count of published articles in each
$categories = $conn->query("
    SELECT c.id, c.name, COUNT(a.id) AS article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
    GROUP BY c.id, c.name 
    ORDER BY c.name ASC
");

require_once 'includes/header.php';
?>

<div class="p-4 p-md-5 mb-4 rounded text-white bg-dark shadow-sm">
    <div class="col-md-8 px-0">
        <h1 class="display-5 fw-bold fst-italic">The Collaborative Encyclopedia</h1>
        <p class="lead my-3">Explore, write, and collaboratively improve knowledge with verified revisions.</p>
        <form action="search.php" method="GET" class="d-flex gap-2 col-md-10">
            <input type="text" name="q" class="form-control" placeholder="Search articles, keywords, or topics..." required>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <h4 class="fw-bold border-bottom pb-2 mb-4">Latest Published Articles</h4>
        
        <?php if ($articles->num_rows > 0): ?>
            <?php while ($row = $articles->fetch_assoc()): ?>
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2"><?= htmlspecialchars($row['category_name']) ?></span>
                        <h3 class="card-title fw-bold">
                            <a href="article.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($row['title']) ?>
                            </a>
                        </h3>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-person"></i> <?= htmlspecialchars($row['username']) ?> &bull; 
                            <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($row['created_at'])) ?>
                        </div>
                        <p class="card-text text-secondary">
                            <?= substr(strip_tags($row['content']), 0, 200) ?>...
                        </p>
                        <a href="article.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">Read Article &rarr;</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">No articles published yet. Be the first to write one!</div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-grid-3x3-gap"></i> Categories</span>
            <span class="badge bg-light text-dark border"><?= $categories->num_rows ?></span>
        </div>
        <!-- Scrollable box so many categories won't make the page too long -->
        <div class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <a href="category.php?id=<?= $cat['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= $cat['article_count'] ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>