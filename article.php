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

// 1. Comment Submission Handler
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

// 2. User Comment Deletion Handler (TASK 2: Author ownership verification)
if (isset($_GET['del_comment']) && isset($_SESSION['user_id'])) {
    $del_comment_id = intval($_GET['del_comment']);
    $del_c_stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $del_c_stmt->bind_param("ii", $del_comment_id, $_SESSION['user_id']);
    $del_c_stmt->execute();
    $del_c_stmt->close();
    header("Location: article.php?id=" . $article_id);
    exit();
}

// 3. Article Watchlist / Bookmark Toggle Handler (TASK 4)
if (isset($_GET['toggle_watchlist']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $chk_wl = $conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND article_id = ? LIMIT 1");
    $chk_wl->bind_param("ii", $user_id, $article_id);
    $chk_wl->execute();
    $wl_res = $chk_wl->get_result();

    if ($wl_item = $wl_res->fetch_assoc()) {
        // Remove from watchlist
        $del_wl = $conn->prepare("DELETE FROM watchlist WHERE id = ?");
        $del_wl->bind_param("i", $wl_item['id']);
        $del_wl->execute();
        $del_wl->close();
    } else {
        // Add to watchlist
        $add_wl = $conn->prepare("INSERT INTO watchlist (user_id, article_id) VALUES (?, ?)");
        $add_wl->bind_param("ii", $user_id, $article_id);
        $add_wl->execute();
        $add_wl->close();
    }
    $chk_wl->close();
    header("Location: article.php?id=" . $article_id);
    exit();
}

// 4. Check if current user bookmarked this article
$is_bookmarked = false;
if (isset($_SESSION['user_id'])) {
    $wl_stmt = $conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND article_id = ? LIMIT 1");
    $wl_stmt->bind_param("ii", $_SESSION['user_id'], $article_id);
    $wl_stmt->execute();
    $is_bookmarked = ($wl_stmt->get_result()->num_rows > 0);
    $wl_stmt->close();
}

// 5. Fetch Comments with c.id and c.user_id for ownership checks
$c_query = "
    SELECT c.id, c.user_id, c.comment_text, c.created_at, u.username, u.profile_pic 
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

$page_title = $article['title'];
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1300px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- ── Article Tabs & Watchlist Action ── -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <ul class="nav nav-tabs mb-0 border-bottom-0">
            <li class="nav-item">
                <a class="nav-link active fw-bold" href="article.php?id=<?= $article_id ?>"><i class="bi bi-book"></i> Read</a>
            </li>
            <?php
                $logged_in_id = intval($_SESSION['user_id'] ?? 0);
                $author_id = intval($article['user_id']);
                $user_role = $_SESSION['role'] ?? 'author';
            ?>
            <?php if ($logged_in_id > 0 && $logged_in_id === $author_id && $user_role !== 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-primary fw-semibold" href="edit_article.php?id=<?= $article_id ?>"><i class="bi bi-pencil-square"></i> Edit My Article</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="history.php?id=<?= $article_id ?>"><i class="bi bi-clock-history"></i> View History</a>
            </li>
        </ul>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="article.php?id=<?= $article_id ?>&toggle_watchlist=1" class="btn btn-sm <?= $is_bookmarked ? 'btn-warning text-dark' : 'btn-outline-secondary' ?>">
                <i class="bi <?= $is_bookmarked ? 'bi-star-fill' : 'bi-star' ?>"></i> <?= $is_bookmarked ? 'Saved in Watchlist' : 'Add to Watchlist' ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- ── Main Content ── -->
        <div class="col-lg-9">

            <!-- Article Header -->
            <div class="wk-article-header animate-fade-in">
                <span class="badge badge-category mb-3" style="font-size:.8rem;padding:.35em .9em;">
                    <i class="bi bi-grid-3x3-gap-fill" style="font-size:.7rem;"></i>
                    <a href="category.php?id=<?= $article['category_id'] ?>" style="color:inherit;text-decoration:none;">
                        <?= htmlspecialchars($article['category_name']) ?>
                    </a>
                </span>
                <h1><?= htmlspecialchars($article['title']) ?></h1>
            </div>

            <!-- Byline with Author Link (TASK 3) -->
            <div class="wk-article-byline animate-fade-in">
                <a href="author.php?id=<?= $article['user_id'] ?>" class="byline-author text-decoration-none">
                    <i class="bi bi-person-fill"></i>
                    <span><?= htmlspecialchars($article['username']) ?></span>
                </a>
                <span class="sep">·</span>
                <span><i class="bi bi-calendar3"></i> Published <?= date('M d, Y', strtotime($article['created_at'])) ?></span>
                <span class="sep">·</span>
                <span><i class="bi bi-pencil"></i> Last revised <?= date('M d, Y', strtotime($article['updated_at'])) ?></span>
            </div>

            <!-- Featured Image -->
            <?php if (!empty($article['featured_image'])): ?>
                <div class="mb-4 text-center">
                    <img src="<?= htmlspecialchars($article['featured_image'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded shadow-sm" style="max-height: 450px; width: 100%; object-fit: cover;" alt="<?= htmlspecialchars($article['title']) ?>">
                </div>
            <?php endif; ?>

            <!-- Table of Contents -->
            <div id="wiki-toc" class="d-none animate-fade-in">
                <div class="toc-heading">
                    <i class="bi bi-list-nested"></i> Contents
                </div>
                <ul id="toc-list"></ul>
            </div>

            <!-- Article Body -->
            <div class="article-body animate-slide-up">
                <?= $rendered_content ?>
            </div>

            <!-- Tags -->
            <?php if (!empty($article['tags'])): ?>
                <div class="wk-tags mt-3 mb-5">
                    <span class="wk-tags-label"><i class="bi bi-tags-fill"></i> Tags:</span>
                    <?php foreach (explode(',', $article['tags']) as $tag): ?>
                        <a href="search.php?q=<?= urlencode(trim($tag)) ?>" class="badge badge-tag">
                            <?= htmlspecialchars(trim($tag)) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Comments Section -->
            <div class="wk-comments-card animate-slide-up">
                <div class="wk-comments-header">
                    <i class="bi bi-chat-dots-fill text-accent"></i>
                    <h5>Discussion &amp; Comments</h5>
                    <span class="wk-comment-count"><?= $comments->num_rows ?></span>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="wk-comment-form">
                        <form action="article.php?id=<?= $article_id ?>" method="POST">
                            <div class="mb-3">
                                <textarea name="comment_text" class="form-control" rows="3"
                                          placeholder="Share your thoughts or contribute to the discussion…" required></textarea>
                            </div>
                            <button type="submit" name="submit_comment" class="btn btn-primary btn-sm">
                                <i class="bi bi-send me-1"></i> Post Comment
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="wk-comment-login-prompt">
                        <i class="bi bi-info-circle me-1"></i>
                        Please <a href="login.php">sign in</a> to participate in the discussion.
                    </div>
                <?php endif; ?>

                <?php if ($comments->num_rows > 0): ?>
                    <div class="wk-comments-list">
                        <?php while ($comm = $comments->fetch_assoc()): ?>
                            <?php
                                $comm_pic = (!empty($comm['profile_pic']) && $comm['profile_pic'] !== 'default_avatar.png' && file_exists('uploads/profiles/' . $comm['profile_pic']))
                                            ? 'uploads/profiles/' . htmlspecialchars($comm['profile_pic'])
                                            : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                            ?>
                            <div class="wk-comment">
                                <a href="author.php?id=<?= $comm['user_id'] ?>">
                                    <img src="<?= $comm_pic ?>" class="wk-comment-avatar" alt="<?= htmlspecialchars($comm['username']) ?>">
                                </a>
                                <div class="wk-comment-body">
                                    <div class="wk-comment-meta">
                                        <a href="author.php?id=<?= $comm['user_id'] ?>" class="wk-comment-author text-decoration-none">
                                            <?= htmlspecialchars($comm['username']) ?>
                                        </a>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="wk-comment-date">
                                                <i class="bi bi-clock" style="font-size:.75rem;"></i>
                                                <?= date('M d, Y · H:i', strtotime($comm['created_at'])) ?>
                                            </span>
                                            <!-- TASK 2: Delete Comment Button if logged in user is author of comment -->
                                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comm['user_id']): ?>
                                                <a href="article.php?id=<?= $article_id ?>&del_comment=<?= $comm['id'] ?>"
                                                   class="btn btn-sm btn-outline-danger border-0 p-0 text-danger lh-1 ms-1"
                                                   title="Delete your comment"
                                                   onclick="return confirm('Delete this comment?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="wk-comment-text"><?= nl2br(htmlspecialchars($comm['comment_text'])) ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="wk-no-comments">
                        <i class="bi bi-chat" style="font-size:1.5rem;opacity:.35;display:block;margin-bottom:.4rem;"></i>
                        No comments yet. Be the first to start the discussion!
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /.col-lg-9 -->

        <!-- ── Right Sidebar ── -->
        <div class="col-lg-3">
            <div class="wk-sidebar-card mb-3" style="position:sticky;top:calc(var(--nav-h) + 1rem);">
                <div class="wk-sidebar-header">
                    <h6><i class="bi bi-info-circle text-accent"></i> Article Info</h6>
                </div>
                <div style="padding:1rem 1.25rem;font-size:.85rem;">
                    <!-- Author Information with link to author.php (TASK 3) -->
                    <div style="margin-bottom:.85rem;">
                        <div style="font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;">Author</div>
                        <a href="author.php?id=<?= $article['user_id'] ?>" class="text-decoration-none text-dark fw-semibold d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle" style="color:var(--accent);font-size:1.1rem;"></i>
                            <span><?= htmlspecialchars($article['username']) ?></span>
                        </a>
                    </div>
                    <div style="margin-bottom:.85rem;">
                        <div style="font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;">Category</div>
                        <a href="category.php?id=<?= $article['category_id'] ?>" class="badge badge-category" style="font-size:.8rem;">
                            <?= htmlspecialchars($article['category_name']) ?>
                        </a>
                    </div>
                    <div style="margin-bottom:.85rem;">
                        <div style="font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;">Published</div>
                        <div style="color:var(--text-secondary);"><?= date('M d, Y', strtotime($article['created_at'])) ?></div>
                    </div>
                    <div>
                        <div style="font-size:.73rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:.2rem;">Last Revised</div>
                        <div style="color:var(--text-secondary);"><?= date('M d, Y', strtotime($article['updated_at'])) ?></div>
                    </div>
                </div>
                <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:.4rem;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="article.php?id=<?= $article_id ?>&toggle_watchlist=1"
                           class="btn btn-sm <?= $is_bookmarked ? 'btn-outline-warning text-dark' : 'btn-outline-secondary' ?> w-100 mb-1">
                            <i class="bi <?= $is_bookmarked ? 'bi-star-fill text-warning' : 'bi-star' ?> me-1"></i>
                            <?= $is_bookmarked ? 'Remove from Watchlist' : 'Add to Watchlist' ?>
                        </a>
                    <?php endif; ?>
                    <a href="history.php?id=<?= $article_id ?>" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-clock-history me-1"></i> View History
                    </a>
                    <?php if ($logged_in_id > 0 && $logged_in_id === $author_id && $user_role !== 'admin'): ?>
                        <a href="edit_article.php?id=<?= $article_id ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-pencil me-1"></i> Edit My Article
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.col-lg-3 -->
    </div><!-- /.row -->
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
