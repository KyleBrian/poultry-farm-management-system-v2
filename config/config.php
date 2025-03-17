<?php
/**
 * Configuration file for Poultry Farm Management System
 * Contains system-wide settings and configuration options
 * 
 * IMPORTANT: This file must be included BEFORE starting any session
 */

// Default timezone
date_default_timezone_set('UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application settings
define('APP_NAME', 'Poultry Farm Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/poultry-farm-management-system');
define('APP_TIMEZONE', 'UTC');
define('APP_LANGUAGE', 'en');
define('APP_DEBUG', true);

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'poultry_farm');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Session settings
define('SESSION_NAME', 'poultry_farm_session');
define('SESSION_LIFETIME', 86400); // 24 hours
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTP_ONLY', true);

// Security settings
define('HASH_COST', 10); // For password hashing
define('AUTH_SALT', 'poultry_farm_salt_2025');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Email settings
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'info@example.com');
define('MAIL_PASSWORD', 'your_password');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'info@example.com');
define('MAIL_FROM_NAME', 'Poultry Farm Management System');

// Pagination settings
define('ITEMS_PER_PAGE', 20);

// Date and time formats
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// System settings
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', ROOT_PATH . '/logs/error.log');
define('ACTIVITY_LOG_ENABLED', true);
define('BACKUP_PATH', ROOT_PATH . '/backups');
define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Default values
define('DEFAULT_CURRENCY', 'USD');
define('DEFAULT_CURRENCY_SYMBOL', '$');
define('DEFAULT_TAX_RATE', 10);

// Feature flags
define('ENABLE_SMS_NOTIFICATIONS', false);
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_REPORTS_EXPORT', true);
define('ENABLE_API_ACCESS', false);

// Upload paths
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('LOG_PATH', ROOT_PATH . '/logs/');

// Create directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}

// System settings
define('SYSTEM_EMAIL', 'info@pfms.com');
define('ADMIN_EMAIL', 'admin@pfms.com');

// API keys (replace with your actual keys)
define('GOOGLE_MAPS_API_KEY', 'your-google-maps-api-key');
define('WEATHER_API_KEY', 'your-weather-api-key');

// Security settings
define('TOKEN_EXPIRY', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes

// DO NOT set session ini settings here - they must be set before session_start()
// These settings should be in a separate file that's included before session_start()

