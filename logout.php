<?php
require_once 'config.php';
require_once 'functions.php';

if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    endUserSession();
}

logout();
?>