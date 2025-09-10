<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// If the user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    $redirect_url = ($_SESSION['user_role'] === 'admin') ? BASE_URL . "admin/dashboard.php" : BASE_URL . "agent/dashboard.php";
    header("Location: " . $redirect_url);
    exit;
}

// --- Initialize variables ---
$error = '';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Using username as per your last provided file
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // --- Validation ---
    if (empty($username) || empty($password)) {
        $error = "Please enter both your username and password.";
    } else {
        // --- Fetch User from Database ---
        $stmt = $mysqli->prepare("SELECT User_ID, User_Name, Password, Role FROM Users WHERE User_Name = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // ⚠️ SECURITY WARNING: Plaintext password check as per user request.
            // This method is not secure and should not be used in production.
            if ($password === $user['Password']) {
                // --- Login Success ---
                session_regenerate_id(true);

                $_SESSION['user_id']       = $user['User_ID'];
                $_SESSION['user_name']     = $user['User_Name'];
                $_SESSION['user_role']     = strtolower($user['Role']);
                $_SESSION['last_activity'] = time();

                // Redirect user based on their role
                if ($_SESSION['user_role'] === 'admin') {
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "agent/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-center fw-bold mb-4"><?= APP_NAME ?></h2>

                <!-- Display Feedback Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
                <?php elseif (isset($_GET['logout'])): ?>
                    <div class="alert alert-success" role="alert"><i class="bi bi-check-circle-fill"></i> You have been logged out successfully.</div>
                <?php elseif (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning" role="alert"><i class="bi bi-clock-fill"></i> Your session timed out. Please login again.</div>
                <?php elseif (isset($_GET['unauthorized'])): ?>
                    <div class="alert alert-danger" role="alert"><i class="bi bi-shield-lock-fill"></i> Unauthorized access. Please login.</div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
