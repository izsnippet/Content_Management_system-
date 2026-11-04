<?php
// dashboard.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$user_role = $_SESSION['role'] ?? 'author';

// User Profile Information
$user_stmt = $conn->prepare("SELECT email, bio, profile_pic, role, created_at FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

$has_custom_pic = (!empty($current_user['profile_pic']) && $current_user['profile_pic'] !== 'default_avatar.png' && file_exists('uploads/profiles/' . $current_user['profile_pic']));
$avatar_path = $has_custom_pic ? 'uploads/profiles/' . htmlspecialchars($current_user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

// Published count
$pub_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'published'");
$pub_stmt->bind_param("i", $user_id);
$pub_stmt->execute();
$pub_stmt->bind_result($total_published);
$pub_stmt->fetch();
$pub_stmt->close();

// Drafts count
$draft_stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ? AND status = 'draft'");
$draft_stmt->bind_param("i", $user_id);
$draft_stmt->execute();
$draft_stmt->bind_result($total_drafts);
$draft_stmt->fetch();
$draft_stmt->close();

// Revisions count
$rev_stmt = $conn->prepare("SELECT COUNT(*) FROM article_revisions WHERE edited_by_user_id = ?");
$rev_stmt->bind_param("i", $user_id);
$rev_stmt->execute();
$rev_stmt->bind_result($total_edits);
$rev_stmt->fetch();
$rev_stmt->close();

// Fetch author's articles list
$query = "
    SELECT a.id, a.title, a.status, a.created_at, a.updated_at, c.name AS category_name 
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.updated_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$articles = $stmt->get_result();

// Fetch recent comments on user's articles (Activity Feed)
$comm_query = "
    SELECT c.comment_text, c.created_at, u.username AS commenter, a.title AS article_title, a.id AS article_id
    FROM comments c
    JOIN articles a ON c.article_id = a.id
    JOIN users u ON c.user_id = u.id
    WHERE a.user_id = ?
    ORDER BY c.created_at DESC
    LIMIT 6
";
$comm_stmt = $conn->prepare($comm_query);
$comm_stmt->bind_param("i", $user_id);
$comm_stmt->execute();
$user_comments = $comm_stmt->get_result();
$comm_stmt->close();

// Admin data (if user is admin or accessing admin tabs)
$is_admin = ($user_role === 'admin');
$admin_articles = null;
$admin_users = null;
$admin_categories = null;
$global_stats = [];

if ($is_admin) {
    // Platform-wide counts
    $g_art = $conn->query("SELECT COUNT(*) AS total_art FROM articles")->fetch_assoc();
    $g_usr = $conn->query("SELECT COUNT(*) AS total_usr FROM users")->fetch_assoc();
    $g_cat = $conn->query("SELECT COUNT(*) AS total_cat FROM categories")->fetch_assoc();
    $g_com = $conn->query("SELECT COUNT(*) AS total_com FROM comments")->fetch_assoc();

    $global_stats = [
        'articles'   => $g_art['total_art'] ?? 0,
        'users'      => $g_usr['total_usr'] ?? 0,
        'categories' => $g_cat['total_cat'] ?? 0,
        'comments'   => $g_com['total_com'] ?? 0
    ];

    // All articles across platform
    $admin_articles = $conn->query("
        SELECT a.id, a.title, a.status, a.created_at, a.updated_at, u.username, c.name AS category_name
        FROM articles a
        JOIN users u ON a.user_id = u.id
        JOIN categories c ON a.category_id = c.id
        ORDER BY a.created_at DESC
    ");

    // All registered users
    $admin_users = $conn->query("
        SELECT u.id, u.username, u.email, u.role, u.created_at,
               (SELECT COUNT(*) FROM articles WHERE user_id = u.id) AS article_count
        FROM users u
        ORDER BY u.created_at DESC
    ");

    // All categories with article counts
    $admin_categories = $conn->query("
        SELECT c.id, c.name, COUNT(a.id) AS article_count
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id
        GROUP BY c.id, c.name
        ORDER BY article_count DESC
    ");
}

$page_title = 'Dashboard';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1300px;padding-top:1.75rem;padding-bottom:3.5rem;">

    <!-- Dashboard Hero Header -->
    <div class="wk-dash-banner animate-fade-in">
        <div class="wk-dash-banner-inner">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= $avatar_path ?>" alt="Avatar" class="rounded-circle border"
                     style="width:58px;height:58px;object-fit:cover;border-width:2px;border-color:rgba(255,255,255,.3);">
                <div>
                    <h2>
                        Welcome back, <?= htmlspecialchars($username) ?>
                        <?php if ($is_admin): ?>
                            <span class="badge bg-danger ms-2" style="font-size:.7rem;vertical-align:middle;">ADMIN</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark ms-2" style="font-size:.7rem;vertical-align:middle;">AUTHOR</span>
                        <?php endif; ?>
                    </h2>
                    <p>
                        <?= !empty($current_user['bio']) ? htmlspecialchars(substr($current_user['bio'], 0, 90)) . '…' : 'Manage your articles, review community comments, and contribute knowledge.' ?>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="profile.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-person-gear me-1"></i> Edit Profile
                </a>
                <a href="create_article.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Write New Article
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Pills / View Switcher (Author vs Admin) -->
    <ul class="nav nav-pills mb-4" id="dashboardTabs" role="tablist" style="gap:.4rem;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="my-articles-tab" data-bs-toggle="pill" data-bs-target="#my-articles-pane" type="button" role="tab">
                <i class="bi bi-journal-text me-1"></i> My Articles (<?= $articles->num_rows ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity-pane" type="button" role="tab">
                <i class="bi bi-chat-left-dots me-1"></i> Comments Activity
            </button>
        </li>
        <?php if ($is_admin): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-danger" id="admin-overview-tab" data-bs-toggle="pill" data-bs-target="#admin-overview-pane" type="button" role="tab">
                    <i class="bi bi-speedometer2 me-1"></i> Platform Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-danger" id="admin-articles-tab" data-bs-toggle="pill" data-bs-target="#admin-articles-pane" type="button" role="tab">
                    <i class="bi bi-collection me-1"></i> All Articles (<?= $global_stats['articles'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-danger" id="admin-users-tab" data-bs-toggle="pill" data-bs-target="#admin-users-pane" type="button" role="tab">
                    <i class="bi bi-people me-1"></i> Users (<?= $global_stats['users'] ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold text-danger" id="admin-categories-tab" data-bs-toggle="pill" data-bs-target="#admin-categories-pane" type="button" role="tab">
                    <i class="bi bi-tags me-1"></i> Categories (<?= $global_stats['categories'] ?>)
                </button>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="dashboardTabContent">

        <!-- ══════════════════════════════════════════
             PANE 1: MY ARTICLES (Author Dashboard)
        ══════════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="my-articles-pane" role="tabpanel">

            <!-- Author Metrics -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="wk-stat-card reveal">
                        <div class="wk-stat-icon success">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="wk-stat-label">Published Articles</div>
                            <div class="wk-stat-value" data-target="<?= $total_published ?>"><?= $total_published ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="wk-stat-card reveal">
                        <div class="wk-stat-icon warning">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <div class="wk-stat-label">Drafts Saved</div>
                            <div class="wk-stat-value" data-target="<?= $total_drafts ?>"><?= $total_drafts ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="wk-stat-card reveal">
                        <div class="wk-stat-icon info">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div class="wk-stat-label">Revisions Contributed</div>
                            <div class="wk-stat-value" data-target="<?= $total_edits ?>"><?= $total_edits ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles Table with Search & Status Filters -->
            <div class="wk-dash-table-wrap reveal">
                <div class="wk-dash-table-header flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <h5><i class="bi bi-files me-1 text-accent"></i> My Article Library</h5>
                        <span class="badge bg-light text-dark border"><?= $articles->num_rows ?> total</span>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Quick filter status pills -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active" id="filterAll">All</button>
                            <button type="button" class="btn btn-outline-secondary" id="filterPublished">Published</button>
                            <button type="button" class="btn btn-outline-secondary" id="filterDraft">Drafts</button>
                        </div>

                        <!-- Live search input -->
                        <div style="position:relative;width:220px;">
                            <i class="bi bi-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted-light);font-size:.8rem;pointer-events:none;"></i>
                            <input type="text" id="articleTableSearch" class="form-control form-control-sm"
                                   style="padding-left:2.1rem;font-size:.82rem;"
                                   placeholder="Filter articles…">
                        </div>

                        <a href="create_article.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus me-1"></i> Write
                        </a>
                    </div>
                </div>

                <?php if ($articles->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="wk-table" id="authorArticlesTable">
                            <thead>
                                <tr>
                                    <th style="width:42%;">Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th class="text-end" style="padding-right:1.4rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $articles->fetch_assoc()): ?>
                                    <tr data-status="<?= htmlspecialchars($row['status']) ?>">
                                        <td>
                                            <a href="article.php?id=<?= $row['id'] ?>"
                                               class="article-row-title"
                                               style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px;"
                                               title="<?= htmlspecialchars($row['title']) ?>">
                                                <?= htmlspecialchars($row['title']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-category" style="font-size:.73rem;">
                                                <?= htmlspecialchars($row['category_name']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] === 'published'): ?>
                                                <span class="status-pill published"><i class="bi bi-check-circle-fill"></i> Published</span>
                                            <?php elseif ($row['status'] === 'draft'): ?>
                                                <span class="status-pill draft"><i class="bi bi-pencil-fill"></i> Draft</span>
                                            <?php else: ?>
                                                <span class="status-pill archived"><i class="bi bi-archive-fill"></i> Archived</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color:var(--muted);font-size:.82rem;white-space:nowrap;">
                                            <i class="bi bi-calendar3" style="font-size:.8rem;"></i>
                                            <?= date('M d, Y', strtotime($row['updated_at'])) ?>
                                        </td>
                                        <td class="actions" style="padding-right:1.4rem;">
                                            <a href="article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Read Article">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="edit_article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Edit Article">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="delete_article.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger wk-delete-btn" title="Delete Article">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="wk-empty">
                        <i class="bi bi-journal-plus"></i>
                        <h5>No articles written yet</h5>
                        <p>Start by creating your first article or saving a draft.</p>
                        <a href="create_article.php" class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-pencil-square me-1"></i> Write Your First Article
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /#my-articles-pane -->

        <!-- ══════════════════════════════════════════
             PANE 2: COMMENTS ACTIVITY
        ══════════════════════════════════════════ -->
        <div class="tab-pane fade" id="activity-pane" role="tabpanel">
            <div class="wk-card wk-card-static">
                <div class="wk-dash-table-header">
                    <h5><i class="bi bi-chat-left-text text-accent me-2"></i> Recent Comments on Your Articles</h5>
                </div>
                <div class="p-3 p-md-4">
                    <?php if ($user_comments && $user_comments->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($comm = $user_comments->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start py-3 px-0 border-bottom">
                                    <div class="me-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($comm['commenter']) ?></span>
                                            <span class="text-muted small">&bull;</span>
                                            <span class="text-muted small"><?= date('M d, Y · H:i', strtotime($comm['created_at'])) ?></span>
                                        </div>
                                        <p class="text-secondary mb-1" style="font-size:.9rem;"><?= nl2br(htmlspecialchars($comm['comment_text'])) ?></p>
                                        <div class="small">
                                            <span class="text-muted">On article:</span>
                                            <a href="article.php?id=<?= $comm['article_id'] ?>" class="text-primary fw-semibold text-decoration-none">
                                                <?= htmlspecialchars($comm['article_title']) ?> &rarr;
                                            </a>
                                        </div>
                                    </div>
                                    <a href="article.php?id=<?= $comm['article_id'] ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                        View Discussion
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="wk-empty py-4">
                            <i class="bi bi-chat-square-dots"></i>
                            <h5>No comments received yet</h5>
                            <p class="text-muted small">When other authors and readers leave comments on your articles, they will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- ══════════════════════════════════════════
                 ADMIN PANE 3: PLATFORM OVERVIEW
            ══════════════════════════════════════════ -->
            <div class="tab-pane fade" id="admin-overview-pane" role="tabpanel">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="wk-stat-card">
                            <div class="wk-stat-icon info">
                                <i class="bi bi-files"></i>
                            </div>
                            <div>
                                <div class="wk-stat-label">Total Articles</div>
                                <div class="wk-stat-value"><?= number_format($global_stats['articles']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="wk-stat-card">
                            <div class="wk-stat-icon success">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <div class="wk-stat-label">Registered Users</div>
                                <div class="wk-stat-value"><?= number_format($global_stats['users']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="wk-stat-card">
                            <div class="wk-stat-icon warning">
                                <i class="bi bi-folder2-open"></i>
                            </div>
                            <div>
                                <div class="wk-stat-label">Categories</div>
                                <div class="wk-stat-value"><?= number_format($global_stats['categories']) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="wk-stat-card">
                            <div class="wk-stat-icon info">
                                <i class="bi bi-chat-quote-fill"></i>
                            </div>
                            <div>
                                <div class="wk-stat-label">Total Comments</div>
                                <div class="wk-stat-value"><?= number_format($global_stats['comments']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wk-card wk-card-static p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-danger me-2"></i>Admin Console Capabilities</h5>
                    <p class="text-secondary small mb-4">You have administrator privileges on this WikiCMS instance. Use the sub-tabs above to manage all articles, inspect user accounts, and monitor category distributions across the platform.</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold"><i class="bi bi-collection text-primary me-2"></i>Articles Oversight</h6>
                                <p class="small text-muted mb-0">Browse every published, draft, or archived article written by any author.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold"><i class="bi bi-people text-success me-2"></i>User Auditing</h6>
                                <p class="small text-muted mb-0">View all registered authors, roles, and contributor statistics.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold"><i class="bi bi-tags text-warning me-2"></i>Taxonomy Distribution</h6>
                                <p class="small text-muted mb-0">Track which knowledge categories are most active or require content expansion.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 ADMIN PANE 4: ALL ARTICLES MANAGEMENT
            ══════════════════════════════════════════ -->
            <div class="tab-pane fade" id="admin-articles-pane" role="tabpanel">
                <div class="wk-dash-table-wrap">
                    <div class="wk-dash-table-header">
                        <h5><i class="bi bi-collection text-danger me-2"></i>Platform Articles Registry</h5>
                        <input type="text" id="adminArticlesSearch" class="form-control form-control-sm" style="width:220px;" placeholder="Filter platform articles…">
                    </div>
                    <div class="table-responsive">
                        <table class="wk-table" id="adminArticlesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end" style="padding-right:1.4rem;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admin_articles && $admin_articles->num_rows > 0): ?>
                                    <?php while ($a = $admin_articles->fetch_assoc()): ?>
                                        <tr>
                                            <td style="color:var(--muted);font-weight:600;">#<?= $a['id'] ?></td>
                                            <td>
                                                <a href="article.php?id=<?= $a['id'] ?>" class="fw-semibold">
                                                    <?= htmlspecialchars($a['title']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <i class="bi bi-person me-1 text-muted"></i><?= htmlspecialchars($a['username']) ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-category"><?= htmlspecialchars($a['category_name']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($a['status'] === 'published'): ?>
                                                    <span class="status-pill published"><i class="bi bi-check-circle-fill"></i> Published</span>
                                                <?php elseif ($a['status'] === 'draft'): ?>
                                                    <span class="status-pill draft"><i class="bi bi-pencil-fill"></i> Draft</span>
                                                <?php else: ?>
                                                    <span class="status-pill archived"><i class="bi bi-archive-fill"></i> Archived</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:var(--muted);font-size:.82rem;white-space:nowrap;">
                                                <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                            </td>
                                            <td class="text-end" style="padding-right:1.4rem;">
                                                <a href="article.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 ADMIN PANE 5: USERS MANAGEMENT
            ══════════════════════════════════════════ -->
            <div class="tab-pane fade" id="admin-users-pane" role="tabpanel">
                <div class="wk-dash-table-wrap">
                    <div class="wk-dash-table-header">
                        <h5><i class="bi bi-people text-danger me-2"></i>Platform Users Registry</h5>
                        <input type="text" id="adminUsersSearch" class="form-control form-control-sm" style="width:220px;" placeholder="Filter users…">
                    </div>
                    <div class="table-responsive">
                        <table class="wk-table" id="adminUsersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Articles Written</th>
                                    <th>Registered Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admin_users && $admin_users->num_rows > 0): ?>
                                    <?php while ($u = $admin_users->fetch_assoc()): ?>
                                        <tr>
                                            <td style="color:var(--muted);font-weight:600;">#<?= $u['id'] ?></td>
                                            <td class="fw-semibold">
                                                <i class="bi bi-person-circle text-accent me-1"></i>
                                                <?= htmlspecialchars($u['username']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                                                    <?= htmlspecialchars(strtoupper($u['role'])) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?= $u['article_count'] ?></td>
                                            <td style="color:var(--muted);font-size:.82rem;">
                                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 ADMIN PANE 6: CATEGORIES MANAGEMENT
            ══════════════════════════════════════════ -->
            <div class="tab-pane fade" id="admin-categories-pane" role="tabpanel">
                <div class="wk-dash-table-wrap">
                    <div class="wk-dash-table-header">
                        <h5><i class="bi bi-tags text-danger me-2"></i>Categories Overview</h5>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> View on Homepage
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="wk-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Published Articles Count</th>
                                    <th class="text-end" style="padding-right:1.4rem;">Browse</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admin_categories && $admin_categories->num_rows > 0): ?>
                                    <?php while ($c = $admin_categories->fetch_assoc()): ?>
                                        <tr>
                                            <td style="color:var(--muted);font-weight:600;">#<?= $c['id'] ?></td>
                                            <td class="fw-semibold">
                                                <i class="bi bi-folder2 text-accent me-1"></i>
                                                <?= htmlspecialchars($c['name']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= $c['article_count'] ?> articles</span>
                                            </td>
                                            <td class="text-end" style="padding-right:1.4rem;">
                                                <a href="category.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    Explore &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /.tab-content -->

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Author articles live search
    const searchInput = document.getElementById("articleTableSearch");
    const table = document.getElementById("authorArticlesTable");
    if (searchInput && table) {
        searchInput.addEventListener("input", function () {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll("tbody tr");
            rows.forEach(function (row) {
                const title = row.querySelector(".article-row-title")?.textContent.toLowerCase() || "";
                row.style.display = title.includes(query) ? "" : "none";
            });
        });
    }

    // 2. Author quick status filters
    const filterAll = document.getElementById("filterAll");
    const filterPublished = document.getElementById("filterPublished");
    const filterDraft = document.getElementById("filterDraft");

    function applyStatusFilter(status, activeBtn) {
        [filterAll, filterPublished, filterDraft].forEach(b => b?.classList.remove("active"));
        if (activeBtn) activeBtn.classList.add("active");

        const rows = table ? table.querySelectorAll("tbody tr") : [];
        rows.forEach(row => {
            if (status === "all" || row.getAttribute("data-status") === status) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    if (filterAll) filterAll.addEventListener("click", () => applyStatusFilter("all", filterAll));
    if (filterPublished) filterPublished.addEventListener("click", () => applyStatusFilter("published", filterPublished));
    if (filterDraft) filterDraft.addEventListener("click", () => applyStatusFilter("draft", filterDraft));

    // 3. Admin table live searches
    function setupTableSearch(inputId, tableId) {
        const inp = document.getElementById(inputId);
        const tbl = document.getElementById(tableId);
        if (inp && tbl) {
            inp.addEventListener("input", function () {
                const q = this.value.toLowerCase().trim();
                tbl.querySelectorAll("tbody tr").forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(q) ? "" : "none";
                });
            });
        }
    }
    setupTableSearch("adminArticlesSearch", "adminArticlesTable");
    setupTableSearch("adminUsersSearch", "adminUsersTable");
});
</script>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>
