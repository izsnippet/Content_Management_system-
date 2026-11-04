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
    <title>Admin Portal - Wiki CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background-color: #f8f9fa; }
        .sidebar { min-height: calc(100vh - 56px); background-color: #212529; }
        .sidebar .nav-link { color: #adb5bd; padding: 0.75rem 1rem; border-radius: 0.375rem; margin-bottom: 0.25rem; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: #343a40; }
        .sidebar .nav-link.active { background-color: #0d6efd; }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container-fluid px-3">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-shield-lock me-1"></i> Wiki CMS Admin
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="../index.php" target="_blank" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Live Site
                </a>
                <span class="text-secondary small d-none d-md-inline">|</span>
                <span class="text-light small d-none d-md-inline"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Sign Out
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar py-3 px-2">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" href="users.php">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'articles.php' ? 'active' : '' ?>" href="articles.php">
                            <i class="bi bi-journal-text me-2"></i> Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'comments.php' ? 'active' : '' ?>" href="comments.php">
                            <i class="bi bi-chat-left-dots me-2"></i> Comments
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main Workspace Container -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">