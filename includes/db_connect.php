<?php
// includes/db_connect.php

require_once __DIR__ . '/config.php';

// Create MySQLi connection
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($mysqli->connect_errno) {
    error_log("❌ Database connection failed: " . $mysqli->connect_error);
    die("❌ Database connection failed. Please contact the administrator.");
}

// Set charset
if (!$mysqli->set_charset("utf8mb4")) {
    error_log("⚠️ Error loading character set utf8mb4: " . $mysqli->error);
}
