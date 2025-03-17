<?php
// Start session
session_start();

// Include configuration and functions
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/functions.php';

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();

