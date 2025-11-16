<?php
// Run this file ONCE to setup the admin user correctly
// Then DELETE this file for security

require_once 'config.php';

$conn = getDBConnection();

// Generate the correct password hash for '25252525'
$password = '25252525';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Setting up Admin User...</h2>";

// Check if admin user exists
$check = $conn->query("SELECT id FROM users WHERE username = 'admin'");

if ($check->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'active' WHERE username = 'admin'");
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin password updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating admin password: " . $conn->error . "</p>";
    }
} else {
    // Create new admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, 'System Administrator', 'admin', 'active')");
    $username = 'admin';
    $stmt->bind_param("ss", $username, $hashed_password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating admin user: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> 25252525</p>";
echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT: Delete this file (setup_admin.php) now for security!</strong></p>";
echo "<p><a href='login.php' style='padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

// Display password hash for verification
echo "<hr>";
echo "<h4>Technical Info (for debugging):</h4>";
echo "<p>Generated Password Hash: <code>" . $hashed_password . "</code></p>";
echo "<p>Password verification test: ";
if (password_verify('25252525', $hashed_password)) {
    echo "<span style='color: green;'>✅ PASS</span>";
} else {
    echo "<span style='color: red;'>❌ FAIL</span>";
}
echo "</p>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Admin - HSMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        code {
            background: #eee;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
</body>
</html>