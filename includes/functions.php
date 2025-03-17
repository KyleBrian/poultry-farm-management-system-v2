<?php
/**
 * Common functions for the Poultry Farm Management System
 */

// Include configuration
require_once __DIR__ . '/../config/config.php';

/**
 * Database connection function
 * @return PDO Database connection object
 */
function db_connect() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Sanitize user input
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate a CSRF token
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
        return false;
    }
    return true;
}

/**
 * Hash a password
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password . AUTH_SALT, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * Verify a password
 * @param string $password Password to verify
 * @param string $hash Hash to verify against
 * @return bool True if password is valid
 */
function verify_password($password, $hash) {
    return password_verify($password . AUTH_SALT, $hash);
}

/**
 * Generate a random token
 * @param int $length Length of token
 * @return string Random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Redirect to a URL
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 * RENAMED from get_current_user() to avoid conflict with PHP built-in function
 * @return array|null User data or null if not logged in
 */
function get_system_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = db_connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if user has a specific role
 * @param string $role Role to check
 * @return bool True if user has role
 */
function has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user = get_system_user();
    return $user['role'] === $role;
}

/**
 * Check if user has permission
 * @param string $permission Permission to check
 * @return bool True if user has permission
 */
function has_permission($permission) {
    if (!is_logged_in()) {
        return false;
    }
    
    $db = db_connect();
    $stmt = $db->prepare("
        SELECT p.name 
        FROM permissions p
        JOIN role_permissions rp ON p.id = rp.permission_id
        JOIN users u ON u.role_id = rp.role_id
        WHERE u.id = ? AND p.name = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $permission]);
    return $stmt->rowCount() > 0;
}

/**
 * Log an activity
 * @param string $action Action performed
 * @param string $description Description of action
 * @param int $user_id User ID (optional)
 * @return void
 */
function log_activity($action, $description, $user_id = null) {
    if (!ACTIVITY_LOG_ENABLED) {
        return;
    }
    
    if ($user_id === null && is_logged_in()) {
        $user_id = $_SESSION['user_id'];
    }
    
    $db = db_connect();
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $action,
        $description,
        $_SERVER['REMOTE_ADDR']
    ]);
}

/**
 * Format date
 * @param string $date Date to format
 * @param string $format Format to use (optional)
 * @return string Formatted date
 */
function format_date($date, $format = null) {
    if ($format === null) {
        $format = DATE_FORMAT;
    }
    return date($format, strtotime($date));
}

/**
 * Format currency
 * @param float $amount Amount to format
 * @param string $currency Currency code (optional)
 * @return string Formatted currency
 */
function format_currency($amount, $currency = null) {
    if ($currency === null) {
        $currency = DEFAULT_CURRENCY_SYMBOL;
    }
    return $currency . number_format($amount, 2);
}

/**
 * Get pagination
 * @param int $total Total number of items
 * @param int $page Current page
 * @param int $per_page Items per page (optional)
 * @return array Pagination data
 */
function get_pagination($total, $page, $per_page = null) {
    if ($per_page === null) {
        $per_page = ITEMS_PER_PAGE;
    }
    
    $total_pages = ceil($total / $per_page);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;
    
    return [
        'total' => $total,
        'per_page' => $per_page,
        'current_page' => $page,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}

/**
 * Get pagination links
 * @param array $pagination Pagination data
 * @param string $url Base URL
 * @return string Pagination links HTML
 */
function get_pagination_links($pagination, $url) {
    $links = '<div class="pagination">';
    
    // Previous link
    if ($pagination['current_page'] > 1) {
        $links .= '<a href="' . $url . '?page=' . ($pagination['current_page'] - 1) . '" class="page-link">&laquo; Previous</a>';
    } else {
        $links .= '<span class="page-link disabled">&laquo; Previous</span>';
    }
    
    // Page links
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $links .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $links .= '<a href="' . $url . '?page=' . $i . '" class="page-link">' . $i . '</a>';
        }
    }
    
    // Next link
    if ($pagination['current_page'] < $pagination['total_pages']) {
        $links .= '<a href="' . $url . '?page=' . ($pagination['current_page'] + 1) . '" class="page-link">Next &raquo;</a>';
    } else {
        $links .= '<span class="page-link disabled">Next &raquo;</span>';
    }
    
    $links .= '</div>';
    return $links;
}

/**
 * Upload a file
 * @param array $file File data from $_FILES
 * @param string $destination Destination directory
 * @param array $allowed_types Allowed file types (optional)
 * @param int $max_size Maximum file size in bytes (optional)
 * @return string|bool Filename if successful, false if failed
 */
function upload_file($file, $destination, $allowed_types = null, $max_size = null) {
    if ($allowed_types === null) {
        $allowed_types = explode(',', ALLOWED_FILE_TYPES);
    }
    
    if ($max_size === null) {
        $max_size = MAX_FILE_SIZE;
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return false;
    }
    
    // Check file type
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_types)) {
        return false;
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $extension;
    $filepath = rtrim($destination, '/') . '/' . $filename;
    
    // Create directory if it doesn't exist
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    return $filename;
}

/**
 * Delete a file
 * @param string $filepath Path to file
 * @return bool True if successful
 */
function delete_file($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Get file URL
 * @param string $filename Filename
 * @param string $directory Directory
 * @return string File URL
 */
function get_file_url($filename, $directory = 'uploads') {
    return APP_URL . '/' . $directory . '/' . $filename;
}

/**
 * Send an email
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Additional headers (optional)
 * @return bool True if successful
 */
function send_email($to, $subject, $message, $headers = []) {
    $default_headers = [
        'From' => MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    $headers = array_merge($default_headers, $headers);
    $header_string = '';
    
    foreach ($headers as $key => $value) {
        $header_string .= $key . ': ' . $value . "\r\n";
    }
    
    return mail($to, $subject, $message, $header_string);
}

/**
 * Generate a random password
 * @param int $length Password length
 * @return string Random password
 */
function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Get system settings
 * @param string $key Setting key (optional)
 * @return mixed Setting value or all settings
 */
function get_setting($key = null) {
    $db = db_connect();
    
    if ($key !== null) {
        $stmt = $db->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    }
    
    $stmt = $db->query("SELECT `key`, value FROM settings");
    $settings = [];
    
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
    
    return $settings;
}

/**
 * Update system setting
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool True if successful
 */
function update_setting($key, $value) {
    $db = db_connect();
    $stmt = $db->prepare("
        INSERT INTO settings (`key`, value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Log an error
 * @param string $message Error message
 * @param string $level Error level
 * @return void
 */
function log_error($message, $level = 'ERROR') {
    if (!LOG_ERRORS) {
        return;
    }
    
    $log_message = date('Y-m-d H:i:s') . ' [' . $level . '] ' . $message . PHP_EOL;
    error_log($log_message, 3, ERROR_LOG_FILE);
}

/**
 * Get client IP address
 * @return string IP address
 */
function get_client_ip() {
    $ip = '0.0.0.0';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Check if request is AJAX
 * @return bool True if request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current URL
 * @return string Current URL
 */
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}

/**
 * Get base URL
 * @return string Base URL
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

/**
 * Calculate age from date of birth
 * @param string $dob Date of birth
 * @return int Age
 */
function calculate_age($dob) {
    $today = new DateTime();
    $birthdate = new DateTime($dob);
    $interval = $today->diff($birthdate);
    return $interval->y;
}

/**
 * Format phone number
 * @param string $phone Phone number
 * @return string Formatted phone number
 */
function format_phone($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length
    if (strlen($phone) === 10) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
    }
    
    return $phone;
}

/**
 * Truncate text
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add (optional)
 * @return string Truncated text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}

/**
 * Convert newlines to <br> tags
 * @param string $text Text to convert
 * @return string Converted text
 */
function nl2br_safe($text) {
    return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * Get file extension
 * @param string $filename Filename
 * @return string File extension
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is an image
 * @param string $filename Filename
 * @return bool True if file is an image
 */
function is_image_file($filename) {
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    return in_array(get_file_extension($filename), $image_extensions);
}

/**
 * Generate a slug from a string
 * @param string $string String to convert
 * @return string Slug
 */
function generate_slug($string) {
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($string)));
    // Remove leading/trailing hyphens
    return trim($slug, '-');
}

