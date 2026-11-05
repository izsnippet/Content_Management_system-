<?php
// admin/includes/admin_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict check: must be logged in and role must be admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=unauthorized_admin_access");
    exit();
}