<?php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

$message = '';

// Test creating a notification
if (isset($_GET['test'])) {
    $conn = getDBConnection();
    
    // Check if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    
    if ($table_check->num_rows == 0) {
        $message .= "❌ Notifications table doesn't exist. Creating it now...<br>";
        
        $create_result = $conn->query("CREATE TABLE notifications (
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
        
        if ($create_result) {
            $message .= "✅ Notifications table created successfully!<br>";
        } else {
            $message .= "❌ Error creating table: " . $conn->error . "<br>";
        }
    } else {
        $message .= "✅ Notifications table exists<br>";
    }
    
    // Try to insert a test notification
    $test_title = "Test Notification " . date('H:i:s');
    $test_message = "This is a test notification created at " . date('H:i:s');
    $test_link = "index.php";
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $test_title, $test_message, $test_link);
    
    if ($stmt->execute()) {
        $message .= "✅ Test notification created! ID: " . $conn->insert_id . "<br>";
    } else {
        $message .= "❌ Error creating notification: " . $stmt->error . "<br>";
    }
    
    // Check if notification exists
    $check = $conn->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $notif = $check->fetch_assoc();
        $message .= "✅ Last notification in DB: " . htmlspecialchars($notif['title']) . "<br>";
    } else {
        $message .= "❌ No notifications found in database<br>";
    }
    
    // Check notification count
    $count = $conn->query("SELECT COUNT(*) as total FROM notifications");
    if ($count) {
        $total = $count->fetch_assoc()['total'];
        $message .= "📊 Total notifications in DB: {$total}<br>";
    }
}

// Check notifications.php file
if (isset($_GET['check_api'])) {
    if (file_exists('notifications.php')) {
        $message .= "✅ notifications.php file exists<br>";
        
        // Test API endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/notifications.php?action=get_notifications");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $result = curl_exec($ch);
        curl_close($ch);
        
        $message .= "📡 API Response: <pre>" . htmlspecialchars($result) . "</pre><br>";
    } else {
        $message .= "❌ notifications.php file NOT found!<br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notifications - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="padding: 40px; max-width: 800px; margin: 0 auto;">
    <h1>🔔 Notification System Test</h1>
    
    <?php if ($message): ?>
        <div class="card" style="background: #f0f9ff; border: 2px solid #3b82f6; padding: 20px; margin: 20px 0;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2>Test Options:</h2>
        
        <a href="?test=1" class="btn btn-primary">🧪 Create Test Notification</a>
        <a href="?check_api=1" class="btn btn-secondary">📡 Check API</a>
        <a href="notifications.php?action=get_notifications" class="btn btn-success" target="_blank">🔍 View API Response</a>
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
    
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2>📋 Checklist:</h2>
        <ol>
            <li>✅ notifications.php file exists in root folder</li>
            <li>✅ notifications table exists in database</li>
            <li>✅ createSaleNotification() function exists in functions.php</li>
            <li>✅ JavaScript is loading and checking notifications</li>
            <li>✅ Notification bell is visible in sidebar</li>
        </ol>
    </div>
    
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2>🔍 Debug Info:</h2>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
        <p><strong>User Name:</strong> <?php echo $_SESSION['full_name']; ?></p>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        
        <?php
        $conn = getDBConnection();
        
        // Check tables
        echo "<h3>Database Tables:</h3>";
        $tables = $conn->query("SHOW TABLES LIKE 'notifications'");
        echo "Notifications table: " . ($tables->num_rows > 0 ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
        
        // Check recent notifications
        $recent = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
        if ($recent && $recent->num_rows > 0) {
            echo "<h3>Recent Notifications:</h3>";
            echo "<table class='table'>";
            echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Created</th><th>Read</th></tr>";
            while ($row = $recent->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['type'] . "</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No notifications in database yet</p>";
        }
        ?>
    </div>
</body>
</html>