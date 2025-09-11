<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";

// --- Session Handling ---
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// If the user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    $redirect_url = ($_SESSION['user_role'] === 'admin') ? 'admin/dashboard.php' : 'agent/dashboard.php';
    header("Location: " . BASE_URL . $redirect_url);
    exit;
}

// --- Initialize variable ---
$error = '';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        // Fetch user by email
        $stmt = $mysqli->prepare("SELECT User_ID, User_Name, Password, Role FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // SECURITY: Verify the submitted password against the hash in the database.
            if (password_verify($password, $user['Password'])) {
                
                // Regenerate session ID for security upon successful login
                session_regenerate_id(true);

                // Set session variables consistently.
                // strtolower() guarantees the role is always lowercase ('admin' or 'agent'),
                // which prevents case-sensitivity bugs with our template system.
                $_SESSION['user_id']    = $user['User_ID'];
                $_SESSION['user_name']  = $user['User_Name'];
                $_SESSION['user_role']  = strtolower($user['Role']);

                // Redirect based on the now-standardized role
                if ($_SESSION['user_role'] === 'admin') {
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                } else {
                    header("Location: " . BASE_URL . "agent/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error = "Please enter both email and password.";
    }
}

// --- Page Setup ---
$pageTitle = "Login";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .auth-card { max-width: 450px; }
    </style>
</head>
<body>
    <div class="d-flex align-items-center justify-content-center min-vh-100 px-3">
        <div class="card border-0 shadow-sm rounded-4 w-100 auth-card">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold"><?= APP_NAME ?></h2>
                    <p class="text-muted">Please sign in to continue</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success" role="alert">You have been logged out successfully.</div>
                <?php endif; ?>
                <?php if (isset($_GET['unauthorized'])): ?>
                     <div class="alert alert-warning" role="alert">You are not authorized to view that page.</div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold">Login</button>
                    </div>
                </form>
                 <div class="text-center mt-4">
                    <p class="text-muted">Don't have an account? <a href="<?= BASE_URL ?>auth/register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

