<?php
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth_check.php"; // Protect session

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= APP_NAME ?> - Home</title>
</head>
<body>
    <h1>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></h1>
    <p>Your role is: <?= htmlspecialchars($_SESSION['role']) ?></p>

    <p>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>admin/dashboard.php">Go to Admin Dashboard</a>
        <?php elseif ($_SESSION['role'] === 'agent'): ?>
            <a href="<?= BASE_URL ?>agent/dashboard.php">Go to Agent Dashboard</a>
        <?php endif; ?>
    </p>

    <p><a href="<?= BASE_URL ?>auth/logout.php">Logout</a></p>
</body>
</html>
