<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hsms');

// System Configuration
define('SITE_NAME', 'Hardware Store Management System'); // Full name
define('SITE_ABBR', 'HSMS-SYSTEM'); // Short name/logo
define('CURRENCY', 'TZS'); // Currency
// File Upload Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXCEL_TYPES', ['xlsx', 'xls', 'csv']);

// Set PHP settings for file uploads
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
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