<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
<header>
    <h1><?php echo APP_NAME; ?></h1>
    <nav>
        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>admin/teams.php">Teams</a>
                <a href="<?php echo BASE_URL; ?>admin/appointments_admin.php">Appointments</a>
                <a href="<?php echo BASE_URL; ?>admin/reports.php">Reports</a>
            <?php elseif ($_SESSION['role'] === 'Agent'): ?>
                <a href="<?php echo BASE_URL; ?>agent/dashboard.php">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>agent/appointments.php">Appointments</a>
                <a href="<?php echo BASE_URL; ?>agent/profile.php">Profile</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>
<main>
