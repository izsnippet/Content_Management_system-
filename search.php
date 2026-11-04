<?php
// search.php
require_once 'config/db.php';

$search_term = trim($_GET['q'] ?? '');
$search_like = "%" . $search_term . "%";

$query = "
    SELECT a.id, a.title, a.content, a.created_at, u.username, c.name AS category_name 
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' 
      AND (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)
    ORDER BY a.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $search_like, $search_like, $search_like);
$stmt->execute();
$results = $stmt->get_result();

$page_title = 'Search: ' . ($search_term ?: 'All Articles');
require_once 'includes/header.php';
?>

<!-- Search Banner -->
<div class="wk-search-banner animate-fade-in">
    <div class="wk-container" style="max-width:1300px;">
        <h2>
            <?php if (!empty($search_term)): ?>
                Search Results for "<span style="color:#93c5fd;"><?= htmlspecialchars($search_term) ?></span>"
            <?php else: ?>
                Search Articles
            <?php endif; ?>
        </h2>
        <div class="result-count">
            <?= $results->num_rows ?> article<?= $results->num_rows !== 1 ? 's' : '' ?> found
        </div>
        <!-- Inline search to refine -->
        <form action="search.php" method="GET" class="wk-hero-search mt-3" style="max-width:480px;">
            <input type="text" name="q" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search again…">
            <button type="submit"><i class="bi bi-search"></i> <span>Search</span></button>
        </form>
    </div>
</div>

<div class="wk-container" style="max-width:1300px;padding-bottom:3rem;">

    <?php if ($results->num_rows > 0): ?>
        <?php while ($row = $results->fetch_assoc()): ?>
            <article class="wk-article-card mb-3 reveal">
                <div class="card-body">
                    <div class="wk-article-meta">
                        <span class="badge badge-category">
                            <i class="bi bi-grid-3x3-gap-fill" style="font-size:.65rem;"></i>
                            <?= htmlspecialchars($row['category_name']) ?>
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
        <div class="wk-card wk-card-static mt-3">
            <div class="wk-empty">
                <i class="bi bi-search"></i>
                <h5>No results found</h5>
                <p>No articles matched "<?= htmlspecialchars($search_term) ?>". Try different keywords or browse by category.</p>
                <a href="index.php" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="bi bi-compass me-1"></i> Browse All Articles
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
