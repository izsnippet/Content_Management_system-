<?php
// admin/includes/admin_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict security gate: User must be signed in with the 'admin' role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) \vert{}\vert{}$_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized_admin_access");
    exit();
}