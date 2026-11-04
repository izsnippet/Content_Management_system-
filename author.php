<?php
// author.php
require_once 'config/db.php';

$author_id = intval($_GET['id'] ?? 0);

if ($author_id <= 0) {
    $author = null;
} else {
    $stmt = $conn->prepare("SELECT id, username, bio, profile_pic, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $author = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$articles = null;
$total_published = 0;

if ($author) {
    // Fetch author's published articles
    $art_stmt = $conn->prepare("
        SELECT a.id, a.title, a.content, a.featured_image, a.created_at, c.name AS category_name, c.id AS category_id
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        WHERE a.user_id = ? AND a.status = 'published'
        ORDER BY a.created_at DESC
    ");
    $art_stmt->bind_param("i", $author_id);
    $art_stmt->execute();
    $articles = $art_stmt->get_result();
    $total_published = $articles->num_rows;
    $art_stmt->close();
}

$page_title = $author ? htmlspecialchars($author['username']) . ' — Author Profile' : 'Author Not Found';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1200px;padding-top:2rem;padding-bottom:3.5rem;">

    <?php if (!$author): ?>
        <div class="wk-card wk-card-static p-4 p-md-5 text-center my-4 animate-fade-in">
            <i class="bi bi-person-x display-3 text-muted mb-3 d-block"></i>
            <h3 class="fw-bold text-dark mb-2">Author Not Found</h3>
            <p class="text-secondary mb-4">The author profile you are looking for does not exist or has been removed.</p>
            <a href="index.php" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Return to Homepage
            </a>
        </div>
    <?php else: ?>
        <?php
            $has_custom_pic = (!empty($author['profile_pic']) && $author['profile_pic'] !== 'default_avatar.png' && file_exists('uploads/profiles/' . $author['profile_pic']));
            $avatar_path = $has_custom_pic ? 'uploads/profiles/' . htmlspecialchars($author['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
        ?>

        <div class="row g-4">
            <!-- ── Left Column: Author Profile Card ── -->
            <div class="col-lg-4">
                <div class="wk-card wk-card-static text-center p-4 animate-fade-in" style="position:sticky;top:calc(var(--nav-h) + 1.25rem);">
                    <img src="<?= $avatar_path ?>" alt="<?= htmlspecialchars($author['username']) ?>"
                         class="wk-profile-avatar mb-3" style="width:120px;height:120px;object-fit:cover;">

                    <h4 class="fw-bold mb-1" style="font-family:var(--font-heading);letter-spacing:-.02em;">
                        <?= htmlspecialchars($author['username']) ?>
                    </h4>

                    <div class="mb-3">
                        <span class="badge <?= $author['role'] === 'admin' ? 'bg-danger' : 'badge-category' ?>" style="font-size:.75rem;">
                            <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars(strtoupper($author['role'] ?? 'author')) ?>
                        </span>
                    </div>

                    <div class="text-secondary small mb-3 lh-base px-2">
                        <?= !empty($author['bio']) ? nl2br(htmlspecialchars($author['bio'])) : '<em class="text-muted">This contributor has not added a biography yet.</em>' ?>
                    </div>

                    <div class="p-3 bg-light border rounded mb-3 text-start small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted"><i class="bi bi-journal-text me-1 text-accent"></i> Articles Published:</span>
                            <span class="fw-bold text-dark"><?= $total_published ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 mt-1">
                            <span class="text-muted"><i class="bi bi-calendar3 me-1 text-accent"></i> Member Since:</span>
                            <span class="fw-semibold text-dark"><?= date('M Y', strtotime($author['created_at'])) ?></span>
                        </div>
                    </div>

                    <a href="index.php" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-compass me-1"></i> Browse All Topics
                    </a>
                </div>
            </div>

            <!-- ── Right Column: Author's Published Articles ── -->
            <div class="col-lg-8">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h4 class="fw-bold mb-0" style="font-family:var(--font-heading);">
                        <i class="bi bi-files text-accent me-2"></i>Published Articles by <?= htmlspecialchars($author['username']) ?>
                    </h4>
                    <span class="badge bg-light text-dark border"><?= $total_published ?> article<?= $total_published !== 1 ? 's' : '' ?></span>
                </div>

                <?php if ($total_published > 0): ?>
                    <?php while ($row = $articles->fetch_assoc()): ?>
                        <article class="wk-article-card mb-3 reveal">
                            <div class="card-body">
                                <div class="wk-article-meta">
                                    <a href="category.php?id=<?= $row['category_id'] ?>" class="badge badge-category text-decoration-none">
                                        <i class="bi bi-folder2" style="font-size:.7rem;"></i>
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </a>
                                    <span class="meta-dot">·</span>
                                    <span>
                                        <i class="bi bi-calendar3" style="font-size:.78rem;"></i>
                                        <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                    </span>
                                </div>
                                <h4 class="wk-article-title mt-2">
                                    <a href="article.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </a>
                                </h4>
                                <p class="wk-article-excerpt mb-2">
                                    <?= htmlspecialchars(substr(strip_tags($row['content']), 0, 220)) ?>…
                                </p>
                                <a href="article.php?id=<?= $row['id'] ?>" class="wk-read-more">
                                    Read Article <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="wk-card wk-card-static p-4 text-center">
                        <i class="bi bi-journal-x display-4 text-muted mb-2 d-block"></i>
                        <h5 class="fw-bold text-dark">No published articles yet</h5>
                        <p class="text-muted small mb-0"><?= htmlspecialchars($author['username']) ?> has not published any public articles yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
