<?php
// All System Functions in One Place

// ==================== AUTHENTICATION FUNCTIONS ====================

function authenticateUser($username, $password) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, password, full_name, role FROM users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// ==================== USER MANAGEMENT FUNCTIONS ====================

function createUser($username, $password, $full_name, $role) {
    $conn = getDBConnection();
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashed_password, $full_name, $role);
    
    return $stmt->execute();
}

function getAllUsers() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT id, username, full_name, role, status, created_at FROM users ORDER BY created_at DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateUser($id, $full_name, $role, $status) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, role = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $role, $status, $id);
    return $stmt->execute();
}

function deleteUser($id) {
    $conn = getDBConnection();
    // Don't allow deleting the default admin
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND id != 1");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// ==================== PRODUCT MANAGEMENT FUNCTIONS ====================

function createProduct($product_code, $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO products (product_code, product_name, category, unit, buying_price, selling_price, stock_quantity, reorder_level, supplier) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssddiis", $product_code, $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier);
    return $stmt->execute();
}

function getAllProducts($active_only = false) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM products";
    if ($active_only) {
        $sql .= " WHERE status = 'active'";
    }
    $sql .= " ORDER BY product_name ASC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updateProduct($id, $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier, $status) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, category = ?, unit = ?, buying_price = ?, selling_price = ?, stock_quantity = ?, reorder_level = ?, supplier = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssddiissi", $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier, $status, $id);
    return $stmt->execute();
}

function updateProductStock($id, $quantity) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $id);
    return $stmt->execute();
}

function deleteProduct($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getLowStockProducts() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM products WHERE stock_quantity <= reorder_level AND status = 'active' ORDER BY stock_quantity ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==================== INVENTORY/STOCK MOVEMENT FUNCTIONS ====================

function receiveStock($product_id, $quantity, $buying_price, $user_id, $reference_number = '', $notes = '') {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, buying_price = ? WHERE id = ?");
        $stmt->bind_param("idi", $quantity, $buying_price, $product_id);
        $stmt->execute();
        
        // Record stock movement
        $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, user_id, movement_type, quantity, buying_price, reference_number, notes) VALUES (?, ?, 'purchase', ?, ?, ?, ?)");
        $stmt->bind_param("iiidss", $product_id, $user_id, $quantity, $buying_price, $reference_number, $notes);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getStockMovements($limit = 50) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT sm.*, p.product_code, p.product_name, u.full_name as user_name FROM stock_movements sm LEFT JOIN products p ON sm.product_id = p.id LEFT JOIN users u ON sm.user_id = u.id ORDER BY sm.movement_date DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductStockMovements($product_id, $limit = 20) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT sm.*, u.full_name as user_name FROM stock_movements sm LEFT JOIN users u ON sm.user_id = u.id WHERE sm.product_id = ? ORDER BY sm.movement_date DESC LIMIT ?");
    $stmt->bind_param("ii", $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ==================== SALES FUNCTIONS ====================

function generateSaleNumber() {
    return 'SALE-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function createSale($user_id, $customer_name, $items, $total_amount, $payment_method, $discount_amount = 0, $discount_percent = 0) {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        $sale_number = generateSaleNumber();
        
        // Calculate final amount after discount
        $final_amount = $total_amount - $discount_amount;
        if ($final_amount < 0) $final_amount = 0;
        
        // Calculate total cost and profit
        $total_cost = 0;
        $total_profit = 0;
        
        foreach ($items as $item) {
            $product = getProductById($item['product_id']);
            $buying_price = isset($product['buying_price']) ? $product['buying_price'] : 0;
            $item_cost = $buying_price * $item['quantity'];
            $item_profit = ($item['unit_price'] - $buying_price) * $item['quantity'];
            
            $total_cost += $item_cost;
            $total_profit += $item_profit;
        }
        
        // Adjust profit for discount
        $adjusted_profit = $total_profit - $discount_amount;
        if ($adjusted_profit < 0) $adjusted_profit = 0;
        
        // Check which columns exist in the sales table
        $result = $conn->query("DESCRIBE sales");
        $existing_columns = [];
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        // Build dynamic SQL based on existing columns
        $columns = ['sale_number', 'user_id', 'customer_name', 'total_amount', 'payment_method'];
        $placeholders = ['?', '?', '?', '?', '?'];
        $bind_types = "sisds"; // string, integer, string, double, string
        
        $bind_params = [
            $sale_number, 
            $user_id, 
            $customer_name, 
            $total_amount, 
            $payment_method
        ];
        
        // Add discount_amount if column exists
        if (in_array('discount_amount', $existing_columns)) {
            $columns[] = 'discount_amount';
            $placeholders[] = '?';
            $bind_types .= 'd';
            $bind_params[] = $discount_amount;
        }
        
        // Add discount_percent if column exists
        if (in_array('discount_percent', $existing_columns)) {
            $columns[] = 'discount_percent';
            $placeholders[] = '?';
            $bind_types .= 'd';
            $bind_params[] = $discount_percent;
        }
        
        // Add final_amount if column exists
        if (in_array('final_amount', $existing_columns)) {
            $columns[] = 'final_amount';
            $placeholders[] = '?';
            $bind_types .= 'd';
            $bind_params[] = $final_amount;
        }
        
        // Add total_cost if column exists
        if (in_array('total_cost', $existing_columns)) {
            $columns[] = 'total_cost';
            $placeholders[] = '?';
            $bind_types .= 'd';
            $bind_params[] = $total_cost;
        }
        
        // Add total_profit if column exists
        if (in_array('total_profit', $existing_columns)) {
            $columns[] = 'total_profit';
            $placeholders[] = '?';
            $bind_types .= 'd';
            $bind_params[] = $adjusted_profit;
        }
        
        // Build and execute the dynamic SQL
        $sql = "INSERT INTO sales (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        
        // Dynamic bind_param
        $stmt->bind_param($bind_types, ...$bind_params);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute sale insert: " . $stmt->error);
        }
        
        $sale_id = $conn->insert_id;
        
        // Insert sale items and update stock
        $stmt_item = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach ($items as $item) {
            $product = getProductById($item['product_id']);
            $buying_price = isset($product['buying_price']) ? $product['buying_price'] : 0;
            $subtotal = $item['quantity'] * $item['unit_price'];
            $profit = ($item['unit_price'] - $buying_price) * $item['quantity'];
            
            $stmt_item->bind_param("iiidddd", $sale_id, $item['product_id'], $item['quantity'], $item['unit_price'], $buying_price, $subtotal, $profit);
            
            if (!$stmt_item->execute()) {
                throw new Exception("Failed to execute sale item insert: " . $stmt_item->error);
            }
            
            $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            
            if (!$stmt_stock->execute()) {
                throw new Exception("Failed to update stock: " . $stmt_stock->error);
            }
            
            // Check if stock is now low after sale
            $new_stock = $product['stock_quantity'] - $item['quantity'];
            $reorder_level = $product['reorder_level'];
            
            if ($new_stock <= $reorder_level && $product['stock_quantity'] > $reorder_level) {
                createLowStockNotification($product['product_name'], $new_stock, $reorder_level);
            }
        }
        
        // Create notification for all users
        createSaleNotification($sale_number, $final_amount, $adjusted_profit, $customer_name);
        
        $conn->commit();
        
        // Log successful sale with discount info
        error_log("Sale created: $sale_number, Discount: $discount_amount, Final: $final_amount");
        
        return $sale_number;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Sale creation error: " . $e->getMessage());
        return false;
    }
}

function getAllSales($limit = 100) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT s.*, u.full_name as cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.sale_date DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getSaleDetails($sale_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT si.*, p.product_name, p.product_code FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getSaleByNumber($sale_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT s.*, u.full_name as cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->bind_param("i", $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ==================== DASHBOARD/STATISTICS FUNCTIONS ====================

function getTotalSalesToday() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalProfitToday() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(total_profit), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalSalesThisMonth() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalProfitThisMonth() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(total_profit), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalProducts() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getLowStockCount() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= reorder_level AND status = 'active'");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getProfitData($period = 'weekly') {
    $conn = getDBConnection();
    $data = [];
    
    switch ($period) {
        case 'weekly':
            // Last 7 days
            $query = "SELECT DATE(sale_date) as date, COALESCE(SUM(total_profit), 0) as profit, COALESCE(SUM(final_amount), 0) as sales 
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(sale_date)
                      ORDER BY date ASC";
            break;
            
        case 'monthly':
            // Last 30 days
            $query = "SELECT DATE(sale_date) as date, COALESCE(SUM(total_profit), 0) as profit, COALESCE(SUM(final_amount), 0) as sales 
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY DATE(sale_date)
                      ORDER BY date ASC";
            break;
            
        case 'three_months':
            // Last 12 weeks (grouped by week)
            $query = "SELECT DATE_FORMAT(sale_date, '%Y-%m-%d') as date, 
                      COALESCE(SUM(total_profit), 0) as profit, 
                      COALESCE(SUM(final_amount), 0) as sales
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
                      GROUP BY YEARWEEK(sale_date)
                      ORDER BY date ASC";
            break;
            
        case 'six_months':
            // Last 6 months (grouped by month)
            $query = "SELECT DATE_FORMAT(sale_date, '%Y-%m-01') as date, 
                      COALESCE(SUM(total_profit), 0) as profit, 
                      COALESCE(SUM(final_amount), 0) as sales 
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
                      ORDER BY date ASC";
            break;
            
        case 'yearly':
            // Last 12 months (grouped by month)
            $query = "SELECT DATE_FORMAT(sale_date, '%Y-%m-01') as date, 
                      COALESCE(SUM(total_profit), 0) as profit, 
                      COALESCE(SUM(final_amount), 0) as sales 
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
                      ORDER BY date ASC";
            break;
            
        default:
            $query = "SELECT DATE(sale_date) as date, COALESCE(SUM(total_profit), 0) as profit, COALESCE(SUM(final_amount), 0) as sales 
                      FROM sales 
                      WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(sale_date)
                      ORDER BY date ASC";
    }
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// ==================== UTILITY FUNCTIONS ====================

function formatCurrency($amount) {
    return CURRENCY . ' ' . number_format($amount, 2);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function showAlert($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// ==================== NOTIFICATION FUNCTIONS ====================

function createSaleNotification($sale_number, $total_amount, $profit, $customer_name) {
    $conn = getDBConnection();
    
    // Check if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->num_rows == 0) {
        // Create table if it doesn't exist
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
    
    // Get all active users
    $users_result = $conn->query("SELECT id FROM users WHERE status = 'active'");
    
    if (!$users_result) {
        error_log("Error getting users: " . $conn->error);
        return false;
    }
    
    $title = "New Sale: " . $sale_number;
    $message = "Sale of " . formatCurrency($total_amount) . " (Profit: " . formatCurrency($profit) . ")";
    if ($customer_name) {
        $message .= " to " . $customer_name;
    }
    $link = "sales.php";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'sale', ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Notification prepare failed: " . $conn->error);
        return false;
    }
    
    $success_count = 0;
    while ($user = $users_result->fetch_assoc()) {
        $stmt->bind_param("isss", $user['id'], $title, $message, $link);
        if ($stmt->execute()) {
            $success_count++;
        } else {
            error_log("Notification insert failed: " . $stmt->error);
        }
    }
    
    error_log("Created {$success_count} sale notifications for sale {$sale_number}");
    return $success_count > 0;
}

function createLowStockNotification($product_name, $current_stock, $reorder_level) {
    $conn = getDBConnection();
    
    // Check if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->num_rows == 0) {
        return false; // Table doesn't exist
    }
    
    // Get all admin users
    $users_result = $conn->query("SELECT id FROM users WHERE status = 'active' AND role = 'admin'");
    
    if (!$users_result) {
        error_log("Error getting admin users: " . $conn->error);
        return false;
    }
    
    $title = "Low Stock Alert: " . $product_name;
    $message = "Stock level (" . $current_stock . ") is at or below reorder level (" . $reorder_level . "). Please restock soon!";
    $link = "inventory.php";
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'low_stock', ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Low stock notification prepare failed: " . $conn->error);
        return false;
    }
    
    $success_count = 0;
    while ($user = $users_result->fetch_assoc()) {
        $stmt->bind_param("isss", $user['id'], $title, $message, $link);
        if ($stmt->execute()) {
            $success_count++;
        } else {
            error_log("Low stock notification insert failed: " . $stmt->error);
        }
    }
    
    error_log("Created {$success_count} low stock notifications for {$product_name}");
    return $success_count > 0;
}

// ==================== ACTIVITY TRACKING FUNCTIONS ====================

function logActivity($user_id, $activity_type, $description = '', $page_url = '') {
    $conn = getDBConnection();
    
    // Auto-create activity_log table if it doesn't exist
    $table_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($table_check->num_rows == 0) {
        $conn->query("CREATE TABLE activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            username VARCHAR(50),
            activity_type ENUM('login', 'logout', 'sale', 'product_add', 'product_edit', 'product_delete', 'inventory_receive', 'user_add', 'user_edit', 'user_delete', 'page_view') NOT NULL,
            description TEXT,
            page_url VARCHAR(200),
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_type (activity_type),
            INDEX idx_date (created_at)
        )");
    }
    
    // Get user info
    $username = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (empty($page_url)) {
        $page_url = $_SERVER['REQUEST_URI'];
    }
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, activity_type, description, page_url, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $username, $activity_type, $description, $page_url, $ip_address, $user_agent);
    $stmt->execute();
}

function startUserSession($user_id) {
    $conn = getDBConnection();
    
    // Auto-create user_sessions table if it doesn't exist
    $table_check = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    if ($table_check->num_rows == 0) {
        $conn->query("CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            session_id VARCHAR(100) UNIQUE,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            logout_time TIMESTAMP NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            is_active TINYINT(1) DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_active (user_id, is_active),
            INDEX idx_session (session_id)
        )");
    }
    
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $session_id, $ip_address, $user_agent);
    $stmt->execute();
}

function endUserSession() {
    $conn = getDBConnection();
    $session_id = session_id();
    
    $stmt = $conn->prepare("UPDATE user_sessions SET logout_time = NOW(), is_active = 0 WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
}

function updateSessionActivity() {
    $conn = getDBConnection();
    $session_id = session_id();
    
    $stmt = $conn->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
}

function getActivityLog($user_id = null, $limit = 100) {
    $conn = getDBConnection();
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $stmt = $conn->prepare("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getActiveSessions() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT us.*, u.full_name, u.role FROM user_sessions us LEFT JOIN users u ON us.user_id = u.id WHERE us.is_active = 1 ORDER BY us.last_activity DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getSessionStats($user_id = null) {
    $conn = getDBConnection();
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_sessions, AVG(TIMESTAMPDIFF(MINUTE, login_time, COALESCE(logout_time, NOW()))) as avg_duration FROM user_sessions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT COUNT(*) as total_sessions, AVG(TIMESTAMPDIFF(MINUTE, login_time, COALESCE(logout_time, NOW()))) as avg_duration FROM user_sessions");
    }
    
    return $result->fetch_assoc();
}

// ==================== EXCEL IMPORT FUNCTIONS ====================


// ==================== CSV IMPORT FUNCTIONS ====================

function importProductsFromCSV($file_path) {
    $result = [
        'success' => false,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'error' => ''
    ];
    
    try {
        // Check if file exists and is readable
        if (!file_exists($file_path) || !is_readable($file_path)) {
            throw new Exception("Cannot read the uploaded file.");
        }
        
        $conn = getDBConnection();
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $row_count = 0;
        
        // Open the CSV file
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            // Skip the header row
            fgetcsv($handle);
            
            // Process each row
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row_count++;
                
                // Skip empty rows
                if (count($data) < 2 || (empty($data[0]) && empty($data[1]))) {
                    $skipped++;
                    continue;
                }
                
                // Map CSV columns to variables
                $product_code = isset($data[0]) ? trim($data[0]) : '';
                $product_name = isset($data[1]) ? trim($data[1]) : '';
                $category = isset($data[2]) ? trim($data[2]) : '';
                $unit = isset($data[3]) ? trim($data[3]) : '';
                $buying_price = isset($data[4]) ? floatval($data[4]) : 0;
                $selling_price = isset($data[5]) ? floatval($data[5]) : 0;
                $stock_quantity = isset($data[6]) ? intval($data[6]) : 0;
                $reorder_level = isset($data[7]) ? intval($data[7]) : 10;
                $supplier = isset($data[8]) ? trim($data[8]) : '';
                
                // Validate required fields
                if (empty($product_code)) {
                    error_log("Skipped row $row_count: Missing product code");
                    $skipped++;
                    continue;
                }
                
                if (empty($product_name)) {
                    error_log("Skipped row $row_count: Missing product name for product code: $product_code");
                    $skipped++;
                    continue;
                }
                
                if ($selling_price <= 0) {
                    error_log("Skipped row $row_count: Invalid selling price for product: $product_code");
                    $skipped++;
                    continue;
                }
                
                // Set default values
                if (empty($unit)) $unit = 'pcs';
                if ($stock_quantity < 0) $stock_quantity = 0;
                if ($reorder_level <= 0) $reorder_level = 10;
                if ($buying_price < 0) $buying_price = 0;
                
                // Check if product code already exists
                $check_stmt = $conn->prepare("SELECT id, product_name FROM products WHERE product_code = ?");
                $check_stmt->bind_param("s", $product_code);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Product exists, update it
                    $existing_product = $check_result->fetch_assoc();
                    $update_stmt = $conn->prepare("UPDATE products SET product_name = ?, category = ?, unit = ?, buying_price = ?, selling_price = ?, stock_quantity = ?, reorder_level = ?, supplier = ?, updated_at = CURRENT_TIMESTAMP WHERE product_code = ?");
                    $update_stmt->bind_param("sssddiiss", $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier, $product_code);
                    
                    if ($update_stmt->execute()) {
                        $updated++;
                        error_log("Updated product: $product_code - $product_name");
                    } else {
                        $skipped++;
                        error_log("Failed to update product $product_code: " . $update_stmt->error);
                    }
                } else {
                    // Insert new product
                    $insert_stmt = $conn->prepare("INSERT INTO products (product_code, product_name, category, unit, buying_price, selling_price, stock_quantity, reorder_level, supplier) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssssddiis", $product_code, $product_name, $category, $unit, $buying_price, $selling_price, $stock_quantity, $reorder_level, $supplier);
                    
                    if ($insert_stmt->execute()) {
                        $imported++;
                        error_log("Imported new product: $product_code - $product_name");
                    } else {
                        $skipped++;
                        error_log("Failed to insert product $product_code: " . $insert_stmt->error);
                    }
                }
            }
            fclose($handle);
        } else {
            throw new Exception("Could not open the CSV file for reading.");
        }
        
        $result['success'] = true;
        $result['imported'] = $imported;
        $result['updated'] = $updated;
        $result['skipped'] = $skipped;
        
        // Log activity
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'product_add', "Imported $imported new products, updated $updated products from CSV", 'products.php');
        }
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
        error_log("CSV import error: " . $e->getMessage());
    }
    
    return $result;
}

function generateSampleCSV() {
    $filename = 'sample_products_template.csv';
    
    try {
        // Create CSV content
        $csv_content = "Product Code,Product Name,Category,Unit,Buying Price,Selling Price,Stock Quantity,Reorder Level,Supplier\n";
        
        // Sample data
        $sample_data = [
            ['PROD001', 'Hammer', 'Tools', 'pcs', '1500.00', '2500.00', '50', '10', 'Tools Supplier'],
            ['PROD002', 'Screwdriver Set', 'Tools', 'set', '3000.00', '5000.00', '30', '5', 'Tools Supplier'],
            ['PROD003', 'Nails 1kg', 'Hardware', 'kg', '800.00', '1500.00', '100', '20', 'Hardware Wholesaler'],
            ['PROD004', 'Paint Brush', 'Painting', 'pcs', '500.00', '1200.00', '40', '10', 'Paint Supplier'],
            ['PROD005', 'Safety Gloves', 'Safety', 'pair', '1200.00', '2000.00', '60', '15', 'Safety Equipment Co.'],
            ['PROD006', 'Electric Drill', 'Power Tools', 'pcs', '25000.00', '40000.00', '15', '3', 'Power Tools Ltd'],
            ['PROD007', 'Measuring Tape', 'Tools', 'pcs', '1500.00', '2800.00', '75', '10', 'Tools Supplier'],
            ['PROD008', 'Wood Plank 2m', 'Lumber', 'pcs', '4500.00', '7500.00', '25', '5', 'Timber Company'],
            ['PROD009', 'Paint White 1L', 'Painting', 'ltr', '3500.00', '6000.00', '30', '8', 'Paint Supplier'],
            ['PROD010', 'Safety Goggles', 'Safety', 'pcs', '800.00', '1800.00', '45', '12', 'Safety Equipment Co.']
        ];
        
        foreach ($sample_data as $data) {
            $csv_content .= implode(',', $data) . "\n";
        }
        
        // Save to file
        if (file_put_contents($filename, $csv_content) !== false) {
            return $filename;
        } else {
            throw new Exception("Could not create sample CSV file.");
        }
        
    } catch (Exception $e) {
        error_log("Sample CSV generation error: " . $e->getMessage());
        return false;
    }
}