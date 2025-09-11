<?php
// This header is the main template for all standard pages.
// It sets up the document structure, includes CSS, and builds the sidebar.

// Ensure a session is active.
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
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- CSS LIBRARIES -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles for the layout -->
    <style>
        body { background-color: #f8f9fa; }
        .page-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            flex-shrink: 0;
            background-color: #212529; /* Darker sidebar for a more modern look */
            color: #fff;
        }
        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.6);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .sidebar .nav-link .bi {
            margin-right: 0.8rem;
            font-size: 1.1rem;
            line-height: 1; /* Aligns icons perfectly with text */
        }
        .sidebar .nav-link:hover { 
            color: #fff;
            background-color: rgba(255, 255, 255, 0.05);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd; /* A vibrant blue for the active link */
            border-left-color: #fff;
        }
        .top-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <!-- Sidebar Navigation -->
    <nav class="sidebar p-3 d-flex flex-column">
        <h3 class="text-center mb-4 fw-bold"><?= APP_NAME ?></h3>
        
        <!-- DYNAMIC NAVIGATION AREA -->
        <?php
        if (isset($_SESSION['user_role'])) {
            // This line dynamically includes the correct navigation partial
            // based on the user's role (e.g., _admin_nav.php or _agent_nav.php)
            $nav_file = __DIR__ . "/partials/_" . $_SESSION['user_role'] . "_nav.php";
            if (file_exists($nav_file)) {
                include $nav_file;
            }
        }
        ?>
        
        <!-- Logout button at the bottom -->
        <div class="mt-auto">
             <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <!-- Top Header Bar -->
        <header class="top-header p-3 d-flex justify-content-end align-items-center">
             <span class="navbar-text">
                Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong>
            </span>
        </header>

        <!-- Page Content -->
        <main class="p-4 flex-grow-1">
