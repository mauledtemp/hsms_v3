<?php
// Start session and load configuration first
require_once 'config.php';
require_once 'functions.php';

// Now check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


// Auto-create notifications table if it doesn't exist
$conn = getDBConnection();
$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check->num_rows == 0) {
    $conn->query("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        type ENUM('sale', 'low_stock', 'system') NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(200),
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_read (user_id, is_read),
        INDEX idx_created (created_at)
    )");
}

// Track page view activity (only if function exists)
if (function_exists('updateSessionActivity') && function_exists('logActivity')) {
    $current_page = basename($_SERVER['PHP_SELF']);
    updateSessionActivity();
    
    // Only log page views for main pages (not too frequently)
    $important_pages = ['index.php', 'pos.php', 'products.php', 'sales.php', 'inventory.php', 'users.php', 'activity_report.php'];
    if (in_array($current_page, $important_pages)) {
        logActivity($_SESSION['user_id'], 'page_view', 'Viewed ' . $current_page, $_SERVER['REQUEST_URI']);
    }
}
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
                <h2>🔧 <?php echo SITE_ABBR; ?></h2>
                <div class="user-info">
                    <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'; ?><br>
                    <span style="text-transform: capitalize;"><?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'guest'; ?></span>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
                <li><a href="pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">💰 Point of Sale</a></li>
                <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">📦 Products</a></li>
                <li><a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">📥 Inventory</a></li>
                <li><a href="sales_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : ''; ?>">📊 Sales Reports</a></li>
                <li><a href="sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">📈 Sales History</a></li>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">👥 Users</a></li>
                    <li><a href="activity_report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'activity_report.php' ? 'active' : ''; ?>">📊 Activity Report</a></li>
                <?php endif; ?>
                
                <li style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 10px; padding-top: 10px;">
                    <a href="#" onclick="toggleNotifications(); return false;" style="position: relative;">
                        🔔 Notifications
                        <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                    </a>
                </li>
                
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>
        
        <!-- Notification Panel -->
        <div id="notificationPanel" class="notification-panel" style="display: none;">
            <div class="notification-header">
                <h3>Notifications</h3>
                <button onclick="markAllAsRead()" class="btn btn-sm btn-secondary">Mark all read</button>
            </div>
            <div id="notificationList" class="notification-list">
                <p style="text-align: center; padding: 20px; color: var(--secondary);">Loading...</p>
            </div>
        </div>
        
        <main class="main-content">