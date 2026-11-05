<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Center - Wiki CMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            min-height: 100vh;
        }
        .admin-topbar {
            background: #0f172a;
            border-bottom: 1px solid #1e293b;
        }
        .admin-sidebar {
            min-height: calc(100vh - 61px);
            background: #1e293b;
            border-right: 1px solid #334155;
        }
        .admin-sidebar .nav-link {
            color: #94a3b8;
            font-weight: 500;
            padding: 0.8rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.35rem;
            transition: all 0.2s ease;
        }
        .admin-sidebar .nav-link:hover {
            color: #f8fafc;
            background-color: rgba(255, 255, 255, 0.05);
        }
        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .card {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark admin-topbar sticky-top py-2 px-3">
        <div class="container-fluid px-0">
            <span class="navbar-brand fw-bold fs-5 mb-0 d-flex align-items-center">
                <i class="bi bi-shield-shaded text-primary me-2 fs-4"></i> Master Control Panel
            </span>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-dark border border-secondary text-light px-3 py-2">
                    <i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Administrator') ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm px-3">
                    <i class="bi bi-power me-1"></i> Sign Out
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar py-4 px-3">
                <div class="small text-uppercase text-secondary fw-bold px-2 mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">Oversight</div>
                <ul class="nav flex-column mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-grid-1x2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="users.php">
                            <i class="bi bi-person-gear me-2"></i> User Directory & Bans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'articles.php' ? 'active' : '' ?>" href="articles.php">
                            <i class="bi bi-shield-check me-2"></i> Content Moderation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                            <i class="bi bi-tags me-2"></i> Taxonomy Manager
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'comments.php' ? 'active' : '' ?>" href="comments.php">
                            <i class="bi bi-chat-square-dots me-2"></i> Comment Moderation
                        </a>
                    </li>
                </ul>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
