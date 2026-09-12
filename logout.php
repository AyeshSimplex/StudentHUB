<?php
/**
 * Logout — StudentHub
 * Destroys the session and redirects to home page.
 */
require_once 'includes/functions.php';

// Destroy session
session_unset();
session_destroy();

// Start new session for flash message
session_start();
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect to home
header('Location: index.php');
exit();
?>
