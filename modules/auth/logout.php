<?php
require_once '../../config/config.php';

if (isset($_SESSION['user_id'])) {
    log_activity($_SESSION['user_id'], 'User logged out');
    
    session_unset();
    session_destroy();
}

header("Location: " . BASE_URL . "modules/auth/login.php");
exit();

