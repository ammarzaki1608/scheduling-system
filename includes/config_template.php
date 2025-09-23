<?php
// ===================================================================
// CONFIGURATION TEMPLATE
// ===================================================================
// INSTRUCTIONS:
// 1. Copy this file and rename it to 'config.php'.
// 2. Fill in your local database credentials below.
// 3. The 'config.php' file is listed in .gitignore and will NOT be
//    committed to the repository, keeping your credentials safe.
// ===================================================================

// =========================
// ENVIRONMENT SETTINGS
// =========================
// Set the environment to 'development' or 'production'.
// In 'development', all PHP errors will be displayed for debugging.
// In 'production', errors will be hidden from the user for security.
define('ENVIRONMENT', 'development');


// =========================
// ERROR REPORTING
// =========================
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}


// =========================
// DATABASE CREDENTIALS (!!! FILL THESE IN !!!)
// =========================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // <-- Change this to your database username
define('DB_PASS', '');           // <-- Change this to your database password
define('DB_NAME', 'appointment_system');


// =========================
// APP SETTINGS
// =========================
define('APP_NAME', 'Scheduling System');
define('BASE_URL', '/SchedulingSystem/'); // Ensure trailing slash


// =========================
// SECURITY SETTINGS
// =========================
define('SESSION_NAME', 'scheduling_session');


// =========================
// TIMEZONE SETTINGS
// =========================
define('TIMEZONE', 'Asia/Kuala_Lumpur');
date_default_timezone_set(TIMEZONE);

