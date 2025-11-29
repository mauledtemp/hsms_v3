<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Log the logout activity
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    
    // End the session tracking
    endUserSession();
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>