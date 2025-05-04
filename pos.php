<?php
// Include database connection
require_once 'db.php';

// Get product data from database
$products_query = "SELECT p.*, c.name as category_name, i.quantity as stock_quantity 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN inventory i ON p.product_id = i.product_id 
                  WHERE p.is_active = 1 
                  ORDER BY p.name";
$products_result = $conn->query($products_query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Include the header (which also handles authentication)
include 'header.php';
?>

<div class="dashboard-content pos-dashboard">
    <div class="pos-header">
        <h1>Point of Sale</h1>
        <div class="quick-actions">
            <button id="new-customer-btn" class="btn-icon" title="New Customer"><i class="fas fa-user-plus"></i></button>
            <button id="view-inventory-btn" class="btn-icon" title="Check Inventory"><i class="fas fa-boxes"></i></button>
            <button class="btn-icon toggle-numpad" title="Show Numpad"><i class="fas fa-calculator"></i></button>
            <button id="help-btn" class="btn-icon" title="Help"><i class="fas fa-question-circle"></i></button>
        </div>
    </div>
    
    <div class="pos-content">
        <div class="product-search-container">
            <div class="search-wrapper">
                <input type="text" id="product-search" placeholder="Search products or scan barcode...">
                <div class="search-icon"><i class="fas fa-search"></i></div>
                <div class="barcode-icon"><i class="fas fa-barcode"></i></div>
            </div>
            
            <div class="product-categories">
                <div class="category-buttons">
                    <button class="category-btn active" data-category="all">All Products</button>
                    <?php while($category = $categories_result->fetch_assoc()): ?>
                        <button class="category-btn" data-category="<?php echo $category['category_id']; ?>">
                            <?php echo $category['name']; ?>
                        </button>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="product-grid">
                <?php if($products_result->num_rows > 0): ?>
                    <?php while($product = $products_result->fetch_assoc()): ?>
                        <div class="product-item" 
                            data-id="<?php echo $product['product_id']; ?>" 
                            data-price="<?php echo $product['selling_price']; ?>"
                            data-category="<?php echo $product['category_id']; ?>"
                            data-stock="<?php echo $product['stock_quantity']; ?>">
                            <div class="product-image">
                                <?php if(!empty($product['image_url'])): ?>
                                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                                <?php else: ?>
                                    <div class="product-icon">
                                        <i class="fas fa-box"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($product['stock_quantity'] <= 5 && $product['stock_quantity'] > 0): ?>
                                    <span class="stock-badge low">Low Stock</span>
                                <?php elseif($product['stock_quantity'] <= 0): ?>
                                    <span class="stock-badge out">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h5 title="<?php echo $product['name']; ?>"><?php echo $product['name']; ?></h5>
                                <div class="product-meta">
                                    <p class="sku"><?php echo $product['sku'] ?? 'No SKU'; ?></p>
                                    <p class="price">$<?php echo number_format($product['selling_price'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>No products found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="cart-container">
            <div class="cart-header">
                <h3>Current Sale</h3>
                <div class="cart-tools">
                    <button id="clear-search-btn" class="btn-icon" title="Clear Search"><i class="fas fa-times"></i></button>
                    <button id="cart-expand-btn" class="btn-icon" title="Expand Cart"><i class="fas fa-expand-alt"></i></button>
                </div>
            </div>
            
            <div class="customer-selection">
                <select id="customer-select">
                    <option value="0">Walk-in Customer</option>
                    <?php 
                    // Get customers
                    $customers_query = "SELECT customer_id, name, phone FROM customers ORDER BY name";
                    $customers_result = $conn->query($customers_query);
                    
                    while($customer = $customers_result->fetch_assoc()) {
                        echo "<option value='{$customer['customer_id']}'>{$customer['name']} ({$customer['phone']})</option>";
                    }
                    ?>
                </select>
                <button id="new-customer-btn"><i class="fas fa-plus"></i></button>
            </div>
            
            <div class="cart-items">
                <table id="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cart items will be added dynamically -->
                        <tr class="empty-cart">
                            <td colspan="5">
                                <div class="empty-cart-message">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>Cart is empty</p>
                                    <span>Add products by clicking on them</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (7.5%):</span>
                    <span id="tax">$0.00</span>
                </div>
                <div class="summary-row discount-row">
                    <span>Discount:</span>
                    <div class="discount-inputs">
                        <input type="number" id="discount-amount" placeholder="0.00" min="0" step="0.01">
                        <select id="discount-type">
                            <option value="fixed">$</option>
                            <option value="percentage">%</option>
                        </select>
                    </div>
                </div>
                <div class="summary-row total-row">
                    <span>Total:</span>
                    <span id="total">$0.00</span>
                </div>
            </div>
            
            <div class="payment-actions">
                <button id="cancel-sale-btn" class="btn-secondary"><i class="fas fa-times"></i> Cancel</button>
                <button id="hold-sale-btn" class="btn-warning"><i class="fas fa-pause"></i> Hold</button>
                <button id="view-held-btn" class="btn-secondary"><i class="fas fa-list"></i> Held</button>
                <button id="checkout-btn" class="btn-primary"><i class="fas fa-credit-card"></i> Payment</button>
            </div>
            
            <div class="shortcuts-help">
                <div class="shortcut"><span>F2</span> New Customer</div>
                <div class="shortcut"><span>F3</span> Search</div>
                <div class="shortcut"><span>F8</span> Hold</div>
                <div class="shortcut"><span>F12</span> Payment</div>
            </div>
        </div>
    </div>
    
    <!-- Numpad -->
    <div class="numpad" style="display:none;">
        <div class="numpad-header">
            <h3>Numpad</h3>
            <button class="close-numpad"><i class="fas fa-times"></i></button>
        </div>
        <div class="numpad-display">
            <input type="text" id="numpad-input" value="0">
        </div>
        <div class="numpad-buttons">
            <button class="numpad-btn">7</button>
            <button class="numpad-btn">8</button>
            <button class="numpad-btn">9</button>
            <button class="numpad-btn">4</button>
            <button class="numpad-btn">5</button>
            <button class="numpad-btn">6</button>
            <button class="numpad-btn">1</button>
            <button class="numpad-btn">2</button>
            <button class="numpad-btn">3</button>
            <button class="numpad-btn">0</button>
            <button class="numpad-btn">.</button>
            <button class="numpad-btn numpad-enter">Enter</button>
        </div>
        <div class="numpad-actions">
            <button class="numpad-action" data-action="quantity">Set Quantity</button>
            <button class="numpad-action" data-action="discount">Set Discount</button>
            <button class="numpad-action" data-action="price">Custom Price</button>
        </div>
    </div>
    
    <!-- Held Sales Modal -->
    <div id="held-sales-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-pause-circle"></i> Held Sales</h2>
                <span class="close held-sales-close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="held-sales-container">
                    <div class="no-held-sales">
                        <i class="fas fa-pause-circle"></i>
                        <p>No held sales found</p>
                    </div>
                </div>
                <div class="modal-actions">
                    <button id="close-held-sales" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-credit-card"></i> Payment</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="payment-total">
                <h3>Total Amount: <span id="payment-total-amount">$0.00</span></h3>
            </div>
            
            <div class="payment-methods">
                <h4>Select Payment Method</h4>
                <div class="payment-method-options">
                    <button class="payment-method-btn active" data-method="cash">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Cash</span>
                    </button>
                    <button class="payment-method-btn" data-method="credit_card">
                        <i class="fas fa-credit-card"></i>
                        <span>Credit Card</span>
                    </button>
                    <button class="payment-method-btn" data-method="debit_card">
                        <i class="fas fa-credit-card"></i>
                        <span>Debit Card</span>
                    </button>
                    <button class="payment-method-btn" data-method="mobile_payment">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Mobile Payment</span>
                    </button>
                </div>
            </div>
            
            <div id="cash-payment-section" class="payment-section">
                <div class="quick-cash">
                    <button class="quick-cash-btn" data-amount="10">$10</button>
                    <button class="quick-cash-btn" data-amount="20">$20</button>
                    <button class="quick-cash-btn" data-amount="50">$50</button>
                    <button class="quick-cash-btn" data-amount="100">$100</button>
                    <button class="quick-cash-btn exact-btn">Exact</button>
                </div>
                <div class="input-group">
                    <label for="cash-tendered">Amount Tendered:</label>
                    <input type="number" id="cash-tendered" placeholder="0.00" step="0.01" min="0">
                </div>
                <div class="change-calculation">
                    <h4>Change: <span id="change-amount">$0.00</span></h4>
                </div>
            </div>
            
            <div id="card-payment-section" class="payment-section" style="display:none;">
                <div class="input-group">
                    <label for="card-number">Card Number:</label>
                    <input type="text" id="card-number" placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19">
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label for="expiry-date">Expiry Date:</label>
                        <input type="text" id="expiry-date" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="input-group">
                        <label for="cvv">CVV:</label>
                        <input type="text" id="cvv" placeholder="XXX" maxlength="4">
                    </div>
                </div>
                <div class="card-actions">
                    <button id="process-card" class="btn-primary">Process Card</button>
                </div>
            </div>
            
            <div id="mobile-payment-section" class="payment-section" style="display:none;">
                <div class="qr-code-container">
                    <img src="assets/images/qr-placeholder.png" alt="QR Code">
                    <p>Scan with your mobile payment app</p>
                </div>
                <div class="input-group">
                    <label for="transaction-reference">Transaction Reference:</label>
                    <input type="text" id="transaction-reference">
                </div>
                <div class="mobile-payment-actions">
                    <button id="verify-mobile" class="btn-primary">Verify Payment</button>
                </div>
            </div>
            
            <div class="payment-note">
                <div class="note-header">
                    <label for="payment-note">
                        <i class="fas fa-sticky-note"></i> Note
                        <span class="note-tip">(Order details, special instructions, etc.)</span>
                    </label>
                    <button type="button" class="note-toggle" title="Expand/Collapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="note-content">
                    <textarea id="payment-note" placeholder="Add details about this transaction, special requests, or any relevant information for reference..."></textarea>
                    <div class="note-footer">
                        <span class="character-count">0/200 characters</span>
                    </div>
                </div>
            </div>
            
            <div class="payment-actions modal-actions">
                <button id="cancel-payment-btn" class="btn-secondary">Cancel</button>
                <button id="complete-payment-btn" class="btn-success">Complete Sale</button>
            </div>
        </div>
    </div>
</div>

<!-- New Customer Modal -->
<div id="customer-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> New Customer</h2>
            <span class="close customer-close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="customer-form">
                <div class="input-group">
                    <label for="customer-name">Full Name: <span class="required">*</span></label>
                    <input type="text" id="customer-name" name="name" required>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label for="customer-phone">Phone:</label>
                        <input type="tel" id="customer-phone" name="phone">
                    </div>
                    <div class="input-group">
                        <label for="customer-email">Email:</label>
                        <input type="email" id="customer-email" name="email">
                    </div>
                </div>
                <div class="input-group">
                    <label for="customer-address">Address:</label>
                    <textarea id="customer-address" name="address"></textarea>
                </div>
                
                <div class="modal-actions">
                    <div class="button-row">
                        <button type="button" class="btn-secondary customer-cancel">Cancel</button>
                        <button type="submit" class="btn-primary">Save Customer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add new enhanced CSS for the POS interface -->
<link rel="stylesheet" href="assets/css/pos-enhanced.css">

<!-- Add enhanced JavaScript for the POS functionality -->
<script src="assets/js/pos-enhanced.js"></script>

<?php include 'footer.php'; ?>
