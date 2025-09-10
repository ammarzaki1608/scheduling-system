<?php
require_once "../includes/auth_check.php";
require_once "../includes/db_connect.php";

$agentId = (int) $_SESSION['user_id'];

// Fetch appointment stats for this agent
$upcoming = $mysqli->query("SELECT COUNT(*) AS cnt FROM Appointments WHERE Agent_ID = $agentId AND Start_At >= NOW()")->fetch_assoc()['cnt'] ?? 0;
$completed = $mysqli->query("SELECT COUNT(*) AS cnt FROM Appointments WHERE Agent_ID = $agentId AND Status = 'Completed'")->fetch_assoc()['cnt'] ?? 0;
$missed = $mysqli->query("SELECT COUNT(*) AS cnt FROM Appointments WHERE Agent_ID = $agentId AND Status = 'Missed'")->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agent Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (Agent)</h1>
    <p>You are logged in as <strong><?= htmlspecialchars($_SESSION['role']) ?></strong>.</p>

    <div class="dashboard-cards">
        <div class="card">Upcoming Appointments<br><span><?= $upcoming ?></span></div>
        <div class="card">Completed<br><span><?= $completed ?></span></div>
        <div class="card">Missed<br><span><?= $missed ?></span></div>
    </div>

    <nav class="nav-links">
        <ul>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="appointment_add.php">Add Appointment</a></li>
            <li><a href="profile.php">My Profile</a></li>
        </ul>
    </nav>

    <p><a href="../auth/logout.php" class="btn-logout">Logout</a></p>
</div>
</body>
</html>
