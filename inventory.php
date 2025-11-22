<?php
$page_title = 'Inventory Management';
include 'header.php';

$message = '';
$message_type = '';

// Handle stock receiving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_stock'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $buying_price = floatval($_POST['buying_price']);
    $reference_number = sanitizeInput($_POST['reference_number']);
    $notes = sanitizeInput($_POST['notes']);
    
    if ($quantity > 0 && $buying_price >= 0) {
        $result = receiveStock($product_id, $quantity, $buying_price, $_SESSION['user_id'], $reference_number, $notes);
        
        if ($result) {
            $message = "Stock received successfully! Added {$quantity} units to inventory.";
            $message_type = 'success';
        } else {
            $message = 'Error receiving stock. Please try again.';
            $message_type = 'danger';
        }
    }
}

$products = getAllProducts(true);

// Check and update database schema if needed
$conn = getDBConnection();

// Check if stock_movements table exists
$table_check = $conn->query("SHOW TABLES LIKE 'stock_movements'");
if ($table_check->num_rows == 0) {
    $conn->query("CREATE TABLE stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        user_id INT,
        movement_type ENUM('purchase', 'sale', 'adjustment') NOT NULL,
        quantity INT NOT NULL,
        buying_price DECIMAL(10,2),
        reference_number VARCHAR(100),
        notes TEXT,
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
}

// Check if products table has new columns
$columns = $conn->query("SHOW COLUMNS FROM products LIKE 'buying_price'");
if ($columns->num_rows == 0) {
    // Add new columns
    $conn->query("ALTER TABLE products ADD COLUMN buying_price DECIMAL(10,2) DEFAULT 0 AFTER unit");
    $conn->query("ALTER TABLE products ADD COLUMN selling_price DECIMAL(10,2) DEFAULT 0 AFTER buying_price");
    
    // Copy old price to selling_price if price column exists
    $price_col = $conn->query("SHOW COLUMNS FROM products LIKE 'price'");
    if ($price_col->num_rows > 0) {
        $conn->query("UPDATE products SET selling_price = price WHERE selling_price = 0");
    }
}

$stock_movements = getStockMovements(50);

// Get pre-selected product ID from URL
$selected_product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;
?>

<h1>📥 Inventory Management</h1>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Receive Stock</h2>
    
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Select Product *</label>
                <select name="product_id" id="productSelect" class="form-control" required onchange="updateProductInfo()">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                                data-name="<?php echo $product['product_name']; ?>"
                                data-code="<?php echo $product['product_code']; ?>"
                                data-stock="<?php echo $product['stock_quantity']; ?>"
                                data-buying="<?php echo $product['buying_price']; ?>"
                                data-selling="<?php echo $product['selling_price']; ?>">
                            <?php echo $product['product_code']; ?> - <?php echo $product['product_name']; ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div id="productInfo" style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Product Code:</strong><br>
                    <span id="infoCode"></span>
                </div>
                <div>
                    <strong>Current Stock:</strong><br>
                    <span id="infoStock" style="font-size: 18px; color: var(--primary);"></span>
                </div>
                <div>
                    <strong>Last Buying Price:</strong><br>
                    <span id="infoBuying"></span>
                </div>
                <div>
                    <strong>Selling Price:</strong><br>
                    <span id="infoSelling"></span>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Quantity Received *</label>
                <input type="number" name="quantity" id="quantityInput" class="form-control" min="1" required oninput="calculateNewStock()">
            </div>
            
            <div class="form-group">
                <label>Buying Price per Unit (<?php echo CURRENCY; ?>) *</label>
                <input type="number" step="0.01" name="buying_price" id="buyingPriceInput" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Reference Number</label>
                <input type="text" name="reference_number" class="form-control" placeholder="Invoice/PO number">
            </div>
        </div>
        
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes (optional)"></textarea>
        </div>
        
        <div id="stockSummary" style="display: none; background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>New Total Stock:</strong>
                    <span id="newStock" style="font-size: 24px; font-weight: bold; color: #10b981; margin-left: 10px;"></span>
                </div>
                <div>
                    <strong>Total Cost:</strong>
                    <span id="totalCost" style="font-size: 20px; font-weight: bold; color: #2563eb; margin-left: 10px;"></span>
                </div>
            </div>
        </div>
        
        <button type="submit" name="receive_stock" class="btn btn-success">📥 Receive Stock</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px;">Stock Movement History</h2>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Buying Price</th>
                    <th>Total Cost</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($stock_movements) > 0): ?>
                    <?php foreach ($stock_movements as $movement): ?>
                    <tr>
                        <td><?php echo date('d M Y, h:i A', strtotime($movement['movement_date'])); ?></td>
                        <td>
                            <strong><?php echo $movement['product_code']; ?></strong><br>
                            <small><?php echo $movement['product_name']; ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $movement['movement_type'] === 'purchase' ? 'success' : 'primary'; ?>">
                                <?php echo ucfirst($movement['movement_type']); ?>
                            </span>
                        </td>
                        <td><strong><?php echo $movement['quantity']; ?></strong></td>
                        <td><?php echo $movement['buying_price'] ? formatCurrency($movement['buying_price']) : '-'; ?></td>
                        <td>
                            <?php 
                            if ($movement['buying_price']) {
                                echo formatCurrency($movement['quantity'] * $movement['buying_price']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?php echo $movement['reference_number'] ?: '-'; ?></td>
                        <td><?php echo $movement['user_name']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">No stock movements recorded yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let currentStock = 0;

function updateProductInfo() {
    const select = document.getElementById('productSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const productCode = option.getAttribute('data-code');
        const productStock = option.getAttribute('data-stock');
        const buyingPrice = option.getAttribute('data-buying');
        const sellingPrice = option.getAttribute('data-selling');
        
        currentStock = parseInt(productStock);
        
        document.getElementById('infoCode').textContent = productCode;
        document.getElementById('infoStock').textContent = productStock + ' units';
        document.getElementById('infoBuying').textContent = '<?php echo CURRENCY; ?> ' + parseFloat(buyingPrice).toFixed(2);
        document.getElementById('infoSelling').textContent = '<?php echo CURRENCY; ?> ' + parseFloat(sellingPrice).toFixed(2);
        
        document.getElementById('productInfo').style.display = 'block';
        document.getElementById('buyingPriceInput').value = buyingPrice;
        
        calculateNewStock();
    } else {
        document.getElementById('productInfo').style.display = 'none';
        document.getElementById('stockSummary').style.display = 'none';
    }
}

function calculateNewStock() {
    const quantity = parseInt(document.getElementById('quantityInput').value) || 0;
    const buyingPrice = parseFloat(document.getElementById('buyingPriceInput').value) || 0;
    
    if (quantity > 0 && currentStock >= 0) {
        const newStock = currentStock + quantity;
        const totalCost = quantity * buyingPrice;
        
        document.getElementById('newStock').textContent = newStock + ' units';
        document.getElementById('totalCost').textContent = '<?php echo CURRENCY; ?> ' + totalCost.toFixed(2);
        document.getElementById('stockSummary').style.display = 'block';
    } else {
        document.getElementById('stockSummary').style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>