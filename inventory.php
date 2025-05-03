<?php
include 'header.php';
require_once 'db.php';

// Define default categories to ensure they're available
$default_categories = [
    'Clothing and Accessories',
    'Food and Beverages',
    'Electronics',
    'Home Goods',
    'Services'
];

// Check if categories table is empty and insert default categories if needed
$check_categories_query = "SELECT COUNT(*) as count FROM categories";
$check_result = $conn->query($check_categories_query);
$row = $check_result->fetch_assoc();

if ($row['count'] == 0) {
    // Insert default categories
    foreach($default_categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $category);
        $stmt->execute();
    }
    
    // Notify the user
    echo '<div class="alert alert-info">Default categories have been added to the database.</div>';
}

// Get inventory data with product information
$inventory_query = "SELECT 
    i.inventory_id, 
    p.product_id, 
    p.name, 
    p.sku, 
    p.barcode, 
    c.name as category_name,
    p.cost_price, 
    p.selling_price, 
    i.quantity, 
    i.min_stock_level,
    i.max_stock_level,
    i.location,
    i.last_restock_date
FROM 
    inventory i
JOIN 
    products p ON i.product_id = p.product_id
LEFT JOIN 
    categories c ON p.category_id = c.category_id
ORDER BY 
    p.name";

$inventory_result = $conn->query($inventory_query);

// Get categories for filter dropdown (fetch again after potentially inserting defaults)
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Define storage locations
$storage_locations = [
    'Back Room',
    'Main Warehouse',
    'Shelf A1',
    'Shelf A2',
    'Shelf A3',
    'Shelf A4',
    'Shelf B1',
    'Shelf B2',
    'Shelf B3',
    'Shelf B4',
    'Shelf C1',
    'Shelf C2',
    'Shelf C3',
    'Shelf C4',
    'Front Display',
    'Clearance Area',
    'Receiving Area',
    'Reserve Stock'
];

// Define SKU format options
$sku_formats = [
    'CAT-SEQ-YEAR' => 'Category + Sequential Number + Year',
    'BR-CAT-SEQ' => 'Brand Initial + Category + Sequential Number',
    'LOC-CAT-SEQ' => 'Location + Category + Sequential Number',
    'CAT-SUB-SEQ' => 'Category + Subcategory + Sequential Number',
    'CUSTOM' => 'Custom Format'
];
?>

<div class="dashboard-content">
    <div class="inventory-header">
        <h1>Inventory Management</h1>
        <div class="inventory-actions">
            <button id="new-product-btn" class="btn-primary"><i class="fas fa-plus"></i> Add New Product</button>
            <button id="import-inventory-btn" class="btn-secondary"><i class="fas fa-file-import"></i> Import</button>
            <button id="export-inventory-btn" class="btn-secondary"><i class="fas fa-file-export"></i> Export</button>
        </div>
    </div>

    <div class="inventory-filters">
        <div class="search-container">
            <input type="text" id="inventory-search" placeholder="Search products by name, SKU or barcode...">
            <i class="fas fa-search search-icon"></i>
        </div>
        <div class="filter-container">
            <select id="category-filter">
                <option value="">All Categories</option>
                <?php 
                // Show existing categories from database
                if($categories_result && $categories_result->num_rows > 0) {
                    while($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                    <?php endwhile;
                    // Reset the pointer for later use
                    $categories_result->data_seek(0);
                } else {
                    // If no categories in database, show default ones
                    foreach($default_categories as $category): ?>
                        <option value="<?php echo strtolower(str_replace(' ', '_', $category)); ?>"><?php echo $category; ?></option>
                    <?php endforeach;
                }
                ?>
            </select>
            <select id="stock-filter">
                <option value="">All Stock Levels</option>
                <option value="in-stock">In Stock</option>
                <option value="low-stock">Low Stock</option>
                <option value="out-of-stock">Out of Stock</option>
            </select>
        </div>
    </div>

    <div class="inventory-table-container">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>Quantity</th>
                    <th>Min Level</th>
                    <th>Max Level</th>
                    <th>Cost Price</th>
                    <th>Selling Price</th>
                    <th>Location</th>
                    <th>Last Restocked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($inventory_result && $inventory_result->num_rows > 0): ?>
                    <?php while($item = $inventory_result->fetch_assoc()): ?>
                        <?php 
                            $stock_class = '';
                            if($item['quantity'] <= 0) {
                                $stock_class = 'out-of-stock';
                            } elseif($item['quantity'] <= $item['min_stock_level']) {
                                $stock_class = 'low-stock';
                            }
                        ?>
                        <tr class="<?php echo $stock_class; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-category-id="<?php echo $item['category_id'] ?? ''; ?>">
                            <td><?php echo $item['product_id']; ?></td>
                            <td class="product-image">
                                <img src="assets/images/product-placeholder.png" alt="<?php echo $item['name']; ?>">
                            </td>
                            <td><?php echo $item['name']; ?></td>
                            <td><?php echo $item['category_name'] ?? 'Uncategorized'; ?></td>
                            <td><?php echo $item['sku'] ?? '—'; ?></td>
                            <td><?php echo $item['barcode'] ?? '—'; ?></td>
                            <td class="quantity-cell">
                                <span class="quantity-value"><?php echo $item['quantity']; ?></span>
                                <?php if($item['quantity'] <= $item['min_stock_level'] && $item['quantity'] > 0): ?>
                                    <span class="stock-badge low">Low</span>
                                <?php elseif($item['quantity'] <= 0): ?>
                                    <span class="stock-badge out">Out</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['min_stock_level']; ?></td>
                            <td><?php echo $item['max_stock_level']; ?></td>
                            <td>$<?php echo number_format($item['cost_price'], 2); ?></td>
                            <td>$<?php echo number_format($item['selling_price'], 2); ?></td>
                            <td><?php echo $item['location'] ?? '—'; ?></td>
                            <td><?php echo $item['last_restock_date'] ? date('Y-m-d', strtotime($item['last_restock_date'])) : '—'; ?></td>
                            <td class="action-buttons">
                                <button class="btn-icon view-product" title="View Product" data-id="<?php echo $item['product_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-icon edit-product" title="Edit Product"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon adjust-stock" title="Adjust Stock"><i class="fas fa-boxes"></i></button>
                                <button class="btn-icon view-history" title="View History"><i class="fas fa-history"></i></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="14" class="no-records">No inventory records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="inventory-summary">
        <div class="summary-card">
            <h3>Total Products</h3>
            <p class="count"><?php echo $inventory_result ? $inventory_result->num_rows : 0; ?></p>
        </div>
        <div class="summary-card">
            <h3>Low Stock Items</h3>
            <p class="count" id="low-stock-count">0</p>
        </div>
        <div class="summary-card">
            <h3>Out of Stock</h3>
            <p class="count" id="out-stock-count">0</p>
        </div>
        <div class="summary-card">
            <h3>Inventory Value</h3>
            <p class="count" id="inventory-value">$0.00</p>
        </div>
    </div>

    <!-- Toast notification container -->
    <div id="toast-container"></div>

    <!-- Add/Edit Product Modal -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Add New Product</h2>
            <form id="product-form" enctype="multipart/form-data">
                <input type="hidden" id="product-id" name="product_id" value="">
                <input type="hidden" id="remove-image-flag" name="remove_image" value="0">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="product-name">Product Name *</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category_id">
                            <option value="">Select Category</option>
                            <?php 
                            // Show existing categories from database
                            if($categories_result && $categories_result->num_rows > 0) {
                                while($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endwhile;
                            } else {
                                // If no categories in database, show default ones
                                foreach($default_categories as $category): ?>
                                    <option value="<?php echo strtolower(str_replace(' ', '_', $category)); ?>"><?php echo $category; ?></option>
                                <?php endforeach;
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-sku">SKU</label>
                        <div class="sku-input-group">
                            <input type="text" id="product-sku" name="sku">
                            <button type="button" id="generate-sku" class="btn-icon" title="Generate SKU">
                                <i class="fas fa-magic"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="product-barcode">Barcode</label>
                        <input type="text" id="product-barcode" name="barcode">
                    </div>
                </div>

                <!-- New SKU Generator Modal -->
                <div id="sku-generator-modal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Generate SKU</h2>
                        <div class="form-group">
                            <label for="sku-format">Select SKU Format</label>
                            <select id="sku-format">
                                <?php foreach($sku_formats as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="custom-format-options" style="display:none;">
                            <div class="form-group">
                                <label for="custom-sku-format">Custom Format Template</label>
                                <input type="text" id="custom-sku-format" placeholder="e.g., [CAT]-[BRAND]-[SEQ]">
                                <div class="format-help">
                                    <p>Available placeholders:</p>
                                    <ul>
                                        <li><code>[CAT]</code> - Category</li>
                                        <li><code>[BRAND]</code> - Brand</li>
                                        <li><code>[SEQ]</code> - Sequential Number</li>
                                        <li><code>[YEAR]</code> - Current Year</li>
                                        <li><code>[LOC]</code> - Location</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sku-preview">
                            <label>Preview:</label>
                            <div id="sku-preview-value" class="sku-preview-value">CAT-001-<?php echo date('Y'); ?></div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="cancel-sku-generator">Cancel</button>
                            <button type="button" class="btn-primary" id="apply-sku">Apply SKU</button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" name="description" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-cost">Cost Price *</label>
                        <input type="number" id="product-cost" name="cost_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Selling Price *</label>
                        <input type="number" id="product-price" name="selling_price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-tax">Tax Rate (%)</label>
                        <input type="number" id="product-tax" name="tax_rate" step="0.01" min="0" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" id="initial-quantity-group">
                        <label for="product-quantity">Initial Quantity *</label>
                        <input type="number" id="product-quantity" name="quantity" min="0" value="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-min-stock">Minimum Stock Level</label>
                        <input type="number" id="product-min-stock" name="min_stock_level" min="0" value="10">
                    </div>
                    <div class="form-group">
                        <label for="product-max-stock">Maximum Stock Level</label>
                        <input type="number" id="product-max-stock" name="max_stock_level" min="0" value="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-location">Storage Location</label>
                        <select id="product-location" name="location">
                            <option value="">Select Location</option>
                            <?php foreach($storage_locations as $location): ?>
                                <option value="<?php echo $location; ?>"><?php echo $location; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-image">Product Image</label>
                        <div class="image-upload-container">
                            <div class="image-upload-preview" id="image-preview">
                                <img src="assets/images/product-placeholder.png" id="preview-image" alt="Product Image">
                            </div>
                            <div class="image-upload-controls">
                                <label for="product-image" class="custom-file-upload">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Image
                                </label>
                                <input type="file" id="product-image" name="image" accept="image/*">
                                <button type="button" id="remove-image" class="btn-text">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <div class="image-upload-hint">
                                Recommended size: 600 x 600px (Max 2MB)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancel-product">Cancel</button>
                    <button type="submit" class="btn-primary" id="save-product">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div id="stock-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Adjust Stock</h2>
            <form id="stock-adjustment-form">
                <input type="hidden" id="adjustment-product-id" name="product_id" value="">
                
                <div class="product-info-display">
                    <p><strong>Product:</strong> <span id="adjustment-product-name"></span></p>
                    <p><strong>Current Stock:</strong> <span id="adjustment-current-stock"></span></p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="adjustment-type">Adjustment Type *</label>
                        <select id="adjustment-type" name="adjustment_type" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Exact Quantity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="adjustment-quantity">Quantity *</label>
                        <input type="number" id="adjustment-quantity" name="quantity" min="0" value="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="adjustment-reason">Reason *</label>
                        <select id="adjustment-reason" name="reason" required>
                            <option value="purchase">New Purchase</option>
                            <option value="return">Customer Return</option>
                            <option value="damaged">Damaged/Expired</option>
                            <option value="correction">Inventory Correction</option>
                            <option value="stock_count">Physical Count</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" id="other-reason-group" style="display:none;">
                        <label for="other-reason">Specify Reason *</label>
                        <input type="text" id="other-reason" name="other_reason" placeholder="Please specify reason">
                    </div>
                </div>

                <div class="form-group">
                    <label for="adjustment-notes">Additional Notes</label>
                    <textarea id="adjustment-notes" name="notes" rows="2"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancel-adjustment">Cancel</button>
                    <button type="submit" class="btn-primary" id="save-adjustment">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction History Modal -->
    <div id="history-modal" class="modal">
        <div class="modal-content large">
            <span class="close">&times;</span>
            <h2>Inventory Transaction History</h2>
            <div class="product-info-display">
                <p><strong>Product:</strong> <span id="history-product-name"></span></p>
                <p><strong>Current Stock:</strong> <span id="history-current-stock"></span></p>
            </div>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Qty Change</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>User</th>
                            <th>Notes</th>
                            <th>Ref ID</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-history-body">
                        <tr><td colspan="8" style="text-align: center;">Loading history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div id="view-product-modal" class="modal">
        <div class="modal-content product-detail-modal">
            <span class="close">&times;</span>
            
            <div class="product-detail-header">
                <div class="product-detail-title">
                    <h2 id="view-product-name">Product Name</h2>
                    <div class="product-badge-container">
                        <span id="view-product-status" class="product-status active">Active</span>
                        <span id="view-product-stock-badge" class="stock-badge">In Stock</span>
                    </div>
                </div>
                <div class="product-quick-metrics">
                    <div class="metric">
                        <span class="metric-value" id="view-inventory-value">$0.00</span>
                        <span class="metric-label">Inventory Value</span>
                    </div>
                    <div class="metric">
                        <span class="metric-value" id="view-profit-margin">0%</span>
                        <span class="metric-label">Profit Margin</span>
                    </div>
                    <div class="metric">
                        <span class="metric-value" id="view-product-stock">0</span>
                        <span class="metric-label">Current Stock</span>
                    </div>
                </div>
            </div>

            <div class="product-view-container">
                <div class="product-view-left">
                    <div class="product-view-image-container">
                        <img id="view-product-image" src="assets/images/product-placeholder.png" alt="Product Image">
                    </div>
                    <div class="product-quick-actions">
                        <button class="action-btn" id="view-product-adjust"><i class="fas fa-boxes"></i> Adjust Stock</button>
                        <button class="action-btn" id="view-product-history"><i class="fas fa-history"></i> View History</button>
                        <button class="action-btn" id="view-product-print"><i class="fas fa-print"></i> Print Details</button>
                    </div>
                </div>
                <div class="product-view-right">
                    <div class="product-view-tabs">
                        <button class="tab-btn active" data-tab="basic">Basic Info</button>
                        <button class="tab-btn" data-tab="inventory">Inventory</button>
                        <button class="tab-btn" data-tab="pricing">Pricing</button>
                        <button class="tab-btn" data-tab="description">Description</button>
                    </div>

                    <div class="product-view-content">
                        <div class="tab-panel active" id="tab-basic">
                            <table class="product-view-details">
                                <tr>
                                    <th>ID:</th>
                                    <td id="view-product-id">-</td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td id="view-product-category">-</td>
                                </tr>
                                <tr>
                                    <th>SKU:</th>
                                    <td id="view-product-sku">-</td>
                                </tr>
                                <tr>
                                    <th>Barcode:</th>
                                    <td id="view-product-barcode">-</td>
                                </tr>
                                <tr>
                                    <th>Created:</th>
                                    <td id="view-product-created">-</td>
                                </tr>
                                <tr>
                                    <th>Last Updated:</th>
                                    <td id="view-product-updated">-</td>
                                </tr>
                            </table>
                        </div>

                        <div class="tab-panel" id="tab-inventory">
                            <table class="product-view-details">
                                <tr>
                                    <th>Current Stock:</th>
                                    <td id="view-product-stock-detail">-</td>
                                </tr>
                                <tr>
                                    <th>Min Stock Level:</th>
                                    <td id="view-product-min-stock">-</td>
                                </tr>
                                <tr>
                                    <th>Max Stock Level:</th>
                                    <td id="view-product-max-stock">-</td>
                                </tr>
                                <tr>
                                    <th>Location:</th>
                                    <td id="view-product-location">-</td>
                                </tr>
                                <tr>
                                    <th>Last Restocked:</th>
                                    <td id="view-product-restock-date">-</td>
                                </tr>
                            </table>
                            <div class="stock-level-indicator">
                                <div class="stock-level-bar-container">
                                    <div class="stock-level-labels">
                                        <span>0</span>
                                        <span id="mid-stock-level">50</span>
                                        <span id="max-stock-level">100</span>
                                    </div>
                                    <div class="stock-level-bar">
                                        <div class="stock-level-progress" id="stock-level-progress"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-panel" id="tab-pricing">
                            <table class="product-view-details">
                                <tr>
                                    <th>Cost Price:</th>
                                    <td id="view-product-cost">-</td>
                                </tr>
                                <tr>
                                    <th>Selling Price:</th>
                                    <td id="view-product-price">-</td>
                                </tr>
                                <tr>
                                    <th>Profit Margin:</th>
                                    <td id="view-product-margin">-</td>
                                </tr>
                                <tr>
                                    <th>Tax Rate:</th>
                                    <td id="view-product-tax">-</td>
                                </tr>
                            </table>
                            <div class="price-breakdown">
                                <h4>Price Breakdown</h4>
                                <div class="breakdown-chart">
                                    <div class="cost-segment" id="cost-segment">
                                        <span>Cost</span>
                                    </div>
                                    <div class="margin-segment" id="margin-segment">
                                        <span>Margin</span>
                                    </div>
                                    <div class="tax-segment" id="tax-segment">
                                        <span>Tax</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-panel" id="tab-description">
                            <div id="view-product-description" class="product-view-description">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-view-footer">
                <button type="button" id="view-product-close" class="btn-secondary">Close</button>
                <button type="button" id="view-product-edit" class="btn-primary">Edit Product</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productModal = document.getElementById('product-modal');
    const stockModal = document.getElementById('stock-modal');
    const historyModal = document.getElementById('history-modal');
    const viewProductModal = document.getElementById('view-product-modal');
    const skuGeneratorModal = document.getElementById('sku-generator-modal');
    const productForm = document.getElementById('product-form');
    const stockAdjustmentForm = document.getElementById('stock-adjustment-form');
    const rows = document.querySelectorAll('.inventory-table tbody tr');

    function showToast(message, isSuccess = true, duration = 5000) {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${isSuccess ? 'success' : 'error'}`;
        
        const icon = document.createElement('i');
        icon.className = `fas ${isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
        toast.appendChild(icon);
        
        const messageContainer = document.createElement('div');
        messageContainer.className = 'toast-message';
        
        const title = document.createElement('div');
        title.className = 'toast-title';
        title.textContent = isSuccess ? 'Success' : 'Error';
        messageContainer.appendChild(title);
        
        const text = document.createElement('div');
        text.className = 'toast-text';
        
        if (typeof message === 'object' && message !== null && message.errors) {
            const errorList = document.createElement('ul');
            errorList.className = 'error-list';
            
            Object.entries(message.errors).forEach(([field, errorMsg]) => {
                const errorItem = document.createElement('li');
                errorItem.textContent = errorMsg;
                errorList.appendChild(errorItem);
                
                const inputField = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                if (inputField) {
                    inputField.classList.add('input-error');
                    
                    inputField.addEventListener('input', function() {
                        this.classList.remove('input-error');
                    }, { once: true });
                }
            });
            
            text.appendChild(errorList);
        } else {
            text.textContent = message;
        }
        
        messageContainer.appendChild(text);
        toast.appendChild(messageContainer);
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function() {
            toast.remove();
        });
        toast.appendChild(closeBtn);
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        if (duration) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, duration);
        }
        
        if (isSuccess) {
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    function calculateSummary() {
        let lowStockCount = 0;
        let outStockCount = 0;
        let inventoryValue = 0;

        rows.forEach(row => {
            if (row.cells.length < 10) return;

            const quantityCell = row.querySelector('.quantity-value');
            const costPriceCell = row.cells[9];

            if (!quantityCell || !costPriceCell) return;

            const quantity = parseInt(quantityCell.textContent) || 0;
            const minStock = parseInt(row.cells[7].textContent) || 0;
            const costPrice = parseFloat(costPriceCell.textContent.replace(/[^0-9.-]+/g,"")) || 0;

            row.classList.remove('low-stock', 'out-of-stock');
            if (quantity <= 0) {
                row.classList.add('out-of-stock');
                outStockCount++;
            } else if (quantity <= minStock) {
                row.classList.add('low-stock');
                lowStockCount++;
            }

            inventoryValue += quantity * costPrice;
        });

        document.getElementById('low-stock-count').textContent = lowStockCount;
        document.getElementById('out-stock-count').textContent = outStockCount;
        document.getElementById('inventory-value').textContent = '$' + inventoryValue.toFixed(2);
    }
    calculateSummary();

    const searchInput = document.getElementById('inventory-search');
    const categoryFilter = document.getElementById('category-filter');
    const stockFilter = document.getElementById('stock-filter');

    function filterInventory() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryValue = categoryFilter.value;
        const stockValue = stockFilter.value;

        rows.forEach(row => {
            if (!row.dataset.productId) return;

            const productName = row.cells[2].textContent.toLowerCase();
            const categoryId = row.dataset.categoryId || '';
            const sku = row.cells[4].textContent.toLowerCase();
            const barcode = row.cells[5].textContent.toLowerCase();
            const isLowStock = row.classList.contains('low-stock');
            const isOutOfStock = row.classList.contains('out-of-stock');

            let showRow = true;

            if (searchTerm && !(productName.includes(searchTerm) || sku.includes(searchTerm) || barcode.includes(searchTerm))) {
                showRow = false;
            }

            if (showRow && categoryValue && row.cells[3].textContent !== categoryFilter.options[categoryFilter.selectedIndex].text) {
                showRow = false;
            }

            if (showRow && stockValue) {
                if (stockValue === 'low-stock' && !isLowStock) showRow = false;
                else if (stockValue === 'out-of-stock' && !isOutOfStock) showRow = false;
                else if (stockValue === 'in-stock' && (isLowStock || isOutOfStock)) showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }
    searchInput.addEventListener('keyup', filterInventory);
    categoryFilter.addEventListener('change', filterInventory);
    stockFilter.addEventListener('change', filterInventory);

    document.querySelectorAll('.modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });

    document.getElementById('new-product-btn').addEventListener('click', () => {
        document.getElementById('modal-title').textContent = 'Add New Product';
        productForm.reset();
        document.getElementById('product-id').value = '';
        document.getElementById('initial-quantity-group').style.display = '';
        document.getElementById('product-quantity').required = true;
        previewImage.src = 'assets/images/product-placeholder.png';
        imagePreview.classList.remove('has-image');
        imageChanged = false;
        productModal.style.display = 'block';
    });

    document.querySelectorAll('.edit-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;

            fetch(`api/product_handler.php?product_id=${productId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const data = result.data;
                        document.getElementById('modal-title').textContent = 'Edit Product';
                        productForm.reset();
                        document.getElementById('product-id').value = data.product_id;
                        document.getElementById('product-name').value = data.name;
                        document.getElementById('product-category').value = data.category_id || '';
                        document.getElementById('product-sku').value = data.sku || '';
                        document.getElementById('product-barcode').value = data.barcode || '';
                        document.getElementById('product-description').value = data.description || '';
                        document.getElementById('product-cost').value = data.cost_price;
                        document.getElementById('product-price').value = data.selling_price;
                        document.getElementById('product-tax').value = data.tax_rate || 0;
                        document.getElementById('product-min-stock').value = data.min_stock_level || 10;
                        document.getElementById('product-max-stock').value = data.max_stock_level || 100;
                        document.getElementById('product-location').value = data.location || '';

                        document.getElementById('initial-quantity-group').style.display = 'none';
                        document.getElementById('product-quantity').required = false;
                        document.getElementById('product-quantity').value = '';

                        // Reset image remove flag
                        document.getElementById('remove-image-flag').value = "0";

                        // Check if product has image and display it
                        if (result.data.image_url) {
                            previewImage.src = result.data.image_url;
                            imagePreview.classList.add('has-image');
                        } else {
                            previewImage.src = 'assets/images/product-placeholder.png';
                            imagePreview.classList.remove('has-image');
                        }
                        imageChanged = false;

                        productModal.style.display = 'block';
                    } else {
                        showToast(result.message || 'Failed to load product data.', false);
                    }
                })
                .catch(error => {
                    console.error('Error fetching product:', error);
                    showToast('Error fetching product data.', false);
                });
        });
    });

    productForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        document.querySelectorAll('.input-error').forEach(el => {
            el.classList.remove('input-error');
        });
        
        const formData = new FormData(this);
        const saveButton = document.getElementById('save-product');
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';

        fetch('api/product_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(data => {
                    if (!response.ok) {
                        return Promise.reject(data);
                    }
                    return data;
                });
            }
            return response.text().then(text => {
                return Promise.reject({
                    message: `Server returned unexpected response: ${text}`,
                    status: response.status
                });
            });
        })
        .then(result => {
            showToast(result.message, result.success);
            if (result.success) {
                productModal.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error saving product:', error);
            
            if (error && typeof error === 'object') {
                if (error.validation_errors) {
                    showToast({
                        message: 'Please correct the following errors:',
                        errors: error.validation_errors
                    }, false);
                } else if (error.db_error) {
                    showToast(`Database error: ${error.db_error}`, false);
                } else if (error.message) {
                    showToast(error.message, false);
                } else {
                    showToast('An error occurred while saving the product.', false);
                }
            } else {
                showToast('An error occurred while saving the product.', false);
            }
        })
        .finally(() => {
             saveButton.disabled = false;
             saveButton.textContent = 'Save Product';
        });
    });

    document.getElementById('cancel-product').addEventListener('click', () => {
        productModal.style.display = 'none';
    });

    document.querySelectorAll('.adjust-stock').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;
            const productName = row.cells[2].textContent;
            const currentStock = row.querySelector('.quantity-value').textContent;

            stockAdjustmentForm.reset();
            document.getElementById('adjustment-product-id').value = productId;
            document.getElementById('adjustment-product-name').textContent = productName;
            document.getElementById('adjustment-current-stock').textContent = currentStock;
            
            // Reset the other reason field
            document.getElementById('other-reason-group').style.display = 'none';
            document.getElementById('other-reason').required = false;
            document.getElementById('other-reason').value = '';
            
            stockModal.style.display = 'block';
        });
    });

    document.getElementById('cancel-adjustment').addEventListener('click', () => {
        stockModal.style.display = 'none';
    });

    stockAdjustmentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const saveButton = document.getElementById('save-adjustment');
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
        
        // Prepare reason for storage - combine reason code with notes
        const reason = formData.get('reason');
        const userNotes = formData.get('notes');
        const otherReason = formData.get('other_reason');
        
        // Create combined notes with the reason clearly indicated
        let combinedNotes = `Reason: ${reason === 'other' ? otherReason : reason}`;
        if (userNotes) {
            combinedNotes += `\nNotes: ${userNotes}`;
        }
        
        // Update the notes field with the combined information
        formData.set('notes', combinedNotes);
        
        // Also set the transaction_type field based on reason
        // Map specific reasons to appropriate transaction types
        let transactionType = 'adjustment'; // default
        if (reason === 'purchase') transactionType = 'purchase';
        if (reason === 'return') transactionType = 'return';
        if (reason === 'stock_count') transactionType = 'stock_count';
        formData.append('transaction_type', transactionType);

        fetch('api/stock_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            showToast(result.message, result.success);
            if (result.success) {
                stockModal.style.display = 'none';
                const productId = formData.get('product_id');
                const row = document.querySelector(`.inventory-table tbody tr[data-product-id="${productId}"]`);
                if (row) {
                    row.querySelector('.quantity-value').textContent = result.new_quantity;
                    calculateSummary();
                } else {
                    location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error adjusting stock:', error);
            showToast('An error occurred while adjusting stock.', false);
        })
        .finally(() => {
             saveButton.disabled = false;
             saveButton.textContent = 'Save Adjustment';
        });
    });

    document.querySelectorAll('.view-history').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;
            const productName = row.cells[2].textContent;
            const currentStock = row.querySelector('.quantity-value').textContent;
            const historyBody = document.getElementById('transaction-history-body');

            document.getElementById('history-product-name').textContent = productName;
            document.getElementById('history-current-stock').textContent = currentStock;
            historyBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Loading history...</td></tr>';
            historyModal.style.display = 'block';

            fetch(`api/history_handler.php?product_id=${productId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        historyBody.innerHTML = '';
                        if (result.data.length > 0) {
                            result.data.forEach(item => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>${item.transaction_date_formatted}</td>
                                    <td>${item.transaction_type}</td>
                                    <td>${item.quantity_change > 0 ? '+' : ''}${item.quantity_change}</td>
                                    <td>${item.before_quantity}</td>
                                    <td>${item.after_quantity}</td>
                                    <td>${item.username}</td>
                                    <td>${item.notes || '—'}</td>
                                    <td>${item.reference_id || '—'}</td>
                                `;
                                historyBody.appendChild(tr);
                            });
                        } else {
                            historyBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No transaction history found.</td></tr>';
                        }
                    } else {
                         historyBody.innerHTML = `<tr><td colspan="8" style="text-align: center;">Error loading history: ${result.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching history:', error);
                     historyBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Failed to load history.</td></tr>';
                });
        });
    });

    document.querySelectorAll('.view-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;

            fetch(`api/product_handler.php?product_id=${productId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const data = result.data;
                        document.getElementById('view-product-id').textContent = data.product_id;
                        document.getElementById('view-product-name').textContent = data.name;
                        document.getElementById('view-product-category').textContent = data.category_name || 'Uncategorized';
                        document.getElementById('view-product-sku').textContent = data.sku || '—';
                        document.getElementById('view-product-barcode').textContent = data.barcode || '—';
                        document.getElementById('view-product-cost').textContent = `$${parseFloat(data.cost_price).toFixed(2)}`;
                        document.getElementById('view-product-price').textContent = `$${parseFloat(data.selling_price).toFixed(2)}`;
                        document.getElementById('view-product-margin').textContent = `${((data.selling_price - data.cost_price) / data.cost_price * 100).toFixed(2)}%`;
                        document.getElementById('view-product-tax').textContent = `${data.tax_rate || 0}%`;
                        document.getElementById('view-product-stock').textContent = data.quantity || '0';
                        document.getElementById('view-product-min-stock').textContent = data.min_stock_level || '0';
                        document.getElementById('view-product-max-stock').textContent = data.max_stock_level || '0';
                        document.getElementById('view-product-location').textContent = data.location || '—';
                        document.getElementById('view-product-restock-date').textContent = data.last_restock_date ? new Date(data.last_restock_date).toLocaleDateString() : '—';
                        document.getElementById('view-product-description').textContent = data.description || '—';
                        document.getElementById('view-product-created').textContent = data.created_at ? new Date(data.created_at).toLocaleDateString() : '—';
                        document.getElementById('view-product-updated').textContent = data.updated_at ? new Date(data.updated_at).toLocaleDateString() : '—';

                        const productImage = document.getElementById('view-product-image');
                        if (data.image_url) {
                            productImage.src = data.image_url;
                        } else {
                            productImage.src = 'assets/images/product-placeholder.png';
                        }

                        viewProductModal.style.display = 'block';

                        setTimeout(() => {
                            updateStockLevelIndicator();
                            updatePriceBreakdown();
                        }, 100);
                    } else {
                        showToast(result.message || 'Failed to load product details.', false);
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    showToast('Error fetching product details.', false);
                });
        });
    });

    document.getElementById('view-product-close').addEventListener('click', () => {
        viewProductModal.style.display = 'none';
    });

    document.getElementById('view-product-edit').addEventListener('click', () => {
        const productId = document.getElementById('view-product-id').textContent;
        const editButton = document.querySelector(`.edit-product[data-id="${productId}"]`);
        if (editButton) {
            editButton.click();
        }
        viewProductModal.style.display = 'none';
    });

    const generateSkuBtn = document.getElementById('generate-sku');
    const skuFormatSelect = document.getElementById('sku-format');
    const customFormatOptions = document.getElementById('custom-format-options');
    const customSkuFormat = document.getElementById('custom-sku-format');
    const skuPreviewValue = document.getElementById('sku-preview-value');
    const applySkuBtn = document.getElementById('apply-sku');
    const cancelSkuGeneratorBtn = document.getElementById('cancel-sku-generator');
    
    generateSkuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        updateSkuPreview();
        skuGeneratorModal.style.display = 'block';
    });
    
    skuFormatSelect.addEventListener('change', function() {
        if (this.value === 'CUSTOM') {
            customFormatOptions.style.display = 'block';
        } else {
            customFormatOptions.style.display = 'none';
        }
        updateSkuPreview();
    });
    
    customSkuFormat.addEventListener('input', updateSkuPreview);
    
    function updateSkuPreview() {
        const format = skuFormatSelect.value;
        const productName = document.getElementById('product-name').value || 'Product';
        const categorySelect = document.getElementById('product-category');
        const categoryText = categorySelect.options[categorySelect.selectedIndex]?.text || 'Category';
        const categoryInitial = categoryText.substring(0, 3).toUpperCase();
        const locationSelect = document.getElementById('product-location');
        const locationText = locationSelect.options[locationSelect.selectedIndex]?.text || 'LOC';
        const locationInitial = locationText.substring(0, 3).toUpperCase();
        const sequentialNum = String(Math.floor(Math.random() * 999) + 1).padStart(3, '0');
        const currentYear = new Date().getFullYear();
        
        let skuValue;
        
        switch(format) {
            case 'CAT-SEQ-YEAR':
                skuValue = `${categoryInitial}-${sequentialNum}-${currentYear}`;
                break;
            case 'BR-CAT-SEQ':
                const brandInitial = (productName.charAt(0) || 'P').toUpperCase();
                skuValue = `${brandInitial}-${categoryInitial}-${sequentialNum}`;
                break;
            case 'LOC-CAT-SEQ':
                skuValue = `${locationInitial}-${categoryInitial}-${sequentialNum}`;
                break;
            case 'CAT-SUB-SEQ':
                const subcategory = 'SUB'; // This would typically come from a subcategory field
                skuValue = `${categoryInitial}-${subcategory}-${sequentialNum}`;
                break;
            case 'CUSTOM':
                let template = customSkuFormat.value || '[CAT]-[SEQ]-[YEAR]';
                skuValue = template
                    .replace('[CAT]', categoryInitial)
                    .replace('[BRAND]', (productName.charAt(0) || 'P').toUpperCase())
                    .replace('[SEQ]', sequentialNum)
                    .replace('[YEAR]', currentYear)
                    .replace('[LOC]', locationInitial);
                break;
            default:
                skuValue = `${categoryInitial}-${sequentialNum}`;
        }
        
        skuPreviewValue.textContent = skuValue;
    }
    
    applySkuBtn.addEventListener('click', function() {
        document.getElementById('product-sku').value = skuPreviewValue.textContent;
        skuGeneratorModal.style.display = 'none';
    });
    
    cancelSkuGeneratorBtn.addEventListener('click', function() {
        skuGeneratorModal.style.display = 'none';
    });
    
    document.querySelectorAll('#sku-generator-modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            skuGeneratorModal.style.display = 'none';
        });
    });

    const productImage = document.getElementById('product-image');
    const previewImage = document.getElementById('preview-image');
    const removeImageBtn = document.getElementById('remove-image');
    const imagePreview = document.getElementById('image-preview');
    const removeImageFlag = document.getElementById('remove-image-flag');
    let imageChanged = false;

    productImage.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                showToast('Image size must be less than 2MB', false);
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                previewImage.src = event.target.result;
                imagePreview.classList.add('has-image');
                imageChanged = true;
                removeImageFlag.value = "0"; // Reset remove flag if new image is selected
            }
            reader.readAsDataURL(file);
        }
    });

    removeImageBtn.addEventListener('click', function() {
        previewImage.src = 'assets/images/product-placeholder.png';
        productImage.value = '';
        imagePreview.classList.remove('has-image');
        imageChanged = true;
        removeImageFlag.value = "1"; // Set remove flag when image is removed
    });

    // Update the product row's edit handler to display the saved image
    document.querySelectorAll('.edit-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;

            fetch(`api/product_handler.php?product_id=${productId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const data = result.data;
                        document.getElementById('modal-title').textContent = 'Edit Product';
                        productForm.reset();
                        document.getElementById('product-id').value = data.product_id;
                        document.getElementById('product-name').value = data.name;
                        document.getElementById('product-category').value = data.category_id || '';
                        document.getElementById('product-sku').value = data.sku || '';
                        document.getElementById('product-barcode').value = data.barcode || '';
                        document.getElementById('product-description').value = data.description || '';
                        document.getElementById('product-cost').value = data.cost_price;
                        document.getElementById('product-price').value = data.selling_price;
                        document.getElementById('product-tax').value = data.tax_rate || 0;
                        document.getElementById('product-min-stock').value = data.min_stock_level || 10;
                        document.getElementById('product-max-stock').value = data.max_stock_level || 100;
                        document.getElementById('product-location').value = data.location || '';

                        document.getElementById('initial-quantity-group').style.display = 'none';
                        document.getElementById('product-quantity').required = false;
                        document.getElementById('product-quantity').value = '';

                        // Reset image remove flag
                        document.getElementById('remove-image-flag').value = "0";

                        // Check if product has image and display it
                        if (result.data.image_url) {
                            previewImage.src = result.data.image_url;
                            imagePreview.classList.add('has-image');
                        } else {
                            previewImage.src = 'assets/images/product-placeholder.png';
                            imagePreview.classList.remove('has-image');
                        }
                        imageChanged = false;

                        productModal.style.display = 'block';
                    } else {
                        showToast(result.message || 'Failed to load product data.', false);
                    }
                })
                .catch(error => {
                    console.error('Error fetching product:', error);
                    showToast('Error fetching product data.', false);
                });
        });
    });

    // Also update the inventory table to show actual product images
    function updateProductImages() {
        document.querySelectorAll('.inventory-table .product-image img').forEach(img => {
            const row = img.closest('tr');
            const productId = row.dataset.productId;
            
            fetch(`api/product_handler.php?product_id=${productId}&get_image=true`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.image_url && result.image_url !== '../assets/images/product-placeholder.png') {
                        img.src = result.image_url;
                    }
                })
                .catch(error => console.error('Error loading image:', error));
        });
    }
    
    // Call once on page load
    updateProductImages();

    // Tab switching functionality for product view modal
    document.querySelectorAll('.product-view-tabs .tab-btn').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.product-view-tabs .tab-btn').forEach(t => {
                t.classList.remove('active');
            });
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab panels
            document.querySelectorAll('.product-view-content .tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Show selected tab panel
            const tabId = this.getAttribute('data-tab');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });

    // Update stock level indicator in the view product modal
    function updateStockLevelIndicator() {
        const stockElement = document.getElementById('view-product-stock');
        const stockBadge = document.getElementById('view-product-stock-badge');
        const stockDetail = document.getElementById('view-product-stock-detail');
        const progressBar = document.getElementById('stock-level-progress');
        
        if (!stockElement || !stockBadge || !stockDetail || !progressBar) return;
        
        const currentStock = parseInt(stockElement.textContent) || 0;
        const minStock = parseInt(document.getElementById('view-product-min-stock').textContent) || 10;
        const maxStock = parseInt(document.getElementById('view-product-max-stock').textContent) || 100;
        
        // Update stock badge styling based on stock level
        stockBadge.className = 'stock-badge';
        stockDetail.textContent = `${currentStock} units`;
        
        if (currentStock <= 0) {
            stockBadge.classList.add('out-of-stock');
            stockBadge.textContent = 'Out of Stock';
            progressBar.style.width = '0%';
            progressBar.style.backgroundColor = '#f44336';
        } else if (currentStock <= minStock) {
            stockBadge.classList.add('low-stock');
            stockBadge.textContent = 'Low Stock';
            const percentage = Math.min(Math.max((currentStock / maxStock * 100), 0), 100);
            progressBar.style.width = `${percentage}%`;
            progressBar.style.backgroundColor = '#ff9800';
        } else {
            stockBadge.classList.add('in-stock');
            stockBadge.textContent = 'In Stock';
            const percentage = Math.min(Math.max((currentStock / maxStock * 100), 0), 100);
            progressBar.style.width = `${percentage}%`;
            progressBar.style.backgroundColor = '#2ecc71';
        }
        
        // Update labels
        document.getElementById('mid-stock-level').textContent = Math.floor(maxStock / 2);
        document.getElementById('max-stock-level').textContent = maxStock;
    }

    // Update price breakdown chart in the view product modal
    function updatePriceBreakdown() {
        const costElement = document.getElementById('view-product-cost');
        const priceElement = document.getElementById('view-product-price');
        const taxElement = document.getElementById('view-product-tax');
        const marginElement = document.getElementById('view-product-margin');
        
        if (!costElement || !priceElement || !taxElement || !marginElement) return;
        
        // Extract values (remove currency symbols)
        const costPrice = parseFloat(costElement.textContent.replace(/[^0-9.-]+/g, '')) || 0;
        const sellingPrice = parseFloat(priceElement.textContent.replace(/[^0-9.-]+/g, '')) || 0;
        const taxRate = parseFloat(taxElement.textContent.replace(/[^0-9.-]+/g, '')) / 100 || 0;
        
        // Calculate margin
        const margin = sellingPrice - costPrice;
        const marginPercent = costPrice > 0 ? (margin / sellingPrice * 100).toFixed(2) : 0;
        marginElement.textContent = `${marginPercent}%`;
        document.getElementById('view-profit-margin').textContent = `${marginPercent}%`;
        
        // Calculate inventory value
        const stockQty = parseInt(document.getElementById('view-product-stock').textContent) || 0;
        const inventoryValue = (costPrice * stockQty).toFixed(2);
        document.getElementById('view-inventory-value').textContent = `$${inventoryValue}`;
        
        // Update the price breakdown chart
        const costSegment = document.getElementById('cost-segment');
        const marginSegment = document.getElementById('margin-segment');
        const taxSegment = document.getElementById('tax-segment');
        
        if (costSegment && marginSegment && taxSegment) {
            if (sellingPrice > 0) {
                const costPercent = (costPrice / sellingPrice * 100).toFixed(2);
                const taxAmount = sellingPrice * taxRate;
                const taxPercent = (taxAmount / sellingPrice * 100).toFixed(2);
                const marginPercent = (100 - parseFloat(costPercent) - parseFloat(taxPercent)).toFixed(2);
                
                costSegment.style.width = `${costPercent}%`;
                taxSegment.style.width = `${taxPercent}%`;
                marginSegment.style.width = `${marginPercent}%`;
                
                costSegment.setAttribute('title', `Cost: $${costPrice.toFixed(2)} (${costPercent}%)`);
                taxSegment.setAttribute('title', `Tax: $${taxAmount.toFixed(2)} (${taxPercent}%)`);
                marginSegment.setAttribute('title', `Margin: $${margin.toFixed(2)} (${marginPercent}%)`);
                
                // Add content inside segments if width allows
                costSegment.innerHTML = parseFloat(costPercent) > 15 ? `<span>Cost: ${costPercent}%</span>` : '';
                marginSegment.innerHTML = parseFloat(marginPercent) > 15 ? `<span>Margin: ${marginPercent}%</span>` : '';
                taxSegment.innerHTML = parseFloat(taxPercent) > 15 ? `<span>Tax: ${taxPercent}%</span>` : '';
            } else {
                costSegment.style.width = '100%';
                taxSegment.style.width = '0%';
                marginSegment.style.width = '0%';
            }
        }
    }

    // View History Button Handler
    document.getElementById('view-product-history').addEventListener('click', () => {
        const productId = document.getElementById('view-product-id').textContent;
        const productName = document.getElementById('view-product-name').textContent;
        const currentStock = document.getElementById('view-product-stock').textContent;
        
        document.getElementById('history-product-name').textContent = productName;
        document.getElementById('history-current-stock').textContent = currentStock;
        
        // Fetch transaction history for the product
        fetch(`api/history_handler.php?product_id=${productId}`)
            .then(response => response.json())
            .then(result => {
                const historyBody = document.getElementById('transaction-history-body');
                
                if (result.success && result.data && result.data.length > 0) {
                    historyBody.innerHTML = result.data.map(item => `
                        <tr>
                            <td>${item.transaction_date_formatted}</td>
                            <td>${item.transaction_type}</td>
                            <td class="${parseInt(item.quantity_change) >= 0 ? 'positive' : 'negative'}">${item.quantity_change}</td>
                            <td>${item.before_quantity}</td>
                            <td>${item.after_quantity}</td>
                            <td>${item.username || 'System'}</td>
                            <td>${item.notes || '—'}</td>
                            <td>${item.reference_id || '—'}</td>
                        </tr>
                    `).join('');
                } else {
                    historyBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No history found for this product.</td></tr>';
                }
                
                viewProductModal.style.display = 'none';
                historyModal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching history:', error);
                showToast('Error loading transaction history.', false);
            });
    });

    // Adjust Stock Button Handler
    document.getElementById('view-product-adjust').addEventListener('click', () => {
        const productId = document.getElementById('view-product-id').textContent;
        const productName = document.getElementById('view-product-name').textContent;
        const currentStock = document.getElementById('view-product-stock').textContent;
        
        stockAdjustmentForm.reset();
        document.getElementById('adjustment-product-id').value = productId;
        document.getElementById('adjustment-product-name').textContent = productName;
        document.getElementById('adjustment-current-stock').textContent = currentStock;
        
        // Reset the other reason field
        document.getElementById('other-reason-group').style.display = 'none';
        document.getElementById('other-reason').required = false;
        document.getElementById('other-reason').value = '';
        
        viewProductModal.style.display = 'none';
        stockModal.style.display = 'block';
    });

    // Print Product Details Handler
    document.getElementById('view-product-print').addEventListener('click', () => {
        const productName = document.getElementById('view-product-name').textContent;
        const productId = document.getElementById('view-product-id').textContent;
        const sku = document.getElementById('view-product-sku').textContent;
        const barcode = document.getElementById('view-product-barcode').textContent;
        const category = document.getElementById('view-product-category').textContent;
        const stock = document.getElementById('view-product-stock').textContent;
        const price = document.getElementById('view-product-price').textContent;
        const description = document.getElementById('view-product-description').textContent;
        
        // Create a printable version of the product details
        const printContent = `
            <div class="print-header">
                <h2>Product Details</h2>
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
            <div class="product-print-content">
                <div class="print-section">
                    <h3>${productName}</h3>
                    <p><strong>ID:</strong> ${productId}</p>
                    <p><strong>SKU:</strong> ${sku}</p>
                    <p><strong>Barcode:</strong> ${barcode}</p>
                    <p><strong>Category:</strong> ${category}</p>
                    <p><strong>Current Stock:</strong> ${stock}</p>
                    <p><strong>Price:</strong> ${price}</p>
                </div>
                <div class="print-section">
                    <h3>Description</h3>
                    <p>${description || 'No description available.'}</p>
                </div>
            </div>
        `;
        
        // Open a new window for printing
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Print - ${productName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .print-header { margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                        .print-header h2 { margin: 0; }
                        .print-section { margin-bottom: 20px; }
                        h3 { margin-bottom: 10px; }
                        p { margin: 5px 0; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        
        // Trigger print dialog
        setTimeout(() => {
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            }
        }, 500);
    });

    // Ensure tab handling works when the view product modal is displayed
    document.querySelectorAll('.view-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const productId = row.dataset.productId;

            fetch(`api/product_handler.php?product_id=${productId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        const data = result.data;
                        document.getElementById('view-product-id').textContent = data.product_id;
                        document.getElementById('view-product-name').textContent = data.name;
                        document.getElementById('view-product-category').textContent = data.category_name || 'Uncategorized';
                        document.getElementById('view-product-sku').textContent = data.sku || '—';
                        document.getElementById('view-product-barcode').textContent = data.barcode || '—';
                        document.getElementById('view-product-cost').textContent = `$${parseFloat(data.cost_price).toFixed(2)}`;
                        document.getElementById('view-product-price').textContent = `$${parseFloat(data.selling_price).toFixed(2)}`;
                        document.getElementById('view-product-margin').textContent = `${((data.selling_price - data.cost_price) / data.cost_price * 100).toFixed(2)}%`;
                        document.getElementById('view-product-tax').textContent = `${data.tax_rate || 0}%`;
                        document.getElementById('view-product-stock').textContent = data.quantity || '0';
                        document.getElementById('view-product-min-stock').textContent = data.min_stock_level || '0';
                        document.getElementById('view-product-max-stock').textContent = data.max_stock_level || '0';
                        document.getElementById('view-product-location').textContent = data.location || '—';
                        document.getElementById('view-product-restock-date').textContent = data.last_restock_date ? new Date(data.last_restock_date).toLocaleDateString() : '—';
                        document.getElementById('view-product-description').textContent = data.description || '—';
                        document.getElementById('view-product-created').textContent = data.created_at ? new Date(data.created_at).toLocaleDateString() : '—';
                        document.getElementById('view-product-updated').textContent = data.updated_at ? new Date(data.updated_at).toLocaleDateString() : '—';

                        const productImage = document.getElementById('view-product-image');
                        if (data.image_url) {
                            productImage.src = data.image_url;
                        } else {
                            productImage.src = 'assets/images/product-placeholder.png';
                        }

                        viewProductModal.style.display = 'block';

                        setTimeout(() => {
                            updateStockLevelIndicator();
                            updatePriceBreakdown();
                        }, 100);
                    } else {
                        showToast(result.message || 'Failed to load product details.', false);
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    showToast('Error fetching product details.', false);
                });

            // Make sure the first tab is active by default when opening the modal
            document.querySelectorAll('.product-view-tabs .tab-btn').forEach((tab, index) => {
                if (index === 0) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.product-view-content .tab-panel').forEach((panel, index) => {
                if (index === 0) {
                    panel.classList.add('active');
                } else {
                    panel.classList.remove('active');
                }
            });
        });
    });

    // Add reason dropdown change event to show/hide "other" field
    document.getElementById('adjustment-reason').addEventListener('change', function() {
        const otherReasonGroup = document.getElementById('other-reason-group');
        if (this.value === 'other') {
            otherReasonGroup.style.display = 'block';
            document.getElementById('other-reason').required = true;
        } else {
            otherReasonGroup.style.display = 'none';
            document.getElementById('other-reason').required = false;
        }
    });
});
</script>

<style>
    .inventory-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eaeaea;
    }

    .inventory-header h1 {
        margin: 0;
        color: #2c3e50;
        font-size: 24px;
        font-weight: 600;
    }

    .inventory-actions {
        display: flex;
        gap: 12px;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(52, 152, 219, 0.15);
    }

    .btn-primary:hover {
        background-color: #2980b9;
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.25);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background-color: #f8f9fa;
        color: #495057;
        border: 1px solid #ddd;
        padding: 10px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .btn-secondary:hover {
        background-color: #e9ecef;
        color: #212529;
    }

    .btn-primary:active, .btn-secondary:active {
        transform: translateY(1px);
    }

    .inventory-filters {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
        gap: 15px;
    }

    .search-container {
        position: relative;
        flex-grow: 1;
    }

    .search-container input {
        padding: 12px 15px 12px 40px;
        border-radius: 6px;
        border: 1px solid #ddd;
        width: 100%;
        font-size: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }

    .search-container input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }

    .filter-container {
        display: flex;
        gap: 12px;
    }

    .filter-container select {
        padding: 10px 30px 10px 15px;
        border-radius: 6px;
        border: 1px solid #ddd;
        background-color: white;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23495057' class='bi bi-chevron-down' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1 .708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }

    .filter-container select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    .inventory-table-container {
        overflow-x: auto;
        margin-bottom: 30px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        background-color: white;
    }

    .inventory-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .inventory-table th, .inventory-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    .inventory-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
        position: sticky;
        top: 0;
        z-index: 1;
        box-shadow: 0 1px 0 rgba(0,0,0,0.1);
    }

    .inventory-table tbody tr {
        transition: all 0.2s ease;
    }

    .inventory-table tbody tr:hover {
        background-color: #f0f7fc;
    }

    .product-image img {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: transform 0.2s ease;
    }

    .product-image img:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .quantity-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .low-stock {
        background-color: #fff8e1;
    }

    .low-stock:hover {
        background-color: #ffecb3 !important;
    }

    .out-of-stock {
        background-color: #ffebee;
    }

    .out-of-stock:hover {
        background-color: #ffcdd2 !important;
    }

    .stock-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stock-badge.low {
        background-color: #ff9800;
    }

    .stock-badge.out {
        background-color: #f44336;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .btn-icon {
        background: none;
        border: 1px solid transparent;
        cursor: pointer;
        padding: 8px;
        font-size: 14px;
        border-radius: 6px;
        color: #6c757d;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon:hover {
        background-color: #e9ecef;
        color: #212529;
        border-color: #ced4da;
    }

    .btn-icon.edit-product:hover {
        color: #3498db;
        background-color: rgba(52, 152, 219, 0.1);
    }

    .btn-icon.adjust-stock:hover {
        color: #2ecc71;
        background-color: rgba(46, 204, 113, 0.1);
    }

    .btn-icon.view-history:hover {
        color: #9b59b6;
        background-color: rgba(155, 89, 182, 0.1);
    }

    .btn-icon.view-product:hover {
        color: #3498db;
        background-color: rgba(52, 152, 219, 0.1);
    }

    .no-records {
        text-align: center;
        padding: 60px 0;
        color: #6c757d;
        font-style: italic;
        background-color: #f8f9fa;
    }

    .inventory-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
    }

    .summary-card {
        background-color: white;
        border-radius: 8px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        text-align: center;
        transition: all 0.3s ease;
        border: 1px solid #eaeaea;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }

    .summary-card h3 {
        margin: 0 0 15px 0;
        color: #6c757d;
        font-size: 16px;
        font-weight: 500;
    }

    .summary-card .count {
        font-size: 28px;
        margin: 0;
        font-weight: 600;
        color: #2c3e50;
    }

    .summary-card:nth-child(1) {
        border-top: 3px solid #3498db;
    }

    .summary-card:nth-child(2) {
        border-top: 3px solid #ff9800;
    }

    .summary-card:nth-child(3) {
        border-top: 3px solid #f44336;
    }

    .summary-card:nth-child(4) {
        border-top: 3px solid #2ecc71;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(3px);
        animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .modal-content {
        position: relative;
        background-color: white;
        margin: 60px auto;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        animation: modalSlideIn 0.3s ease;
        max-height: 85vh;
        overflow-y: auto;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-40px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal .close {
        position: absolute;
        top: 18px;
        right: 25px;
        font-size: 22px;
        cursor: pointer;
        color: #adb5bd;
        transition: color 0.2s ease;
        height: 30px;
        width: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .modal .close:hover {
        color: #212529;
        background-color: #f8f9fa;
    }

    .modal h2 {
        margin-top: 0;
        margin-bottom: 25px;
        color: #2c3e50;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 15px;
        font-weight: 600;
        font-size: 20px;
    }

    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        flex: 1;
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #495057;
        font-size: 14px;
    }

    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
    }

    .product-info-display {
        background-color: #f8f9fa;
        padding: 18px;
        border-radius: 8px;
        margin-bottom: 25px;
        border-left: 4px solid #3498db;
    }

    .product-info-display p {
        margin: 8px 0;
        font-size: 15px;
        color: #495057;
    }

    .product-info-display strong {
        font-weight: 600;
        color: #2c3e50;
    }

    .history-table-container {
        max-height: 450px;
        overflow-y: auto;
        margin-top: 20px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
    }

    .history-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .history-table th, .history-table td {
        padding: 12px 16px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
        font-size: 13px;
    }

    .history-table th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 1;
        font-weight: 600;
        color: #495057;
        box-shadow: 0 1px 0 rgba(0,0,0,0.1);
    }

    .history-table td:nth-child(3),
    .history-table td:nth-child(4),
    .history-table td:nth-child(5) {
        text-align: right;
    }

    .history-table td:nth-child(8) {
        text-align: center;
    }

    .modal-content.large {
        max-width: 1000px;
    }

    @media (max-width: 768px) {
        .inventory-header, .inventory-filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        .inventory-actions, .filter-container {
            margin-top: 15px;
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .inventory-summary {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
        
        .action-buttons {
            flex-wrap: wrap;
        }
    }

    .image-upload-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        width: 100%;
    }

    .image-upload-preview {
        width: 150px;
        height: 150px;
        border-radius: 8px;
        overflow: hidden;
        background-color: #f8f9fa;
        border: 2px dashed #ced4da;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .image-upload-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .image-upload-preview.has-image {
        border: 2px solid #3498db;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
    }

    .image-upload-preview:hover {
        border-color: #3498db;
    }

    .image-upload-controls {
        display: flex;
        gap: 10px;
        width: 100%;
        justify-content: center;
    }

    input[type="file"] {
        display: none;
    }

    .custom-file-upload {
        background-color: #3498db;
        color: white;
        padding: 10px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(52, 152, 219, 0.15);
    }

    .custom-file-upload:hover {
        background-color: #2980b9;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.25);
    }

    .btn-text {
        background: none;
        border: none;
        color: #dc3545;
        padding: 10px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .btn-text:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .image-upload-hint {
        font-size: 12px;
        color: #6c757d;
        text-align: center;
    }

    .image-upload-preview.drag-over {
        background-color: rgba(52, 152, 219, 0.1);
        border: 2px dashed #3498db;
    }

    @media (max-width: 576px) {
        .image-upload-controls {
            flex-direction: column;
            align-items: center;
        }
    }

    .sku-input-group {
        display: flex;
        align-items: center;
        position: relative;
    }

    .sku-input-group input {
        width: calc(100% - 40px);
        padding-right: 40px;
    }

    .sku-input-group .btn-icon {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #3498db;
        padding: 8px 12px;
        cursor: pointer;
    }

    .sku-input-group .btn-icon:hover {
        color: #2980b9;
    }

    .sku-preview {
        margin: 20px 0;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 6px;
    }

    .sku-preview label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #495057;
    }

    .sku-preview-value {
        font-size: 24px;
        font-weight: bold;
        color: #3498db;
        text-align: center;
        padding: 10px;
        border: 1px dashed #ced4da;
        border-radius: 4px;
        background: white;
    }

    .format-help {
        margin-top: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        font-size: 12px;
    }

    .format-help p {
        margin: 0 0 5px 0;
        font-weight: 500;
    }

    .format-help ul {
        margin: 0;
        padding-left: 20px;
    }

    .format-help code {
        background-color: #e9ecef;
        padding: 2px 4px;
        border-radius: 3px;
        font-family: monospace;
    }

    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1100;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    }

    .toast {
        background-color: white;
        color: #333;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: flex-start;
        gap: 15px;
        opacity: 0;
        transform: translateX(50px);
        transition: all 0.3s ease;
        border-left: 4px solid #333;
        width: 100%;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast.success {
        border-left-color: #2ecc71;
    }

    .toast.success i {
        color: #2ecc71;
    }

    .toast.error {
        border-left-color: #e74c3c;
    }

    .toast.error i {
        color: #e74c3c;
    }

    .toast i {
        font-size: 20px;
        margin-top: 3px;
    }

    .toast-message {
        flex-grow: 1;
    }

    .toast-title {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }

    .toast-text {
        font-size: 14px;
        color: #666;
        line-height: 1.4;
    }

    .toast-close {
        background: none;
        border: none;
        color: #aaa;
        font-size: 18px;
        cursor: pointer;
        padding: 0 5px;
        margin-left: 10px;
        align-self: flex-start;
    }

    .toast-close:hover {
        color: #333;
    }

    .error-list {
        margin: 5px 0 0 0;
        padding-left: 20px;
        font-size: 13px;
    }

    .error-list li {
        margin-bottom: 5px;
    }

    .input-error {
        border-color: #e74c3c !important;
        background-color: rgba(231, 76, 60, 0.05);
    }

    .input-error:focus {
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.15) !important;
    }

    /* Enhanced Product View Modal Styles */
    .product-detail-modal {
        max-width: 900px;
        padding: 0;
        overflow: hidden;
        border-radius: 12px;
    }
    
    .product-detail-header {
        background-color: #f8f9fa;
        padding: 25px 30px;
        border-bottom: 1px solid #e9ecef;
        position: relative;
    }
    
    .product-detail-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .product-detail-title h2 {
        margin: 0;
        font-size: 24px;
        color: #2c3e50;
        font-weight: 600;
        padding: 0;
        border: none;
    }
    
    .product-badge-container {
        display: flex;
        gap: 10px;
    }
    
    .product-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-status.active {
        background-color: rgba(46, 204, 113, 0.15);
        color: #2ecc71;
    }
    
    .stock-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stock-badge.in-stock {
        background-color: rgba(46, 204, 113, 0.15);
        color: #2ecc71;
    }
    
    .stock-badge.low {
        background-color: rgba(255, 152, 0, 0.15);
        color: #ff9800;
    }
    
    .stock-badge.out {
        background-color: rgba(244, 67, 54, 0.15);
        color: #f44336;
    }
    
    .product-quick-metrics {
        display: flex;
        gap: 20px;
    }
    
    .metric {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 15px;
        border-radius: 8px;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        min-width: 120px;
    }
    
    .metric-value {
        font-size: 22px;
        font-weight: 600;
    }
    
    .metric-label {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .text-danger {
        color: #dc3545;
    }
    
    .text-warning {
        color: #ffc107;
    }
    
    .text-success {
        color: #28a745;
    }
    
    .product-view-container {
        display: flex;
        padding: 25px 30px;
    }
    
    .product-view-left {
        width: 220px;
        margin-right: 25px;
    }
    
    .product-view-image-container {
        width: 220px;
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }
    
    .product-view-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .product-quick-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
        border-radius: 6px;
        border: none;
        background-color: #f8f9fa;
        color: #495057;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
        width: 100%;
    }
    
    .action-btn:hover {
        background-color: #e9ecef;
    }
    
    .product-view-right {
        flex: 1;
    }
    
    .product-view-tabs {
        display: flex;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 20px;
        gap: 2px;
    }
    
    .tab-btn {
        padding: 12px 16px;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        font-weight: 500;
        color: #6c757d;
        transition: all 0.2s;
    }
    
    .tab-btn:hover {
        color: #3498db;
    }
    
    .tab-btn.active {
        color: #3498db;
        border-bottom-color: #3498db;
    }
    
    .product-view-content {
        position: relative;
        min-height: 300px;
    }
    
    .tab-panel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s;
    }
    
    .tab-panel.active {
        position: relative;
        opacity: 1;
        visibility: visible;
    }
    
    .product-view-details {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 20px;
    }
    
    .product-view-details th {
        width: 40%;
        padding: 10px 0;
        text-align: left;
        font-weight: 500;
        color: #6c757d;
        vertical-align: top;
    }
    
    .product-view-details td {
        padding: 10px 0;
        font-weight: 500;
        color: #495057;
    }
    
    .product-view-description {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        line-height: 1.5;
        color: #495057;
        white-space: pre-wrap;
    }
    
    .product-view-footer {
        padding: 20px 30px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    .stock-level-indicator {
        margin-top: 20px;
    }
    
    .stock-level-bar-container {
        width: 100%;
    }
    
    .stock-level-labels {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
        color: #6c757d;
    }
    
    .stock-level-bar {
        height: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
        position: relative;
    }
    
    .stock-level-progress {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        border-radius: 5px;
        transition: width 0.5s;
    }
    
    .price-breakdown {
        margin-top: 25px;
    }
    
    .price-breakdown h4 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 16px;
        color: #495057;
    }
    
    .breakdown-chart {
        display: flex;
        height: 40px;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    
    .cost-segment, .margin-segment, .tax-segment {
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .cost-segment {
        background-color: #3498db;
    }
    
    .margin-segment {
        background-color: #2ecc71;
    }
    
    .tax-segment {
        background-color: #f39c12;
    }
    
    @media (max-width: 768px) {
        .product-view-container {
            flex-direction: column;
            padding: 20px;
        }
        
        .product-view-left {
            width: 100%;
            margin-right: 0;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .product-view-image-container {
            width: 180px;
            height: 180px;
        }
        
        .product-quick-actions {
            margin-top: 10px;
            flex-direction: row;
            width: 100%;
        }
        
        .product-quick-metrics {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .metric {
            flex: 1;
            min-width: 100px;
        }
        
        .product-detail-title {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .product-badge-container {
            margin-top: 10px;
        }
    }
</style>

<?php
include 'footer.php';
?>