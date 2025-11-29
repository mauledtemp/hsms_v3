<?php
// ========================================================================
// TIMEZONE TEST FILE
// Upload this to BOTH local and online server to verify timezone settings
// ========================================================================

// Set timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timezone Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #667eea;
            margin-top: 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .value {
            color: #333;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>🌍 Timezone Configuration Test</h1>
    
    <div class="test-box">
        <h2>PHP Timezone Settings</h2>
        <div class="info-row">
            <span class="label">Configured Timezone:</span>
            <span class="value"><?php echo date_default_timezone_get(); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Current Date/Time:</span>
            <span class="value"><?php echo date('l, F d, Y h:i:s A'); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Timezone Offset:</span>
            <span class="value">UTC<?php echo date('P'); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Unix Timestamp:</span>
            <span class="value"><?php echo time(); ?></span>
        </div>
    </div>
    
    <?php
    // Test database connection and timezone
    require_once 'config.php';
    
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT NOW() as current_time, CURDATE() as current_date, @@session.time_zone as tz_setting");
        $row = $result->fetch_assoc();
        ?>
        
        <div class="test-box">
            <h2>MySQL Timezone Settings</h2>
            <div class="info-row">
                <span class="label">MySQL Current Time:</span>
                <span class="value"><?php echo $row['current_time']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">MySQL Current Date:</span>
                <span class="value"><?php echo $row['current_date']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">MySQL Timezone Setting:</span>
                <span class="value"><?php echo $row['tz_setting']; ?></span>
            </div>
        </div>
        
        <?php
        // Compare times
        $php_time = strtotime(date('Y-m-d H:i:s'));
        $mysql_time = strtotime($row['current_time']);
        $time_diff = abs($php_time - $mysql_time);
        
        if ($time_diff <= 60) {
            echo '<div class="success">';
            echo '<strong>✅ SUCCESS!</strong> PHP and MySQL timezones are synchronized.<br>';
            echo 'Time difference: ' . $time_diff . ' seconds (acceptable)';
            echo '</div>';
        } else {
            echo '<div class="warning">';
            echo '<strong>⚠️ WARNING!</strong> PHP and MySQL times are not synchronized.<br>';
            echo 'Time difference: ' . $time_diff . ' seconds<br>';
            echo 'You may need to adjust your MySQL timezone settings.';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="warning">';
        echo '<strong>⚠️ ERROR!</strong> Could not connect to database.<br>';
        echo 'Error: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    ?>
    
    <div class="test-box">
        <h2>Server Information</h2>
        <div class="info-row">
            <span class="label">Server Software:</span>
            <span class="value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
        </div>
        <div class="info-row">
            <span class="label">PHP Version:</span>
            <span class="value"><?php echo phpversion(); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Server Name:</span>
            <span class="value"><?php echo $_SERVER['SERVER_NAME']; ?></span>
        </div>
        <div class="info-row">
            <span class="label">Document Root:</span>
            <span class="value"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></span>
        </div>
    </div>
    
    <div class="test-box">
        <h2>Expected Times (East Africa Time)</h2>
        <div class="info-row">
            <span class="label">Expected Timezone:</span>
            <span class="value">Africa/Dar_es_Salaam (EAT)</span>
        </div>
        <div class="info-row">
            <span class="label">Expected Offset:</span>
            <span class="value">UTC+03:00</span>
        </div>
        <div class="info-row">
            <span class="label">Current Displayed Time:</span>
            <span class="value"><?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
    </div>
    
</body>
</html>
