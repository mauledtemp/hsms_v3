<?php
$page_title = 'Point of Sale';
include 'header.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_sale'])) {
    $customer_name = sanitizeInput($_POST['customer_name']);
    $payment_method = sanitizeInput($_POST['payment_method']);
    $cart_items = json_decode($_POST['cart_data'], true);
    $total_amount = floatval($_POST['total_amount']);
    
    if (!empty($cart_items) && $total_amount > 0) {
        $sale_number = createSale($_SESSION['user_id'], $customer_name, $cart_items, $total_amount, $payment_method);
        
        if ($sale_number) {
            $message = "Sale completed successfully! Sale Number: {$sale_number}";
            $message_type = 'success';
            
            // Debug: Check if notification was created
            error_log("Sale created: {$sale_number}, checking notifications...");
            $conn_check = getDBConnection();
            $notif_check = $conn_check->query("SELECT COUNT(*) as count FROM notifications WHERE title LIKE '%{$sale_number}%'");
            if ($notif_check) {
                $count = $notif_check->fetch_assoc()['count'];
                error_log("Notifications created: {$count}");
            }
        } else {
            $message = "Error completing sale. Please try again.";
            $message_type = 'danger';
        }
    }
}

$products = getAllProducts(true);
?>

<h1>💰 Point of Sale</h1>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<div class="pos-container">
    <div class="card">
        <div class="product-search">
            <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Search products...">
        </div>
        
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $product): ?>
                <?php if ($product['stock_quantity'] > 0): ?>
                <?php 
                    // Handle both old 'price' and new 'selling_price' columns
                    $price = isset($product['selling_price']) ? $product['selling_price'] : $product['price'];
                ?>
                <div class="product-item" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $price; ?>, <?php echo $product['stock_quantity']; ?>)">
                    <h4><?php echo $product['product_name']; ?></h4>
                    <div class="price"><?php echo formatCurrency($price); ?></div>
                    <small>Stock: <?php echo $product['stock_quantity']; ?></small>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="cart-container">
        <h2 style="margin-bottom: 20px;">Cart</h2>
        
        <div id="cartItems"></div>
        
        <div class="cart-total">
            <h3>
                <span>Total:</span>
                <span id="cartTotal"><?php echo formatCurrency(0); ?></span>
            </h3>
        </div>
        
        <form method="POST" id="saleForm" style="margin-top: 20px;">
            <div class="form-group">
                <label>Customer Name (Optional)</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Walk-in customer">
            </div>
            
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" id="paymentMethod" class="form-control" required onchange="toggleReceivedAmount()">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="mobile">Mobile Money</option>
                </select>
            </div>
            
            <div class="form-group" id="receivedAmountGroup">
                <label>Amount Received</label>
                <input type="number" step="0.01" id="receivedAmount" class="form-control" placeholder="Enter amount received" oninput="calculateChange()">
            </div>
            
            <div id="changeDisplay" style="display: none; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 16px; font-weight: 500;">Change:</span>
                    <span id="changeAmount" style="font-size: 24px; font-weight: bold; color: #2563eb;"></span>
                </div>
            </div>
            
            <input type="hidden" name="cart_data" id="cartData">
            <input type="hidden" name="total_amount" id="totalAmount">
            <input type="hidden" name="complete_sale" value="1">
            
            <button type="button" onclick="completeSale()" class="btn btn-success btn-block" id="completeSaleBtn" disabled>Complete Sale</button>
            <button type="button" onclick="clearCart()" class="btn btn-danger btn-block" style="margin-top: 10px;">Clear Cart</button>
        </form>
    </div>
</div>

<script>
let cart = [];
let currentTotal = 0;

function addToCart(id, name, price, stock) {
    const existingItem = cart.find(item => item.product_id === id);
    
    if (existingItem) {
        if (existingItem.quantity < stock) {
            existingItem.quantity++;
        } else {
            alert('Not enough stock available!');
            return;
        }
    } else {
        cart.push({
            product_id: id,
            name: name,
            unit_price: price,
            quantity: 1,
            max_stock: stock
        });
    }
    
    updateCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.product_id !== id);
    updateCart();
}

function updateQuantity(id, change) {
    const item = cart.find(item => item.product_id === id);
    if (item) {
        const newQty = item.quantity + change;
        if (newQty > 0 && newQty <= item.max_stock) {
            item.quantity = newQty;
            updateCart();
        } else if (newQty > item.max_stock) {
            alert('Not enough stock available!');
        }
    }
}

function updateCartItemQuantity(id, newQty) {
    newQty = parseInt(newQty);
    
    if (isNaN(newQty) || newQty < 1) {
        alert('Please enter a valid quantity!');
        updateCart();
        return;
    }
    
    const item = cart.find(item => item.product_id === id);
    if (item) {
        if (newQty <= item.max_stock) {
            item.quantity = newQty;
            updateCart();
        } else {
            alert('Not enough stock! Maximum available: ' + item.max_stock);
            updateCart();
        }
    }
}

function updateCart() {
    const cartContainer = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const completeBtn = document.getElementById('completeSaleBtn');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '<p style="text-align: center; color: var(--secondary); padding: 40px;">Cart is empty</p>';
        cartTotal.textContent = '<?php echo CURRENCY; ?> 0.00';
        completeBtn.disabled = true;
        currentTotal = 0;
        calculateChange();
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.quantity * item.unit_price;
        total += subtotal;
        
        html += '<div class="cart-item">';
        html += '<div style="flex: 1;">';
        html += '<div style="font-weight: 600;">' + item.name + '</div>';
        html += '<div style="font-size: 13px; color: var(--secondary);">';
        html += formatCurrency(item.unit_price);
        html += '</div></div>';
        html += '<div style="display: flex; align-items: center; gap: 10px;">';
        html += '<input type="number" value="' + item.quantity + '" min="1" max="' + item.max_stock + '" ';
        html += 'onchange="updateCartItemQuantity(' + item.product_id + ', this.value)" ';
        html += 'style="width: 70px; padding: 5px; text-align: center; border: 2px solid var(--border); border-radius: 5px; font-weight: bold;" />';
        html += '<button onclick="removeFromCart(' + item.product_id + ')" class="btn btn-sm btn-danger" type="button">×</button>';
        html += '</div></div>';
        html += '<div style="text-align: right; font-weight: bold; padding: 5px 0;">' + formatCurrency(subtotal) + '</div>';
    });
    
    cartContainer.innerHTML = html;
    cartTotal.textContent = formatCurrency(total);
    currentTotal = total;
    completeBtn.disabled = false;
    calculateChange();
}

function clearCart() {
    if (confirm('Clear all items from cart?')) {
        cart = [];
        updateCart();
        document.getElementById('receivedAmount').value = '';
    }
}

function toggleReceivedAmount() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const receivedAmountGroup = document.getElementById('receivedAmountGroup');
    const receivedAmountInput = document.getElementById('receivedAmount');
    
    if (paymentMethod === 'cash') {
        receivedAmountGroup.style.display = 'block';
        receivedAmountInput.required = true;
    } else {
        receivedAmountGroup.style.display = 'none';
        receivedAmountInput.required = false;
        receivedAmountInput.value = '';
        document.getElementById('changeDisplay').style.display = 'none';
    }
}

function calculateChange() {
    const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
    const changeDisplay = document.getElementById('changeDisplay');
    const changeAmount = document.getElementById('changeAmount');
    const completeBtn = document.getElementById('completeSaleBtn');
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    if (paymentMethod === 'cash' && receivedAmount > 0 && currentTotal > 0) {
        const change = receivedAmount - currentTotal;
        
        if (change >= 0) {
            changeDisplay.style.display = 'block';
            changeAmount.textContent = formatCurrency(change);
            changeDisplay.style.borderColor = '#10b981';
            changeDisplay.style.background = '#f0fdf4';
            changeAmount.style.color = '#10b981';
            completeBtn.disabled = cart.length === 0;
        } else {
            changeDisplay.style.display = 'block';
            changeAmount.textContent = formatCurrency(Math.abs(change)) + ' SHORT';
            changeDisplay.style.borderColor = '#ef4444';
            changeDisplay.style.background = '#fef2f2';
            changeAmount.style.color = '#ef4444';
            completeBtn.disabled = true;
        }
    } else if (paymentMethod !== 'cash') {
        changeDisplay.style.display = 'none';
        completeBtn.disabled = cart.length === 0;
    } else {
        changeDisplay.style.display = 'none';
    }
}

function completeSale() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    const paymentMethod = document.getElementById('paymentMethod').value;
    const receivedAmount = parseFloat(document.getElementById('receivedAmount').value) || 0;
    
    if (paymentMethod === 'cash') {
        if (receivedAmount === 0) {
            alert('Please enter the amount received!');
            document.getElementById('receivedAmount').focus();
            return;
        }
        
        if (receivedAmount < currentTotal) {
            alert('Insufficient amount received! Short by ' + formatCurrency(currentTotal - receivedAmount));
            return;
        }
        
        const change = receivedAmount - currentTotal;
        if (!confirm('Total: ' + formatCurrency(currentTotal) + '\nReceived: ' + formatCurrency(receivedAmount) + '\nChange: ' + formatCurrency(change) + '\n\nComplete this sale?')) {
            return;
        }
    } else {
        if (!confirm('Total: ' + formatCurrency(currentTotal) + '\nPayment Method: ' + paymentMethod.toUpperCase() + '\n\nComplete this sale?')) {
            return;
        }
    }
    
    const total = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    
    document.getElementById('cartData').value = JSON.stringify(cart);
    document.getElementById('totalAmount').value = total;
    
    document.getElementById('saleForm').submit();
}

function formatCurrency(amount) {
    const num = parseFloat(amount).toFixed(2);
    const parts = num.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return '<?php echo CURRENCY; ?> ' + parts.join('.');
}

document.getElementById('searchProduct').addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const name = product.querySelector('h4').textContent.toLowerCase();
        product.style.display = name.includes(search) ? 'block' : 'none';
    });
});

updateCart();
toggleReceivedAmount();
</script>

<?php include 'footer.php'; ?>