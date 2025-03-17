<?php
/**
 * Session initialization file
 * This file must be included BEFORE starting any session
 */

// Set session cookie parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Set session name
session_name(SESSION_NAME);

// Start session
session_start();

