<?php
/**
 * Common functions for Poultry Farm Management System
 */

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format date according to system settings
 * 
 * @param string $date Date to format
 * @param string $format Format to use (default: system date format)
 * @return string Formatted date
 */
function format_date($date, $format = null) {
    if (empty($date)) return '';
    
    if ($format === null) {
        $format = defined('DATE_FORMAT') ? DATE_FORMAT : 'Y-m-d';
    }
    
    return date($format, strtotime($date));
}

/**
 * Format currency according to system settings
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency symbol (default: system currency)
 * @return string Formatted currency
 */
function format_currency($amount, $currency = null) {
    if ($currency === null) {
        $currency = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '$';
    }
    
    return $currency . number_format($amount, 2);
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool True if user has role, false otherwise
 */
function has_role($roles) {
    if (!is_logged_in()) return false;
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message and clear it
 * 
 * @return array|null Flash message or null if none
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    
    return null;
}

/**
 * Display flash message
 * 
 * @return string HTML for flash message
 */
function display_flash_message() {
    $message = get_flash_message();
    
    if (!$message) {
        return '';
    }
    
    $type_class = '';
    switch ($message['type']) {
        case 'success':
            $type_class = 'bg-green-100 border-green-400 text-green-700';
            break;
        case 'error':
            $type_class = 'bg-red-100 border-red-400 text-red-700';
            break;
        case 'warning':
            $type_class = 'bg-yellow-100 border-yellow-400 text-yellow-700';
            break;
        case 'info':
        default:
            $type_class = 'bg-blue-100 border-blue-400 text-blue-700';
            break;
    }
    
    return '<div class="' . $type_class . ' px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">' . htmlspecialchars($message['message']) . '</span>
    </div>';
}

/**
 * Log activity
 * 
 * @param int $user_id User ID
 * @param string $activity_type Activity type
 * @param string $description Activity description
 * @return bool True on success, false on failure
 */
function logActivity($user_id, $activity_type, $description) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, activity_type, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $user_id,
            $activity_type,
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 * 
 * @param int $user_id User ID
 * @return array|bool User data or false on failure
 */
function get_user($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get User Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate pagination links
 * 
 * @param int $total_items Total number of items
 * @param int $items_per_page Items per page
 * @param int $current_page Current page
 * @param string $url_pattern URL pattern with :page placeholder
 * @return string HTML pagination links
 */
function generate_pagination($total_items, $items_per_page, $current_page, $url_pattern) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="flex items-center justify-between mt-6" aria-label="Pagination">';
    $html .= '<div class="flex-1 flex justify-between">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = str_replace(':page', $current_page - 1, $url_pattern);
        $html .= '<a href="' . $prev_url . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">Previous</span>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_url = str_replace(':page', $current_page + 1, $url_pattern);
        $html .= '<a href="' . $next_url . '" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</a>';
    } else {
        $html .= '<span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">Next</span>';
    }
    
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Create database tables if they don't exist
 * 
 * @return bool True on success, false on failure
 */
function create_database_tables() {
    global $pdo;
    
    try {
        // Users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT(11) NOT NULL AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'manager', 'user') NOT NULL DEFAULT 'user',
                phone VARCHAR(20) DEFAULT NULL,
                status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
                last_login DATETIME DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY username (username),
                UNIQUE KEY email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Check if admin user exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $admin_exists = $stmt->fetchColumn();
        
        // Create admin user if it doesn't exist
        if (!$admin_exists) {
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("
                INSERT INTO users (username, password, email, full_name, role, status)
                VALUES ('admin', '$admin_password', 'admin@example.com', 'System Administrator', 'admin', 'active')
            ");
        }
        
        // Flocks table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS flocks (
                id INT(11) NOT NULL AUTO_INCREMENT,
                flock_id VARCHAR(20) NOT NULL,
                breed VARCHAR(50) NOT NULL,
                batch_name VARCHAR(50) NOT NULL,
                quantity INT(11) NOT NULL,
                acquisition_date DATE NOT NULL,
                acquisition_age INT(11) NOT NULL DEFAULT 0,
                source VARCHAR(100) DEFAULT NULL,
                cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                notes TEXT DEFAULT NULL,
                status ENUM('active', 'sold', 'culled', 'completed') NOT NULL DEFAULT 'active',
                created_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY flock_id (flock_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Egg Production table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS egg_production (
                id INT(11) NOT NULL AUTO_INCREMENT,
                flock_id INT(11) NOT NULL,
                collection_date DATE NOT NULL,
                total_eggs INT(11) NOT NULL DEFAULT 0,
                broken_eggs INT(11) NOT NULL DEFAULT 0,
                small_eggs INT(11) NOT NULL DEFAULT 0,
                medium_eggs INT(11) NOT NULL DEFAULT 0,
                large_eggs INT(11) NOT NULL DEFAULT 0,
                xlarge_eggs INT(11) NOT NULL DEFAULT 0,
                notes TEXT DEFAULT NULL,
                collected_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY flock_date (flock_id, collection_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Feed Inventory table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS feed_inventory (
                id INT(11) NOT NULL AUTO_INCREMENT,
                feed_type VARCHAR(50) NOT NULL,
                batch_number VARCHAR(50) DEFAULT NULL,
                quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                unit ENUM('kg', 'lb', 'ton') NOT NULL DEFAULT 'kg',
                purchase_date DATE NOT NULL,
                expiry_date DATE DEFAULT NULL,
                cost_per_unit DECIMAL(10,2) NOT NULL,
                supplier VARCHAR(100) DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                created_by INT(11) DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Activity Log table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_log (
                id INT(11) NOT NULL AUTO_INCREMENT,
                user_id INT(11) DEFAULT NULL,
                activity_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        return true;
    } catch (PDOException $e) {
        error_log("Database Setup Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if database tables exist
 * 
 * @return bool True if tables exist, false otherwise
 */
function check_database_tables() {
    global $pdo;
    
    try {
        $tables = ['users', 'flocks', 'egg_production', 'feed_inventory', 'activity_log'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                return false;
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Database Check Error: " . $e->getMessage());
        return false;
    }
}

// Create database tables if they don't exist
if (!check_database_tables()) {
    create_database_tables();
}

