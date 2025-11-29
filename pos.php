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
    $final_amount = floatval($_POST['final_amount']);
    $discount_amount = floatval($_POST['discount_amount']);
    $discount_percent = floatval($_POST['discount_percent']);
    
    if (!empty($cart_items) && $total_amount > 0) {
        $sale_number = createSale($_SESSION['user_id'], $customer_name, $cart_items, $total_amount, $payment_method, $discount_amount, $discount_percent);
        
        if ($sale_number) {
            $message = "Sale completed successfully! Sale Number: {$sale_number}";
            if ($discount_amount > 0) {
                $message .= " (Discount: " . formatCurrency($discount_amount) . ")";
            }
            $message_type = 'success';
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
            <div class="total-breakdown">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="cartSubtotal"><?php echo formatCurrency(0); ?></span>
                </div>
                
                <div class="discount-section">
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Discount Type</label>
                            <select id="discountType" class="form-control" onchange="updateDiscount()">
                                <option value="none">No Discount</option>
                                <option value="percentage">Percentage %</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label>Discount Value</label>
                            <input type="number" step="0.01" id="discountValue" class="form-control" 
                                   placeholder="0.00" oninput="updateDiscount()" disabled>
                        </div>
                    </div>
                    
                    <div id="discountDisplay" style="display: none;">
                        <div class="total-row discount-row">
                            <span>Discount:</span>
                            <span id="discountAmount"><?php echo formatCurrency(0); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="total-row final-total">
                    <span><strong>Total:</strong></span>
                    <span id="cartTotal" style="font-size: 24px;"><?php echo formatCurrency(0); ?></span>
                </div>
            </div>
        </div>
        
        <form method="POST" id="saleForm" style="margin-top: 20px;">
            <div class="form-group">
                <label>Customer Name (Optional)</label>
                <input type="text" name="customer_name" class="form-control" placeholder="Walk-in customer">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" id="paymentMethod" class="form-control" required onchange="toggleReceivedAmount()">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile Money</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: none;">
                    <input type="hidden" name="discount_amount" id="discountAmountInput">
                    <input type="hidden" name="discount_percent" id="discountPercentInput">
                </div>
            </div>
            
            <div class="form-group" id="receivedAmountGroup">
                <label>Amount Received</label>
                <input type="number" step="0.01" id="receivedAmount" class="form-control" 
                       placeholder="Enter amount received" oninput="calculateChange()">
            </div>
            
            <div id="changeDisplay" style="display: none; background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 16px; font-weight: 500;">Change:</span>
                    <span id="changeAmount" style="font-size: 24px; font-weight: bold; color: #2563eb;"></span>
                </div>
            </div>
            
            <input type="hidden" name="cart_data" id="cartData">
            <input type="hidden" name="total_amount" id="totalAmount">
            <input type="hidden" name="final_amount" id="finalAmount">
            <input type="hidden" name="complete_sale" value="1">
            
            <button type="button" onclick="completeSale()" class="btn btn-success btn-block" id="completeSaleBtn" disabled>
                Complete Sale
            </button>
            <button type="button" onclick="clearCart()" class="btn btn-danger btn-block" style="margin-top: 10px;">
                Clear Cart
            </button>
        </form>
    </div>
</div>

<script>
let cart = [];
let currentSubtotal = 0;
let currentDiscount = 0;
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
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');
    const completeBtn = document.getElementById('completeSaleBtn');
    
    if (cart.length === 0) {
        cartContainer.innerHTML = '<p style="text-align: center; color: var(--secondary); padding: 40px;">Cart is empty</p>';
        cartSubtotal.textContent = formatCurrency(0);
        cartTotal.textContent = formatCurrency(0);
        completeBtn.disabled = true;
        currentSubtotal = 0;
        currentDiscount = 0;
        currentTotal = 0;
        updateDiscount();
        calculateChange();
        return;
    }
    
    let html = '';
    let subtotal = 0;
    
    cart.forEach(item => {
        const itemSubtotal = item.quantity * item.unit_price;
        subtotal += itemSubtotal;
        
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
        html += '<div style="text-align: right; font-weight: bold; padding: 5px 0;">' + formatCurrency(itemSubtotal) + '</div>';
    });
    
    cartContainer.innerHTML = html;
    cartSubtotal.textContent = formatCurrency(subtotal);
    currentSubtotal = subtotal;
    
    updateDiscount();
    calculateChange();
}

function updateDiscount() {
    const discountType = document.getElementById('discountType').value;
    const discountValueInput = document.getElementById('discountValue');
    const discountDisplay = document.getElementById('discountDisplay');
    const discountAmountSpan = document.getElementById('discountAmount');
    const cartTotal = document.getElementById('cartTotal');
    
    // Enable/disable discount value input
    discountValueInput.disabled = discountType === 'none';
    
    if (discountType === 'none' || currentSubtotal === 0) {
        discountDisplay.style.display = 'none';
        currentDiscount = 0;
        currentTotal = currentSubtotal;
        cartTotal.textContent = formatCurrency(currentTotal);
        return;
    }
    
    const discountValue = parseFloat(discountValueInput.value) || 0;
    let discountAmount = 0;
    
    if (discountType === 'percentage') {
        // Limit percentage to 0-100
        const percentage = Math.min(Math.max(discountValue, 0), 100);
        discountValueInput.value = percentage;
        discountAmount = (currentSubtotal * percentage) / 100;
    } else if (discountType === 'fixed') {
        // Limit fixed amount to subtotal
        discountAmount = Math.min(Math.max(discountValue, 0), currentSubtotal);
        discountValueInput.value = discountAmount;
    }
    
    currentDiscount = discountAmount;
    currentTotal = Math.max(currentSubtotal - discountAmount, 0);
    
    discountAmountSpan.textContent = formatCurrency(discountAmount * -1);
    cartTotal.textContent = formatCurrency(currentTotal);
    discountDisplay.style.display = 'block';
}

function clearCart() {
    if (confirm('Clear all items from cart?')) {
        cart = [];
        document.getElementById('discountType').value = 'none';
        document.getElementById('discountValue').value = '';
        document.getElementById('receivedAmount').value = '';
        updateCart();
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
    const discountType = document.getElementById('discountType').value;
    const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
    
    // Set hidden discount values
    document.getElementById('discountAmountInput').value = currentDiscount;
    document.getElementById('discountPercentInput').value = discountType === 'percentage' ? discountValue : 0;
    
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
        let confirmMessage = 'Subtotal: ' + formatCurrency(currentSubtotal) + '\n';
        
        if (currentDiscount > 0) {
            confirmMessage += 'Discount: -' + formatCurrency(currentDiscount) + '\n';
        }
        
        confirmMessage += 'Total: ' + formatCurrency(currentTotal) + '\n';
        confirmMessage += 'Received: ' + formatCurrency(receivedAmount) + '\n';
        confirmMessage += 'Change: ' + formatCurrency(change) + '\n\n';
        confirmMessage += 'Complete this sale?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
    } else {
        let confirmMessage = 'Subtotal: ' + formatCurrency(currentSubtotal) + '\n';
        
        if (currentDiscount > 0) {
            confirmMessage += 'Discount: -' + formatCurrency(currentDiscount) + '\n';
        }
        
        confirmMessage += 'Total: ' + formatCurrency(currentTotal) + '\n';
        confirmMessage += 'Payment Method: ' + paymentMethod.toUpperCase() + '\n\n';
        confirmMessage += 'Complete this sale?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
    }
    
    document.getElementById('cartData').value = JSON.stringify(cart);
    document.getElementById('totalAmount').value = currentSubtotal;
    document.getElementById('finalAmount').value = currentTotal;
    
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