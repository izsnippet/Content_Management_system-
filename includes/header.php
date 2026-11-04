<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — WikiCMS' : 'WikiCMS — The Collaborative Encyclopedia' ?></title>
    <meta name="description" content="A collaborative knowledge platform for exploring, writing, and improving articles.">
    <!-- Google Fonts: Inter + Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS (with cache buster) -->
    <?php $css_v = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time(); ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $css_v ?>">
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<header class="wk-navbar">
    <div class="wk-container d-flex align-items-center justify-content-between" style="max-width:1300px;width:100%;height:100%;">

        <!-- Brand Logo -->
        <a href="index.php" class="wk-brand">
            <span class="brand-icon"><i class="bi bi-journal-richtext"></i></span>
            <span class="brand-text">Wiki<span>CMS</span></span>
        </a>

        <!-- Search Bar (desktop) -->
        <div class="wk-search-wrap">
            <form action="search.php" method="GET" class="wk-search" role="search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Search articles, topics, keywords…" aria-label="Search">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Nav Links (desktop) -->
        <ul class="wk-nav-links">
            <li>
                <a href="index.php" class="nav-link-item">
                    <i class="bi bi-compass"></i> Browse
                </a>
            </li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="dashboard.php" class="nav-link-item">
                        <i class="bi bi-layout-text-sidebar-reverse"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="create_article.php" class="nav-link-item">
                        <i class="bi bi-pencil-square"></i> Write
                    </a>
                </li>
                <!-- User dropdown -->
                <li class="wk-user-menu">
                    <button class="wk-user-btn" aria-haspopup="true" aria-expanded="false">
                        <span class="wk-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></span>
                        <?= htmlspecialchars($_SESSION['username']) ?>
                        <i class="bi bi-chevron-down" style="font-size:.7rem;opacity:.6;"></i>
                    </button>
                    <div class="wk-dropdown" role="menu">
                        <a href="profile.php" role="menuitem"><i class="bi bi-person-circle"></i> My Profile</a>
                        <a href="dashboard.php" role="menuitem"><i class="bi bi-grid"></i> Dashboard</a>
                        <a href="create_article.php" role="menuitem"><i class="bi bi-plus-circle"></i> New Article</a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-danger" role="menuitem"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="login.php" class="btn-nav-signin"><i class="bi bi-person"></i> Sign In</a></li>
                <li><a href="register.php" class="btn-nav-signup"><i class="bi bi-stars"></i> Get Started</a></li>
            <?php endif; ?>
        </ul>

        <!-- Mobile Toggle -->
        <button class="wk-toggler" id="wk-mobile-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>

    </div><!-- /.wk-container -->
</header>

<!-- Mobile Nav -->
<nav class="wk-nav-mobile" id="wk-nav-mobile" aria-label="Mobile navigation">
    <div class="wk-search-wrap">
        <form action="search.php" method="GET" class="wk-search" role="search">
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Search…" aria-label="Search">
            <button type="submit">Go</button>
        </form>
    </div>
    <a href="index.php"><i class="bi bi-compass"></i> Browse Articles</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php"><i class="bi bi-layout-text-sidebar-reverse"></i> Dashboard</a>
        <a href="create_article.php"><i class="bi bi-pencil-square"></i> Write Article</a>
        <a href="profile.php"><i class="bi bi-person-circle"></i> My Profile (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
        <div class="mobile-divider"></div>
        <a href="logout.php" style="color:var(--danger);"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
    <?php else: ?>
        <div class="mobile-divider"></div>
        <a href="login.php"><i class="bi bi-person"></i> Sign In</a>
        <a href="register.php" class="btn-nav-signup"><i class="bi bi-stars"></i> Get Started</a>
    <?php endif; ?>
</nav>

<!-- Page Content starts here — each page opens its own container -->
