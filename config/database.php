<?php
/**
 * Database connection file for Poultry Farm Management System
 */

// Include configuration
require_once 'config.php';

try {
    // Create PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display error message
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Execute a query and return all results
 * 
 * @param PDO $pdo PDO connection
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Query results
 */
function db_query($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Query Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute a query and return a single value
 * 
 * @param PDO $pdo PDO connection
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return mixed Query result or false on failure
 */
function db_query_value($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a query and return the last inserted ID
 * 
 * @param PDO $pdo PDO connection
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return int|bool Last inserted ID or false on failure
 */
function db_insert($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Database Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a query and return the number of affected rows
 * 
 * @param PDO $pdo PDO connection
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return int|bool Number of affected rows or false on failure
 */
function db_execute($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Database Execute Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Test database connection
 * 
 * @return bool True if connection is successful, false otherwise
 */
function test_db_connection() {
    global $pdo;
    
    try {
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

