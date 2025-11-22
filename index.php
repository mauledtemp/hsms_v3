<?php
$page_title = 'Dashboard';
include 'header.php';

// Auto-migrate database for profit tracking
$conn = getDBConnection();
$columns_check = $conn->query("SHOW COLUMNS FROM sales LIKE 'total_profit'");
if ($columns_check->num_rows == 0) {
    $conn->query("ALTER TABLE sales ADD COLUMN total_cost DECIMAL(10,2) DEFAULT 0 AFTER total_amount");
    $conn->query("ALTER TABLE sales ADD COLUMN total_profit DECIMAL(10,2) DEFAULT 0 AFTER total_cost");
    $conn->query("ALTER TABLE sale_items ADD COLUMN buying_price DECIMAL(10,2) DEFAULT 0 AFTER unit_price");
    $conn->query("ALTER TABLE sale_items ADD COLUMN profit DECIMAL(10,2) DEFAULT 0 AFTER subtotal");
}

$today_sales = getTotalSalesToday();
$today_profit = getTotalProfitToday();
$month_sales = getTotalSalesThisMonth();
$month_profit = getTotalProfitThisMonth();
$total_products = getTotalProducts();
$low_stock_count = getLowStockCount();
$low_stock_products = getLowStockProducts();
$recent_sales = getAllSales(10);

// Get profit data for graph (default to weekly)
$period = isset($_GET['period']) ? $_GET['period'] : 'weekly';
$profit_data = getProfitData($period);
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
            <h3>Today's Profit</h3>
            <div class="stat-value"><?php echo formatCurrency($today_profit); ?></div>
        </div>
        <div class="stat-icon">💰</div>
    </div>
    
    <div class="card stat-card primary">
        <div class="stat-info">
            <h3>This Month Sales</h3>
            <div class="stat-value"><?php echo formatCurrency($month_sales); ?></div>
        </div>
        <div class="stat-icon">📊</div>
    </div>
    
    <div class="card stat-card success">
        <div class="stat-info">
            <h3>This Month Profit</h3>
            <div class="stat-value"><?php echo formatCurrency($month_profit); ?></div>
        </div>
        <div class="stat-icon">📈</div>
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

<!-- Profit Graph Section -->
<div class="card" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h2>📈 Sales & Profit Trends</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?period=weekly" class="btn btn-sm <?php echo $period == 'weekly' ? 'btn-primary' : 'btn-secondary'; ?>">7 Days</a>
            <a href="?period=monthly" class="btn btn-sm <?php echo $period == 'monthly' ? 'btn-primary' : 'btn-secondary'; ?>">30 Days</a>
            <a href="?period=three_months" class="btn btn-sm <?php echo $period == 'three_months' ? 'btn-primary' : 'btn-secondary'; ?>">3 Months</a>
            <a href="?period=six_months" class="btn btn-sm <?php echo $period == 'six_months' ? 'btn-primary' : 'btn-secondary'; ?>">6 Months</a>
            <a href="?period=yearly" class="btn btn-sm <?php echo $period == 'yearly' ? 'btn-primary' : 'btn-secondary'; ?>">1 Year</a>
        </div>
    </div>
    
    <canvas id="profitChart" style="max-height: 400px;"></canvas>
    
    <?php if (empty($profit_data)): ?>
        <p style="text-align: center; padding: 40px; color: var(--secondary);">No sales data available for this period</p>
    <?php endif; ?>
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
                        <a href="inventory.php?product=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Update Stock</a>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
console.log('Chart.js loaded:', typeof Chart !== 'undefined');

<?php if (!empty($profit_data)): ?>
console.log('Profit data available');

var profitData = <?php echo json_encode($profit_data); ?>;
var period = '<?php echo $period; ?>';
var currency = '<?php echo CURRENCY; ?>';

function formatMoney(value) {
    var num = parseFloat(value).toFixed(2);
    var parts = num.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return currency + ' ' + parts.join('.');
}

function formatLabel(dateStr, period) {
    var date = new Date(dateStr);
    if (period === 'weekly' || period === 'monthly') {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } else if (period === 'three_months') {
        return 'Week ' + date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    }
}

var labels = profitData.map(function(item) {
    return formatLabel(item.date, period);
});

var salesData = profitData.map(function(item) {
    return parseFloat(item.sales);
});

var profitDataPoints = profitData.map(function(item) {
    return parseFloat(item.profit);
});

var ctx = document.getElementById('profitChart');

if (ctx && typeof Chart !== 'undefined') {
    var profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sales Revenue',
                    data: salesData,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Profit',
                    data: profitDataPoints,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: 'bold'
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += formatMoney(context.parsed.y);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatMoney(value);
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    console.log('Chart created successfully!');
}
<?php else: ?>
console.log('No profit data available');
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>

<?php include 'footer.php'; ?>