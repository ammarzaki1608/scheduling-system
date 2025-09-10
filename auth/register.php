<?php
// --- Includes ---
// config.php must be first to define constants like BASE_URL
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


// --- Initialize variables ---
$error = "";
$success = "";


// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = "agent"; // All public registrations default to the 'agent' role


    // --- Validation ---
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $error = "Please fill all fields with valid information.";
    } else {
        // Check if email already exists
        $stmt = $mysqli->prepare("SELECT User_ID FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();


        if ($stmt->num_rows > 0) {
            $error = "An account with this email address already exists.";
        } else {
            // --- Insert into Database ---
            // ⚠️ SECURITY RISK: Storing password in plaintext as per request.
            // In a real application, you MUST use password_hash().
            // Example: $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


            $stmt_insert = $mysqli->prepare("INSERT INTO users (User_Name, Email, Password, Role, Created_At) VALUES (?, ?, ?, ?, NOW())");
            // Note: The password is being stored as a simple string.
            $stmt_insert->bind_param("ssss", $name, $email, $password, $role);


            if ($stmt_insert->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Error registering user. Please try again later.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}


// --- Page Title ---
$pageTitle = "Register";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= $pageTitle ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
                    <p class="text-muted">Create a New Agent Account</p>
                </div>


                <!-- Display success or error messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>


                <?php if (empty($success)): // Hide form on success ?>
                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold">Register</button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="text-muted">
                        <?php if (!empty($success)): ?>
                            <a href="<?= BASE_URL ?>auth/login.php">Proceed to Login</a>
                        <?php else: ?>
                            Already have an account? <a href="<?= BASE_URL ?>auth/login.php">Login here</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

