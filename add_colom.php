<?php
require_once 'config.php';

$conn = getDBConnection();

echo "<h2>Adding Discount Columns to Sales Table</h2>";

$columns_to_add = [
    'discount_amount' => "ALTER TABLE sales ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER total_amount",
    'discount_percent' => "ALTER TABLE sales ADD COLUMN discount_percent DECIMAL(5,2) DEFAULT 0 AFTER discount_amount", 
    'final_amount' => "ALTER TABLE sales ADD COLUMN final_amount DECIMAL(10,2) DEFAULT 0 AFTER discount_percent",
    'total_cost' => "ALTER TABLE sales ADD COLUMN total_cost DECIMAL(10,2) DEFAULT 0 AFTER final_amount",
    'total_profit' => "ALTER TABLE sales ADD COLUMN total_profit DECIMAL(10,2) DEFAULT 0 AFTER total_cost"
];

foreach ($columns_to_add as $column => $sql) {
    $check = $conn->query("SHOW COLUMNS FROM sales LIKE '$column'");
    if ($check->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Added column '$column' successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add column '$column': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>→ Column '$column' already exists</p>";
    }
}

// Update existing records to set final_amount = total_amount
$conn->query("UPDATE sales SET final_amount = total_amount WHERE final_amount = 0 OR final_amount IS NULL");
echo "<p style='color: green;'>✓ Updated existing records</p>";
?>