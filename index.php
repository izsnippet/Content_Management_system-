<?php
// index.php
require_once 'config/db.php';

$query = "
    SELECT a.id, a.user_id, a.title, a.content, a.featured_image, a.created_at, u.username, c.name AS category_name 
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

// Total stats for hero
$total_articles_res = $conn->query("SELECT COUNT(*) AS cnt FROM articles WHERE status = 'published'");
$total_articles = $total_articles_res->fetch_assoc()['cnt'] ?? 0;

$total_authors_res = $conn->query("SELECT COUNT(DISTINCT user_id) AS cnt FROM articles WHERE status = 'published'");
$total_authors = $total_authors_res->fetch_assoc()['cnt'] ?? 0;

$total_cats_res = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
$total_cats = $total_cats_res->fetch_assoc()['cnt'] ?? 0;

$page_title = 'The Collaborative Encyclopedia';
require_once 'includes/header.php';
?>

<!-- ══════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════ -->
<section class="wk-hero animate-fade-in">
    <div class="wk-container">
        <div class="wk-hero-label">
            <i class="bi bi-stars"></i> Open Knowledge Platform
        </div>
        <h1>The Collaborative<br><span>Encyclopedia</span></h1>
        <p>Explore, write, and collaboratively improve knowledge across all topics. Free, open, and built by a community of curious minds.</p>

        <form action="search.php" method="GET" class="wk-hero-search" role="search">
            <input type="text" name="q" placeholder="Search articles, topics, keywords…" aria-label="Search articles">
            <button type="submit">
                <i class="bi bi-search"></i>
                <span>Search</span>
            </button>
        </form>

        <div class="wk-hero-stats">
            <div class="wk-hero-stat">
                <i class="bi bi-file-text"></i>
                <div><strong><?= number_format($total_articles) ?></strong> Articles</div>
            </div>
            <div class="wk-hero-stat">
                <i class="bi bi-people"></i>
                <div><strong><?= number_format($total_authors) ?></strong> Contributors</div>
            </div>
            <div class="wk-hero-stat">
                <i class="bi bi-grid-3x3-gap"></i>
                <div><strong><?= number_format($total_cats) ?></strong> Categories</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════ -->
<main class="wk-home-content">
    <div class="wk-container">
        <div class="row g-4">

            <!-- ── Articles Column ── -->
            <div class="col-lg-8">
                <h2 class="wk-section-title">
                    <i class="bi bi-clock-history text-accent"></i>
                    Latest Articles
                </h2>

                <?php if ($articles->num_rows > 0): ?>
                    <?php while ($row = $articles->fetch_assoc()): ?>
                        <article class="wk-article-card mb-3 reveal">
                            <div class="card-body">
                                <div class="wk-article-meta">
                                    <span class="badge badge-category">
                                        <i class="bi bi-grid-3x3-gap-fill" style="font-size:.65rem;"></i>
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </span>
                                    <span class="meta-dot">·</span>
                                    <a href="author.php?id=<?= $row['user_id'] ?>" class="text-decoration-none text-muted fw-semibold">
                                        <i class="bi bi-person-fill" style="font-size:.8rem;"></i>
                                        <?= htmlspecialchars($row['username']) ?>
                                    </a>
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
                            <i class="bi bi-journal-plus"></i>
                            <h5>No articles published yet</h5>
                            <p>Be the first to write one and share your knowledge.</p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="create_article.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-pencil-square me-1"></i> Write First Article
                                </a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-stars me-1"></i> Join &amp; Contribute
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Sidebar ── -->
            <div class="col-lg-4">

                <!-- Categories -->
                <div class="wk-sidebar-card mb-4 reveal">
                    <div class="wk-sidebar-header">
                        <h6><i class="bi bi-grid-3x3-gap-fill text-accent"></i> Categories</h6>
                        <span class="count-badge"><?= $categories->num_rows ?></span>
                    </div>
                    <div class="wk-category-list">
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <a href="category.php?id=<?= $cat['id'] ?>" class="wk-category-item">
                                <span class="cat-name">
                                    <i class="bi bi-folder2" style="font-size:.8rem;color:var(--muted-light);"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                                <span class="cat-count"><?= $cat['article_count'] ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- CTA card -->
                <div class="wk-sidebar-card reveal" style="background:linear-gradient(135deg,var(--primary) 0%,#1e3a5f 100%);border-color:transparent;">
                    <div class="card-body p-4 text-center">
                        <div style="width:52px;height:52px;background:rgba(59,130,246,.25);border-radius:var(--radius);display:grid;place-items:center;margin:0 auto .9rem;font-size:1.4rem;color:#93c5fd;">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <h6 style="color:#fff;font-size:.95rem;font-weight:700;margin-bottom:.4rem;">Share Your Knowledge</h6>
                        <p style="color:rgba(255,255,255,.65);font-size:.82rem;margin-bottom:1rem;line-height:1.5;">Join our community of writers and contribute to the collective encyclopedia.</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="create_article.php" class="btn btn-primary w-100" style="font-size:.85rem;">
                                <i class="bi bi-plus-circle me-1"></i> Write an Article
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary w-100 mb-2" style="font-size:.85rem;">
                                <i class="bi bi-stars me-1"></i> Get Started Free
                            </a>
                            <a href="login.php" style="color:rgba(255,255,255,.55);font-size:.8rem;text-decoration:none;display:block;transition:var(--transition);">
                                Already have an account? Sign in →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /.col-lg-4 -->
        </div><!-- /.row -->
    </div><!-- /.wk-container -->
</main>

<?php require_once 'includes/footer.php'; ?>
