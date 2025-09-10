<?php
require_once "../includes/auth_check.php"; // session & role check
require_once "../includes/db_connect.php";

// Fetch stats safely
$totalTeams = $mysqli->query("SELECT COUNT(*) AS cnt FROM Teams")->fetch_assoc()['cnt'] ?? 0;
$totalPods = $mysqli->query("SELECT COUNT(*) AS cnt FROM Pods")->fetch_assoc()['cnt'] ?? 0;
$totalAgents = $mysqli->query("SELECT COUNT(*) AS cnt FROM Users WHERE Role='agent'")->fetch_assoc()['cnt'] ?? 0;
$totalAppointments = $mysqli->query("SELECT COUNT(*) AS cnt FROM Appointments")->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (Admin)</h1>
    <p>You are logged in as <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>.</p>

    <div class="dashboard-cards">
        <div class="card">Total Teams<br><span><?= $totalTeams ?></span></div>
        <div class="card">Total Pods<br><span><?= $totalPods ?></span></div>
        <div class="card">Total Agents<br><span><?= $totalAgents ?></span></div>
        <div class="card">Total Appointments<br><span><?= $totalAppointments ?></span></div>
    </div>

    <nav class="nav-links">
        <ul>
            <li><a href="teams.php">Manage Teams</a></li>
            <li><a href="pods.php">Manage Pods</a></li>
            <li><a href="agents.php">Manage Agents</a></li>
            <li><a href="appointments_admin.php">View Appointments</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </nav>

    <p><a href="../auth/logout.php" class="btn-logout">Logout</a></p>
</div>
</body>
</html>
