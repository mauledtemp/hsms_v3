<?php
require_once 'config.php';

$conn = getDBConnection();

// Check sales table structure
echo "<h2>Sales Table Structure</h2>";
$result = $conn->query("DESCRIBE sales");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if discount columns exist
$discount_check = $conn->query("SHOW COLUMNS FROM sales LIKE 'discount_amount'");
if ($discount_check->num_rows == 0) {
    echo "<p style='color: red;'>Discount columns do NOT exist in sales table</p>";
} else {
    echo "<p style='color: green;'>Discount columns exist in sales table</p>";
}
?>