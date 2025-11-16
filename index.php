<?php
$page_title = 'Dashboard';
include 'header.php';

$today_sales = getTotalSalesToday();
$month_sales = getTotalSalesThisMonth();
$total_products = getTotalProducts();
$low_stock_count = getLowStockCount();
$low_stock_products = getLowStockProducts();
$recent_sales = getAllSales(10);
?>

<h1>Dashboard</h1>

<div class="dashboard-cards">
    <div class="card stat-card primary">
        <div class="stat-info">
            <h3>Today's Sales</h3>
            <div class="stat-value"><?php echo formatCurrency($today_sales); ?></div>
        </div>
        <div class="stat-icon">💵</div>
    </div>
    
    <div class="card stat-card success">
        <div class="stat-info">
            <h3>This Month</h3>
            <div class="stat-value"><?php echo formatCurrency($month_sales); ?></div>
        </div>
        <div class="stat-icon">📊</div>
    </div>
    
    <div class="card stat-card warning">
        <div class="stat-info">
            <h3>Total Products</h3>
            <div class="stat-value"><?php echo $total_products; ?></div>
        </div>
        <div class="stat-icon">📦</div>
    </div>
    
    <div class="card stat-card danger">
        <div class="stat-info">
            <h3>Low Stock Items</h3>
            <div class="stat-value"><?php echo $low_stock_count; ?></div>
        </div>
        <div class="stat-icon">⚠️</div>
    </div>
</div>

<?php if ($low_stock_count > 0): ?>
<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px; color: var(--danger);">⚠️ Low Stock Alert</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Current Stock</th>
                    <th>Reorder Level</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $product): ?>
                <tr>
                    <td><?php echo $product['product_code']; ?></td>
                    <td><?php echo $product['product_name']; ?></td>
                    <td><span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span></td>
                    <td><?php echo $product['reorder_level']; ?></td>
                    <td>
                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Update Stock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2 style="margin-bottom: 20px;">Recent Sales</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sale Number</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Cashier</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_sales) > 0): ?>
                    <?php foreach ($recent_sales as $sale): ?>
                    <tr>
                        <td><?php echo $sale['sale_number']; ?></td>
                        <td><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></td>
                        <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                        <td><?php echo $sale['cashier_name']; ?></td>
                        <td><?php echo date('d M Y, h:i A', strtotime($sale['sale_date'])); ?></td>
                        <td>
                            <a href="sales.php?view=<?php echo $sale['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">No sales recorded yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>