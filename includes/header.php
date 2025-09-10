<?php
// This header is included on every page. It sets up the document structure,
// includes necessary CSS, and builds the sidebar and top navigation bar.

// Ensure a session is active.
if (session_status() === PHP_SESSION_NONE) {
    // It's good practice to also include the config here to ensure session name is set,
    // though auth_check.php will likely handle this.
    require_once __DIR__ . '/config.php';
    session_name(SESSION_NAME);
    session_start();
}

// Helper function to check if a navigation link should be marked as 'active'
function is_nav_active($link_file_name) {
    // basename() gets the filename from a path (e.g., /admin/dashboard.php -> dashboard.php)
    // $_SERVER['PHP_SELF'] is the path of the currently executing script.
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
    <!-- The page title is now dynamic. Set the $pageTitle variable before including the header. -->
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    
    <!-- CSS LIBRARIES -->
    <!-- Bootstrap 5 CSS from a CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons from a CDN for nice icons in the navigation -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles for the layout -->
    <style>
        body { background-color: #f8f9fa; }
        .page-wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            flex-shrink: 0;
            background-color: #343a40;
            color: #fff;
        }
        .content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link .bi {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }
        .top-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

<div class="page-wrapper">
    <!-- Sidebar Navigation -->
    <nav class="sidebar p-3 d-flex flex-column">
        <h3 class="text-center mb-4"><?= APP_NAME ?></h3>
        <ul class="nav flex-column flex-grow-1">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link <?= is_nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>admin/dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= is_nav_active('agents.php') ?>" href="<?= BASE_URL ?>admin/agents.php"><i class="bi bi-people-fill"></i>Manage Users</a></li>
                    <!-- Add other admin links here as they are created -->
                    
                <?php elseif ($_SESSION['user_role'] === 'agent'): ?>
                    <li class="nav-item"><a class="nav-link <?= is_nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>agent/dashboard.php"><i class="bi bi-person-workspace"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= is_nav_active('appointments.php') ?>" href="#"><i class="bi bi-calendar-week-fill"></i>My Appointments</a></li>
                    <li class="nav-item"><a class="nav-link <?= is_nav_active('profile.php') ?>" href="#"><i class="bi bi-person-circle"></i>My Profile</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
        <!-- Logout button at the bottom of the sidebar -->
        <div class="mt-auto">
             <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <!-- Top Header Bar -->
        <header class="top-header p-3 d-flex justify-content-end">
             <span class="navbar-text">
                Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></strong>
            </span>
        </header>

        <!-- Page Content (this is where individual page content will go) -->
        <main class="p-4 flex-grow-1">
