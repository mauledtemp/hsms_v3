<?php
// Run once to create DB, tables and default admin. Then delete or protect this file.
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hsmsv2';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database created or exists.<br>";

    // Use DB
    $pdo->exec("USE `$dbname`");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) DEFAULT 'Unnamed',
        code VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) DEFAULT NULL,
        role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table users ready.<br>";

    // Create products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(100) DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        qty INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table products ready.<br>";

    // Create sales and sale_items tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table sales ready.<br>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS sale_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        qty INT NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table sale_items ready.<br>";

    // Insert default admin if not exists (code 2552, password 25252525)
    $code = '2552';
    $passwordPlain = '25252525';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->rowCount() == 0) {
        $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, code, password, role) VALUES (?, ?, ?, ?)");
        $ins->execute(['Administrator', $code, $hash, 'admin']);
        echo "Default admin created (code: 2552, password: 25252525).<br>";
    } else {
        echo "Default admin already exists.<br>";
    }

    echo "<br>Setup complete. Please remove or protect setup.php for security.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
