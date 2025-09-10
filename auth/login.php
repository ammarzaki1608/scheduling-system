<?php
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = $mysqli->prepare("SELECT User_ID, User_Name, Password, Role FROM users WHERE User_Name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // ⚠️ Plaintext password check (not secure, but as per your request)
            if ($password === $user['Password']) {
                $_SESSION['user_id']    = $user['User_ID'];
                $_SESSION['user_name']  = $user['User_Name'];
                $_SESSION['role']       = strtolower($user['Role']);
                $_SESSION['last_activity'] = time();
                $_SESSION['created']    = time();
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

                // Redirect by role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                } elseif ($_SESSION['role'] === 'agent') {
                    header("Location: " . BASE_URL . "agent/dashboard.php");
                } else {
                    $error = "Invalid role assigned.";
                }
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    } else {
        $error = "Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= APP_NAME ?> - Login</title>
</head>
<body>
    <h2>Login</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif (isset($_GET['logout'])): ?>
        <p style="color:green;">You have been logged out successfully.</p>
    <?php elseif (isset($_GET['timeout'])): ?>
        <p style="color:orange;">Your session timed out. Please login again.</p>
    <?php elseif (isset($_GET['unauthorized'])): ?>
        <p style="color:red;">Unauthorized access. Please login again.</p>
    <?php endif; ?>

    <form method="post">
        <label>Username:</label>
        <input type="text" name="username" required><br><br>

        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
