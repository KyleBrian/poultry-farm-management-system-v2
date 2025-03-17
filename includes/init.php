<?php
// This file should be created or modified to handle initialization

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration only once
require_once __DIR__ . '/../config/config.php';

// Other initialization code...
?>

