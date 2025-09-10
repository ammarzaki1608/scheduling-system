<?php
// This is the main entry point of the application.
// Its sole purpose is to route logged-in users to their correct dashboard.


// --- INCLUDES ---
// config.php must be included first to define constants.
require_once __DIR__ . "/includes/config.php";
// auth_check.php will handle security. If the user is not logged in,
// it will automatically redirect them to the login page.
require_once __DIR__ . "/includes/auth_check.php";




// --- ROUTING LOGIC ---
// At this point, auth_check.php has confirmed the user is logged in.
// We now check their role and redirect them to the appropriate dashboard.


if (isset($_SESSION['user_role'])) {
    
    if ($_SESSION['user_role'] === 'admin') {
        // User is an admin, send them to the admin dashboard.
        header("Location: " . BASE_URL . "admin/dashboard.php");
        exit;


    } elseif ($_SESSION['user_role'] === 'agent') {
        // User is an agent, send them to the agent dashboard.
        header("Location: " . BASE_URL . "agent/dashboard.php");
        exit;
        
    } else {
        // The user has an unknown or invalid role. For security,
        // redirect them to the login page with an error.
        header("Location: " . BASE_URL . "auth/login.php?error=invalid_role");
        exit;
    }


} else {
    // Failsafe: If for some reason the role is not set in the session,
    // which shouldn't happen after auth_check, redirect to login.
    header("Location: " . BASE_URL . "auth/login.php?error=session_error");
    exit;
}

