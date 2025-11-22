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

function createSale($user_id, $customer_name, $items, $total_amount, $payment_method) {
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        $sale_number = generateSaleNumber();
        
        // Insert sale
        $stmt = $conn->prepare("INSERT INTO sales (sale_number, user_id, customer_name, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisds", $sale_number, $user_id, $customer_name, $total_amount, $payment_method);
        $stmt->execute();
        $sale_id = $conn->insert_id;
        
        // Insert sale items and update stock
        $stmt_item = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        
        foreach ($items as $item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $stmt_item->bind_param("iiidd", $sale_id, $item['product_id'], $item['quantity'], $item['unit_price'], $subtotal);
            $stmt_item->execute();
            
            $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt_stock->execute();
        }
        
        $conn->commit();
        return $sale_number;
    } catch (Exception $e) {
        $conn->rollback();
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

function getSaleByNumber($sale_number) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT s.*, u.full_name as cashier_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.sale_number = ?");
    $stmt->bind_param("s", $sale_number);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ==================== DASHBOARD/STATISTICS FUNCTIONS ====================

function getTotalSalesToday() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $row = $result->fetch_assoc();
    return $row['total'];
}

function getTotalSalesThisMonth() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
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
?>