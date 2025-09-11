<?php
// This script is the primary "security guard" for the application.
// It is included on every page that requires a user to be logged in.

// Ensure the config is loaded for constants like BASE_URL and SESSION_NAME.
require_once __DIR__ . '/config.php';

// Start session with our custom name if it's not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// --- CHECKPOINT 1: Is the user logged in at all? ---
// If the 'user_id' is not set in the session, they are not logged in.
if (!isset($_SESSION['user_id'])) {
    // Redirect them to the login page and stop the script.
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// --- CHECKPOINT 2: Role-Based Access Control (RBAC) ---
// This check prevents users from accessing pages for other roles (e.g., an agent trying to access an admin page).
$current_url_path = $_SERVER['PHP_SELF'];

// If the URL contains '/admin/' but the user's role is NOT 'admin'...
if (strpos($current_url_path, '/admin/') !== false && $_SESSION['user_role'] !== 'admin') {
    // ...they are not authorized. Redirect them to the login page with an error.
    header("Location: " . BASE_URL . "auth/login.php?unauthorized=1");
    exit;
}

// If the URL contains '/agent/' but the user's role is NOT 'agent'...
if (strpos($current_url_path, '/agent/') !== false && $_SESSION['user_role'] !== 'agent') {
    // ...they are not authorized. Redirect them.
    header("Location: " . BASE_URL . "auth/login.php?unauthorized=1");
    exit;
}

// --- CHECKPOINT 3: Session Security - Inactivity Timeout (Optional but Recommended) ---
// To enable, uncomment the following lines. It will log users out after a period of inactivity.
/*
$inactive_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_duration) {
    // Last activity was too long ago, destroy session and redirect.
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time(); // Update last activity time stamp on each page load.
*/

