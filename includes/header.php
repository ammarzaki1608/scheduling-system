<?php
// This header is the main template for all standard pages.
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/config.php';
    session_name(SESSION_NAME);
    session_start();
}
// Helper function to highlight the active navigation link.
function is_nav_active($link_file_name) {
    if (basename($_SERVER['PHP_SELF']) == $link_file_name) {
        return 'active';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Our New Master Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="page-wrapper">
    <!-- Redesigned Sidebar -->
    <nav class="sidebar p-3 d-flex flex-column">
        <div class="text-center mb-4">
             <a class="sidebar-brand d-flex align-items-center justify-content-center text-decoration-none" href="#">
                 <i class="bi bi-calendar-check-fill fs-2 text-primary"></i>
                 <span class="sidebar-brand-text ms-2 h4 mb-0"><?= APP_NAME ?></span>
             </a>
        </div>
        
        <!-- Dynamic Navigation Area (uses the same partials) -->
        <?php
        if (isset($_SESSION['user_role'])) {
            $nav_file = __DIR__ . "/partials/_" . $_SESSION['user_role'] . "_nav.php";
            if (file_exists($nav_file)) {
                include $nav_file;
            }
        }
        ?>
        
        <div class="mt-auto">
             <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <header class="top-header p-3 d-flex justify-content-end align-items-center">
             <span class="navbar-text">Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong></span>
        </header>
        <main class="p-4 flex-grow-1">

