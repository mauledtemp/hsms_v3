<?php
$page_title = 'Sales History';
include 'header.php';

$view_sale = null;
$sale_items = [];

if (isset($_GET['view'])) {
    $sale_id = intval($_GET['view']);
    $view_sale = getSaleByNumber($sale_id);
    if ($view_sale) {
        $sale_items = getSaleDetails($view_sale['id']);
    }
}

$sales = getAllSales(100);
?>

<h1>📈 Sales History</h1>

<?php if ($view_sale): ?>
    <div class="card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Sale Details - <?php echo $view_sale['sale_number']; ?></h2>
            <a href="sales.php" class="btn btn-secondary">Back to List</a>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div>
                <strong>Sale Number:</strong><br>
                <?php echo $view_sale['sale_number']; ?>
            </div>
            <div>
                <strong>Customer:</strong><br>
                <?php echo $view_sale['customer_name'] ?: 'Walk-in Customer'; ?>
            </div>
            <div>
                <strong>Cashier:</strong><br>
                <?php echo $view_sale['cashier_name']; ?>
            </div>
            <div>
                <strong>Payment Method:</strong><br>
                <span class="badge badge-primary"><?php echo ucfirst($view_sale['payment_method']); ?></span>
            </div>
            <div>
                <strong>Date:</strong><br>
                <?php echo date('d M Y, h:i A', strtotime($view_sale['sale_date'])); ?>
            </div>
            <div>
                <strong>Subtotal:</strong><br>
                <?php echo formatCurrency($view_sale['total_amount']); ?>
            </div>
            <?php if ($view_sale['discount_amount'] > 0): ?>
            <div>
                <strong>Discount:</strong><br>
                <span style="color: var(--danger); font-weight: bold;">
                    -<?php echo formatCurrency($view_sale['discount_amount']); ?>
                    <?php if ($view_sale['discount_percent'] > 0): ?>
                        (<?php echo $view_sale['discount_percent']; ?>%)
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <div>
                <strong>Final Amount:</strong><br>
                <span style="font-size: 20px; font-weight: bold; color: var(--primary);">
                    <?php echo formatCurrency($view_sale['final_amount']); ?>
                </span>
            </div>
            <div>
                <strong>Total Cost:</strong><br>
                <span style="font-size: 18px; font-weight: bold; color: var(--secondary);">
                    <?php echo formatCurrency($view_sale['total_cost']); ?>
                </span>
            </div>
            <div>
                <strong>Profit:</strong><br>
                <span style="font-size: 20px; font-weight: bold; color: var(--success);">
                    <?php echo formatCurrency($view_sale['total_profit']); ?>
                </span>
            </div>
        </div>
        
        <h3 style="margin-bottom: 15px;">Items Sold</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sale_items as $item): ?>
                    <tr>
                        <td><?php echo $item['product_code']; ?></td>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td><strong><?php echo formatCurrency($item['subtotal']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: var(--light); font-weight: bold;">
                        <td colspan="4" style="text-align: right;">TOTAL:</td>
                        <td><?php echo formatCurrency($view_sale['final_amount']); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <button onclick="window.print()" class="btn btn-primary" style="margin-top: 20px;">🖨️ Print Receipt</button>
    </div>
<?php else: ?>
    <div class="card">
        <h2 style="margin-bottom: 20px;">All Sales</h2>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Sale Number</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Discount</th>
                        <th>Payment</th>
                        <th>Cashier</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales) > 0): ?>
                        <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?php echo $sale['sale_number']; ?></td>
                            <td><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></td>
                            <td><strong><?php echo formatCurrency($sale['final_amount']); ?></strong></td>
                            <td>
                                <?php if ($sale['discount_amount'] > 0): ?>
                                    <span style="color: var(--danger); font-weight: bold;">
                                        -<?php echo formatCurrency($sale['discount_amount']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-primary"><?php echo ucfirst($sale['payment_method']); ?></span></td>
                            <td><?php echo $sale['cashier_name']; ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($sale['sale_date'])); ?></td>
                            <td>
                                <a href="sales.php?view=<?php echo $sale['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">No sales recorded yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
    @media print {
        .sidebar, .btn, nav { display: none !important; }
        .main-content { margin-left: 0 !important; }
    }
</style>

<?php include 'footer.php'; ?>