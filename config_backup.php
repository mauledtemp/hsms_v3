<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'kiwanjachapco_yahya_store');
define('DB_PASS', '@1234Yahya_store');
define('DB_NAME', 'kiwanjachapco_darajani_store');

// System Configuration
define('SITE_NAME', 'Hardware Store Management System'); // Full name
define('SITE_ABBR', 'DARAJANI-HSMS'); // Short name/logo
define('CURRENCY', 'TZS'); // Currency
// Session Configuration
ini_set('session.cookie_httponly', 1);
session_start();

// Database Connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>