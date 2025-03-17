<?php
/**
 * File: includes/auth.php
 * Authentication functions for the Poultry Farm Management System
 * @version 1.0.3
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    die('Direct access to this file is not allowed.');
}

/**
 * Authenticate user
 * 
 * @param PDO $pdo PDO connection object
 * @param string $username Username or email
 * @param string $password Password
 * @return array|bool User data or false on failure
 */
function authenticate_user($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if password needs rehashing
            if (check_password_rehash($user['password'])) {
                $new_hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $user['id']]);
            }
            
            return $user;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication
 * 
 * @param string $redirect URL to redirect to if not authenticated
 * @return void
 */
function require_auth($redirect = 'login.php') {
    if (!is_authenticated()) {
        // Store current URL for redirect after login
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require specific role
 * 
 * @param string|array $roles Role(s) to check
 * @param string $redirect URL to redirect to if not authorized
 * @return void
 */
function require_role($roles, $redirect = 'dashboard.php') {
    require_auth();
    
    if (!has_role($roles)) {
        set_flash_message('error', 'You do not have permission to access this page.');
        header("Location: $redirect");
        exit;
    }
}

/**
 * Logout user
 * 
 * @return void
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session.
    session_destroy();
}
?>

