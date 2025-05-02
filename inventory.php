<?php
include 'header.php';
require_once 'db.php';

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

// Get categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
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
                <?php while($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                <?php endwhile; ?>
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
                        <tr class="<?php echo $stock_class; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-category-id="<?php echo $item['category_id']; ?>">
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

    <!-- Add/Edit Product Modal -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Add New Product</h2>
            <form id="product-form">
                <input type="hidden" id="product-id" name="product_id" value="">
                
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
                            // Reset the categories result pointer
                            $categories_result->data_seek(0);
                            while($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="product-sku">SKU</label>
                        <input type="text" id="product-sku" name="sku">
                    </div>
                    <div class="form-group">
                        <label for="product-barcode">Barcode</label>
                        <input type="text" id="product-barcode" name="barcode">
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
                        <input type="text" id="product-location" name="location">
                    </div>
                    <div class="form-group">
                        <label for="product-image">Product Image</label>
                        <input type="file" id="product-image" name="image" disabled>
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

                <div class="form-group">
                    <label for="adjustment-notes">Notes</label>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productModal = document.getElementById('product-modal');
    const stockModal = document.getElementById('stock-modal');
    const historyModal = document.getElementById('history-modal');
    const productForm = document.getElementById('product-form');
    const stockAdjustmentForm = document.getElementById('stock-adjustment-form');
    const rows = document.querySelectorAll('.inventory-table tbody tr');

    function showToast(message, isSuccess = true) {
        alert(`${isSuccess ? 'Success' : 'Error'}: ${message}`);
        if (isSuccess) {
            location.reload();
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
        const formData = new FormData(this);
        const saveButton = document.getElementById('save-product');
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';

        fetch('api/product_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            showToast(result.message, result.success);
            if (result.success) {
                productModal.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error saving product:', error);
            showToast('An error occurred while saving.', false);
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
});
</script>

<style>
.inventory-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.inventory-actions {
    display: flex;
    gap: 10px;
}

.inventory-filters {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 15px;
}

.search-container {
    position: relative;
    flex-grow: 1;
}

.search-container input {
    padding: 10px 15px 10px 40px;
    border-radius: 4px;
    border: 1px solid #ddd;
    width: 100%;
    font-size: 14px;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.filter-container {
    display: flex;
    gap: 10px;
}

.filter-container select {
    padding: 8px 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background-color: white;
}

.inventory-table-container {
    overflow-x: auto;
    margin-bottom: 20px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.inventory-table {
    width: 100%;
    border-collapse: collapse;
}

.inventory-table th, .inventory-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.inventory-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    color: #333;
    position: sticky;
    top: 0;
    z-index: 1;
}

.inventory-table tbody tr:hover {
    background-color: #f9f9f9;
}

.product-image img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.quantity-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.low-stock {
    background-color: #fff3e0;
}

.out-of-stock {
    background-color: #ffebee;
}

.stock-badge {
    padding: 2px 6px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}

.stock-badge.low {
    background-color: #ff9800;
}

.stock-badge.out {
    background-color: #f44336;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    font-size: 14px;
    border-radius: 4px;
    color: #555;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background-color: #e0e0e0;
    color: #333;
}

.no-records {
    text-align: center;
    padding: 50px 0;
    color: #666;
}

.inventory-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background-color: white;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 10px 0;
    color: #555;
    font-size: 16px;
}

.summary-card .count {
    font-size: 24px;
    margin: 0;
    font-weight: bold;
    color: #333;
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
}

.modal-content {
    position: relative;
    background-color: white;
    margin: 50px auto;
    padding: 25px;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal .close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
}

.modal h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 15px;
}

.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    flex: 1;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 14px;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.product-info-display {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.product-info-display p {
    margin: 5px 0;
}

.history-table-container {
    max-height: 400px;
    overflow-y: auto;
    margin-top: 15px;
    border: 1px solid #e0e0e0;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th, .history-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
    font-size: 13px;
}

.history-table th {
    position: sticky;
    top: 0;
    background-color: #f5f5f5;
    z-index: 1;
    font-weight: bold;
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
</style>

<?php
include 'footer.php';
?>