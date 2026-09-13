<?php
/**
 * Database Connection — StudentHub
 * 
 * This file establishes the connection to the MySQL database.
 * All database credentials are stored here in one place.
 * 
 * -----------------------------------------------
 * HOW TO CHANGE DATABASE CREDENTIALS:
 * If your WAMP MySQL has a different password,
 * change the DB_PASS value below.
 * -----------------------------------------------
 */

// Set default timezone for PHP to match local time (Sri Lanka)
date_default_timezone_set('Asia/Colombo');

// Database configuration
define('DB_HOST', 'localhost');    // Database host (usually 'localhost' for WAMP)
define('DB_USER', 'root');         // Database username (default WAMP user is 'root')
define('DB_PASS', '');             // Database password (default WAMP password is empty '')
define('DB_NAME', 'studenthub');   // Database name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('<div style="text-align:center;padding:50px;font-family:sans-serif;">
        <h2>Database Connection Failed</h2>
        <p>Could not connect to the StudentHub database.</p>
        <p>Please check your database credentials in <code>includes/db.php</code></p>
        <p>Error: ' . $conn->connect_error . '</p>
    </div>');
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");
?>
