<?php
// This script securely handles the user logout process.

require_once __DIR__ . "/../includes/config.php";

// It's good practice to start the session to be able to modify it.
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Step 1: Unset all of the session variables.
$_SESSION = [];

// Step 2: Destroy the session cookie on the user's browser.
// This is done by setting a cookie with a name in the past.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Step 3: Finally, destroy the session on the server.
session_destroy();

// Step 4: Redirect to the login page with a success message.
header("Location: " . BASE_URL . "auth/login.php?logout=1");
exit;
