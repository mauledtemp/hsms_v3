<?php
$page_title = 'Sales Reports';
include 'header.php';
requireAdmin();

// Default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'daily_summary';
$cashier_id = isset($_GET['cashier_id']) ? intval($_GET['cashier_id']) : 'all';

// Get sales data based on filters
$sales_data = getSalesReportData($start_date, $end_date, $report_type, $cashier_id);
$cashiers = getAllUsers();

// Calculate totals
$total_sales = 0;
$total_cost = 0;
$total_profit = 0;
$total_discount = 0;

if ($sales_data) {
    foreach ($sales_data as $sale) {
        $total_sales += $sale['final_amount'];
        $total_cost += $sale['total_cost'];
        $total_profit += $sale['total_profit'];
        $total_discount += $sale['discount_amount'];
    }
}
?>

<h1>📊 Sales Reports</h1>

<!-- Report Filters -->
<div class="card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">Report Filters</h2>
    
    <form method="GET" action="">
        <div class="form-row">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Report Type</label>
                <select name="report_type" class="form-control" onchange="this.form.submit()">
                    <option value="daily_summary" <?php echo $report_type == 'daily_summary' ? 'selected' : ''; ?>>Daily Summary</option>
                    <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Sales</option>
                    <option value="product_performance" <?php echo $report_type == 'product_performance' ? 'selected' : ''; ?>>Product Performance</option>
                    <option value="cashier_performance" <?php echo $report_type == 'cashier_performance' ? 'selected' : ''; ?>>Cashier Performance</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Cashier</label>
                <select name="cashier_id" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?php echo $cashier_id == 'all' ? 'selected' : ''; ?>>All Cashiers</option>
                    <?php foreach ($cashiers as $cashier): ?>
                        <option value="<?php echo $cashier['id']; ?>" <?php echo $cashier_id == $cashier['id'] ? 'selected' : ''; ?>>
                            <?php echo $cashier['full_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <button type="button" onclick="printReport()" class="btn btn-secondary">🖨️ Print Report</button>
            <button type="button" onclick="exportToExcel()" class="btn btn-success">📥 Export to Excel</button>
            <a href="sales_report.php" class="btn btn-outline">Reset Filters</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="dashboard-cards" style="margin-bottom: 30px;">
    <div class="card stat-card primary">
        <div class="stat-info">
            <h3>Total Sales</h3>
            <div class="stat-value"><?php echo formatCurrency($total_sales); ?></div>
            <small><?php echo count($sales_data); ?> transactions</small>
        </div>
        <div class="stat-icon">💵</div>
    </div>
    
    <div class="card stat-card success">
        <div class="stat-info">
            <h3>Total Profit</h3>
            <div class="stat-value"><?php echo formatCurrency($total_profit); ?></div>
            <small>Profit Margin: <?php echo $total_sales > 0 ? number_format(($total_profit / $total_sales) * 100, 2) : '0'; ?>%</small>
        </div>
        <div class="stat-icon">💰</div>
    </div>
    
    <div class="card stat-card warning">
        <div class="stat-info">
            <h3>Total Cost</h3>
            <div class="stat-value"><?php echo formatCurrency($total_cost); ?></div>
            <small>Cost of Goods Sold</small>
        </div>
        <div class="stat-icon">📦</div>
    </div>
    
    <div class="card stat-card danger">
        <div class="stat-info">
            <h3>Total Discount</h3>
            <div class="stat-value"><?php echo formatCurrency($total_discount); ?></div>
            <small>Discount Given</small>
        </div>
        <div class="stat-icon">🎁</div>
    </div>
</div>

<!-- Report Content -->
<div class="card" id="reportContent">
    <h2 style="margin-bottom: 20px;">
        <?php 
        echo ucfirst(str_replace('_', ' ', $report_type)) . " Report";
        echo " (" . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . ")";
        ?>
    </h2>
    
    <?php if (empty($sales_data)): ?>
        <div style="text-align: center; padding: 40px; color: var(--secondary);">
            <h3>No sales data found for the selected period</h3>
            <p>Try adjusting your date range or filters.</p>
        </div>
    <?php else: ?>
        <?php if ($report_type == 'daily_summary'): ?>
            <!-- Daily Summary Report -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transactions</th>
                            <th>Total Sales</th>
                            <th>Total Cost</th>
                            <th>Total Profit</th>
                            <th>Discount</th>
                            <th>Profit Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $daily_totals = [];
                        foreach ($sales_data as $sale) {
                            $date = date('Y-m-d', strtotime($sale['sale_date']));
                            if (!isset($daily_totals[$date])) {
                                $daily_totals[$date] = [
                                    'transactions' => 0,
                                    'sales' => 0,
                                    'cost' => 0,
                                    'profit' => 0,
                                    'discount' => 0
                                ];
                            }
                            $daily_totals[$date]['transactions']++;
                            $daily_totals[$date]['sales'] += $sale['final_amount'];
                            $daily_totals[$date]['cost'] += $sale['total_cost'];
                            $daily_totals[$date]['profit'] += $sale['total_profit'];
                            $daily_totals[$date]['discount'] += $sale['discount_amount'];
                        }
                        
                        krsort($daily_totals); // Sort by date descending
                        
                        foreach ($daily_totals as $date => $totals): 
                            $profit_margin = $totals['sales'] > 0 ? ($totals['profit'] / $totals['sales']) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo date('M j, Y', strtotime($date)); ?></strong></td>
                            <td><?php echo $totals['transactions']; ?></td>
                            <td><strong><?php echo formatCurrency($totals['sales']); ?></strong></td>
                            <td><?php echo formatCurrency($totals['cost']); ?></td>
                            <td>
                                <span style="color: <?php echo $totals['profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold;">
                                    <?php echo formatCurrency($totals['profit']); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($totals['discount']); ?></td>
                            <td>
                                <span style="color: <?php echo $profit_margin >= 20 ? 'var(--success)' : ($profit_margin >= 10 ? 'var(--warning)' : 'var(--danger)'); ?>; font-weight: bold;">
                                    <?php echo number_format($profit_margin, 2); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report_type == 'detailed'): ?>
            <!-- Detailed Sales Report -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Sale Number</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Cost</th>
                            <th>Profit</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_data as $sale): ?>
                        <tr>
                            <td>
                                <a href="sales.php?view=<?php echo $sale['id']; ?>" class="btn btn-sm btn-outline">
                                    <?php echo $sale['sale_number']; ?>
                                </a>
                            </td>
                            <td><?php echo date('M j, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo $sale['customer_name'] ?: 'Walk-in'; ?></td>
                            <td><?php echo $sale['cashier_name']; ?></td>
                            <td>
                                <?php 
                                $items = getSaleDetails($sale['id']);
                                echo count($items) . ' items';
                                ?>
                            </td>
                            <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            <td>
                                <?php if ($sale['discount_amount'] > 0): ?>
                                    <span style="color: var(--danger);">
                                        -<?php echo formatCurrency($sale['discount_amount']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo formatCurrency($sale['final_amount']); ?></strong></td>
                            <td><?php echo formatCurrency($sale['total_cost']); ?></td>
                            <td>
                                <span style="color: <?php echo $sale['total_profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold;">
                                    <?php echo formatCurrency($sale['total_profit']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?php echo ucfirst($sale['payment_method']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report_type == 'product_performance'): ?>
            <!-- Product Performance Report -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Code</th>
                            <th>Units Sold</th>
                            <th>Sales Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                            <th>Profit Margin</th>
                            <th>Avg. Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $product_stats = [];
                        foreach ($sales_data as $sale) {
                            $items = getSaleDetails($sale['id']);
                            foreach ($items as $item) {
                                $product_id = $item['product_id'];
                                if (!isset($product_stats[$product_id])) {
                                    $product_stats[$product_id] = [
                                        'name' => $item['product_name'],
                                        'code' => $item['product_code'],
                                        'quantity' => 0,
                                        'revenue' => 0,
                                        'cost' => 0,
                                        'profit' => 0
                                    ];
                                }
                                $product_stats[$product_id]['quantity'] += $item['quantity'];
                                $product_stats[$product_id]['revenue'] += $item['subtotal'];
                                $product_stats[$product_id]['cost'] += $item['buying_price'] * $item['quantity'];
                                $product_stats[$product_id]['profit'] += $item['profit'];
                            }
                        }
                        
                        // Sort by profit descending
                        uasort($product_stats, function($a, $b) {
                            return $b['profit'] - $a['profit'];
                        });
                        
                        foreach ($product_stats as $stat): 
                            $profit_margin = $stat['revenue'] > 0 ? ($stat['profit'] / $stat['revenue']) * 100 : 0;
                            $avg_price = $stat['quantity'] > 0 ? $stat['revenue'] / $stat['quantity'] : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo $stat['name']; ?></strong></td>
                            <td><?php echo $stat['code']; ?></td>
                            <td><?php echo $stat['quantity']; ?></td>
                            <td><?php echo formatCurrency($stat['revenue']); ?></td>
                            <td><?php echo formatCurrency($stat['cost']); ?></td>
                            <td>
                                <span style="color: <?php echo $stat['profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold;">
                                    <?php echo formatCurrency($stat['profit']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?php echo $profit_margin >= 20 ? 'var(--success)' : ($profit_margin >= 10 ? 'var(--warning)' : 'var(--danger)'); ?>; font-weight: bold;">
                                    <?php echo number_format($profit_margin, 2); ?>%
                                </span>
                            </td>
                            <td><?php echo formatCurrency($avg_price); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report_type == 'cashier_performance'): ?>
            <!-- Cashier Performance Report -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Transactions</th>
                            <th>Total Sales</th>
                            <th>Average Sale</th>
                            <th>Total Profit</th>
                            <th>Discount Given</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cashier_stats = [];
                        foreach ($sales_data as $sale) {
                            $cashier_id = $sale['user_id'];
                            if (!isset($cashier_stats[$cashier_id])) {
                                $cashier_stats[$cashier_id] = [
                                    'name' => $sale['cashier_name'],
                                    'transactions' => 0,
                                    'sales' => 0,
                                    'profit' => 0,
                                    'discount' => 0
                                ];
                            }
                            $cashier_stats[$cashier_id]['transactions']++;
                            $cashier_stats[$cashier_id]['sales'] += $sale['final_amount'];
                            $cashier_stats[$cashier_id]['profit'] += $sale['total_profit'];
                            $cashier_stats[$cashier_id]['discount'] += $sale['discount_amount'];
                        }
                        
                        // Sort by sales descending
                        uasort($cashier_stats, function($a, $b) {
                            return $b['sales'] - $a['sales'];
                        });
                        
                        foreach ($cashier_stats as $stat): 
                            $avg_sale = $stat['transactions'] > 0 ? $stat['sales'] / $stat['transactions'] : 0;
                            $performance = $stat['sales'] > 0 ? ($stat['profit'] / $stat['sales']) * 100 : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo $stat['name']; ?></strong></td>
                            <td><?php echo $stat['transactions']; ?></td>
                            <td><strong><?php echo formatCurrency($stat['sales']); ?></strong></td>
                            <td><?php echo formatCurrency($avg_sale); ?></td>
                            <td>
                                <span style="color: <?php echo $stat['profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; font-weight: bold;">
                                    <?php echo formatCurrency($stat['profit']); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($stat['discount']); ?></td>
                            <td>
                                <span style="color: <?php echo $performance >= 20 ? 'var(--success)' : ($performance >= 10 ? 'var(--warning)' : 'var(--danger)'); ?>; font-weight: bold;">
                                    <?php echo number_format($performance, 2); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function printReport() {
    var printContent = document.getElementById('reportContent').innerHTML;
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

function exportToExcel() {
    // Simple CSV export
    let csv = [];
    let rows = document.querySelectorAll('table tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Remove currency symbols and format for Excel
            let text = cols[j].innerText.replace(/[^\d.,-]/g, '');
            row.push(text);
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV file
    let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    let downloadLink = document.createElement('a');
    downloadLink.download = 'sales_report_<?php echo date('Y-m-d'); ?>.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<style>
@media print {
    .sidebar, .btn, .dashboard-cards, .card:first-child { 
        display: none !important; 
    }
    .main-content { 
        margin-left: 0 !important; 
        width: 100% !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<?php include 'footer.php'; ?>