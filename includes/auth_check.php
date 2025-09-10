<?php
require_once __DIR__ . '/config.php';

// Start session with custom name
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// --- LOGIN CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

// --- INACTIVITY TIMEOUT (30 mins) ---
$inactive = 1800; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "auth/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// --- SESSION ID REGENERATION (every 10 mins) ---
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 600) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// --- ROLE-BASED ACCESS CONTROL ---
$currentFile = $_SERVER['PHP_SELF'];

if (strpos($currentFile, '/admin/') !== false && $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php?unauthorized=1");
    exit;
}

if (strpos($currentFile, '/agent/') !== false && $_SESSION['user_role'] !== 'agent') {
    header("Location: " . BASE_URL . "auth/login.php?unauthorized=1");
    exit;
}
