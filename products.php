<?php
$page_title = 'Products';
include 'header.php';

$message = '';
$message_type = '';
$edit_product = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $result = createProduct(
            sanitizeInput($_POST['product_code']),
            sanitizeInput($_POST['product_name']),
            sanitizeInput($_POST['category']),
            sanitizeInput($_POST['unit']),
            floatval($_POST['price']),
            intval($_POST['stock_quantity']),
            intval($_POST['reorder_level']),
            sanitizeInput($_POST['supplier'])
        );
        
        if ($result) {
            $message = 'Product added successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error adding product. Product code might already exist.';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['update_product'])) {
        $result = updateProduct(
            intval($_POST['product_id']),
            sanitizeInput($_POST['product_name']),
            sanitizeInput($_POST['category']),
            sanitizeInput($_POST['unit']),
            floatval($_POST['price']),
            intval($_POST['stock_quantity']),
            intval($_POST['reorder_level']),
            sanitizeInput($_POST['supplier']),
            sanitizeInput($_POST['status'])
        );
        
        if ($result) {
            $message = 'Product updated successfully!';
            $message_type = 'success';
        } else {
            $message = 'Error updating product.';
            $message_type = 'danger';
        }
    } elseif (isset($_POST['adjust_stock'])) {
        $product_id = intval($_POST['product_id']);
        $adjustment = intval($_POST['stock_adjustment']);
        $adjustment_type = sanitizeInput($_POST['adjustment_type']);
        
        // Get current stock
        $product = getProductById($product_id);
        if ($product) {
            $new_stock = $product['stock_quantity'];
            
            if ($adjustment_type === 'add') {
                $new_stock += $adjustment;
                $action = 'Added';
            } else {
                $new_stock -= $adjustment;
                $action = 'Removed';
            }
            
            // Prevent negative stock
            if ($new_stock < 0) {
                $message = 'Cannot remove more stock than available!';
                $message_type = 'danger';
            } else {
                // Update stock
                $conn = getDBConnection();
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_stock, $product_id);
                
                if ($stmt->execute()) {
                    $message = $action . ' ' . $adjustment . ' units. New stock: ' . $new_stock;
                    $message_type = 'success';
                } else {
                    $message = 'Error adjusting stock.';
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $result = deleteProduct(intval($_GET['delete']));
    if ($result) {
        $message = 'Product deleted successfully!';
        $message_type = 'success';
    } else {
        $message = 'Error deleting product.';
        $message_type = 'danger';
    }
}

// Handle edit
if (isset($_GET['edit'])) {
    $edit_product = getProductById(intval($_GET['edit']));
}

$products = getAllProducts();
?>

<h1>📦 Products Management</h1>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;"><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
    
    <form method="POST">
        <?php if ($edit_product): ?>
            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Product Code *</label>
                <input type="text" name="product_code" class="form-control" value="<?php echo $edit_product['product_code'] ?? ''; ?>" required <?php echo $edit_product ? 'readonly' : ''; ?>>
            </div>
            
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="product_name" class="form-control" value="<?php echo $edit_product['product_name'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" class="form-control" value="<?php echo $edit_product['category'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" class="form-control" value="<?php echo $edit_product['unit'] ?? 'pcs'; ?>" placeholder="pcs, kg, ltr, etc">
            </div>
            
            <div class="form-group">
                <label>Price (<?php echo CURRENCY; ?>) *</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $edit_product['price'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Stock Quantity *</label>
                <input type="number" name="stock_quantity" class="form-control" value="<?php echo $edit_product['stock_quantity'] ?? '0'; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Reorder Level</label>
                <input type="number" name="reorder_level" class="form-control" value="<?php echo $edit_product['reorder_level'] ?? '10'; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Supplier</label>
                <input type="text" name="supplier" class="form-control" value="<?php echo $edit_product['supplier'] ?? ''; ?>">
            </div>
            
            <?php if ($edit_product): ?>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo ($edit_product['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($edit_product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="btn btn-primary">
                <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
            </button>
            
            <?php if ($edit_product): ?>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h2 style="margin-bottom: 20px;">All Products</h2>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['product_code']; ?></td>
                    <td><?php echo $product['product_name']; ?></td>
                    <td><?php echo $product['category']; ?></td>
                    <td><?php echo formatCurrency($product['price']); ?></td>
                    <td>
                        <?php if ($product['stock_quantity'] <= $product['reorder_level']): ?>
                            <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span>
                        <?php else: ?>
                            <span class="badge badge-success"><?php echo $product['stock_quantity']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>