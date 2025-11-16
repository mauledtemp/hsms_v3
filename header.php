<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🔧 HSMS</h2>
                <div class="user-info">
                    <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?><br>
                    <span style="text-transform: capitalize;"><?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?></span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
                <li><a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">💰 Point of Sale</a></li>
                <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">📦 Products</a></li>
                <li><a href="sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">📈 Sales History</a></li>
                
                <?php if (isAdmin()): ?>
                    <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">👥 Users</a></li>
                <?php endif; ?>
                
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">