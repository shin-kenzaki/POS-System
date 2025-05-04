<?php
include 'header.php';
require_once 'db.php';

// Get payment methods from database schema
$payment_methods = ['cash', 'credit_card', 'debit_card', 'mobile_payment', 'other'];

// Get summary statistics
$today = date('Y-m-d');
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$start_of_month = date('Y-m-d', strtotime('first day of this month'));

// Daily sales
$daily_sales_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM sales WHERE DATE(sale_date) = ?";
$stmt = $conn->prepare($daily_sales_query);
$stmt->bind_param('s', $today);
$stmt->execute();
$daily_sales_result = $stmt->get_result()->fetch_assoc();
$daily_sales_count = $daily_sales_result['count'] ?? 0;
$daily_sales_total = $daily_sales_result['total'] ?? 0;

// Weekly sales
$weekly_sales_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM sales WHERE sale_date >= ?";
$stmt = $conn->prepare($weekly_sales_query);
$stmt->bind_param('s', $start_of_week);
$stmt->execute();
$weekly_sales_result = $stmt->get_result()->fetch_assoc();
$weekly_sales_count = $weekly_sales_result['count'] ?? 0;
$weekly_sales_total = $weekly_sales_result['total'] ?? 0;

// Monthly sales
$monthly_sales_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM sales WHERE sale_date >= ?";
$stmt = $conn->prepare($monthly_sales_query);
$stmt->bind_param('s', $start_of_month);
$stmt->execute();
$monthly_sales_result = $stmt->get_result()->fetch_assoc();
$monthly_sales_count = $monthly_sales_result['count'] ?? 0;
$monthly_sales_total = $monthly_sales_result['total'] ?? 0;

// Get most recent sales (limited to 50)
$recent_sales_query = "SELECT s.*, c.name as customer_name, 
                        (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.sale_id) as item_count
                        FROM sales s 
                        LEFT JOIN customers c ON s.customer_id = c.customer_id 
                        ORDER BY s.sale_date DESC LIMIT 50";
$sales_result = $conn->query($recent_sales_query);
?>

<div class="dashboard-content">
    <div class="sales-header">
        <h1>Sales Management</h1>
        <div class="sales-actions">
            <button id="export-sales-btn" class="btn-secondary"><i class="fas fa-file-export"></i> Export</button>
            <a href="pos.php" class="btn-primary"><i class="fas fa-plus"></i> New Sale</a>
        </div>
    </div>

    <div class="sales-summary">
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="summary-content">
                <h3>Today's Sales</h3>
                <div class="summary-data">
                    <p class="summary-value">$<?php echo number_format($daily_sales_total, 2); ?></p>
                    <p class="summary-label"><?php echo $daily_sales_count; ?> transactions</p>
                </div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-calendar-week"></i></div>
            <div class="summary-content">
                <h3>Weekly Sales</h3>
                <div class="summary-data">
                    <p class="summary-value">$<?php echo number_format($weekly_sales_total, 2); ?></p>
                    <p class="summary-label"><?php echo $weekly_sales_count; ?> transactions</p>
                </div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="summary-content">
                <h3>Monthly Sales</h3>
                <div class="summary-data">
                    <p class="summary-value">$<?php echo number_format($monthly_sales_total, 2); ?></p>
                    <p class="summary-label"><?php echo $monthly_sales_count; ?> transactions</p>
                </div>
            </div>
        </div>
    </div>

    <div class="sales-filters">
        <div class="filter-row">
            <div class="filter-group">
                <label>Date Range:</label>
                <div class="date-range-inputs">
                    <input type="date" id="date-from" name="date_from">
                    <span>to</span>
                    <input type="date" id="date-to" name="date_to">
                </div>
            </div>
            
            <div class="filter-group">
                <label for="status-filter">Status:</label>
                <select id="status-filter" name="status">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="refunded">Refunded</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="payment-method-filter">Payment Method:</label>
                <select id="payment-method-filter" name="payment_method">
                    <option value="">All Methods</option>
                    <?php foreach ($payment_methods as $method): ?>
                        <option value="<?php echo $method; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $method)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="filter-row">
            <div class="search-container">
                <input type="text" id="sales-search" placeholder="Search by sale ID, customer name, or reference...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <button id="apply-filters" class="btn-primary">Apply Filters</button>
            <button id="reset-filters" class="btn-secondary">Reset</button>
        </div>
    </div>

    <div class="sales-table-container">
        <table class="sales-table" id="sales-table">
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Date & Time</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if($sales_result && $sales_result->num_rows > 0): ?>
                    <?php while($sale = $sales_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $sale['sale_id']; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($sale['sale_date'])); ?></td>
                            <td><?php echo $sale['customer_name'] ?? 'Walk-in Customer'; ?></td>
                            <td><?php echo $sale['item_count']; ?> item(s)</td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower($sale['payment_status']); ?>">
                                    <?php echo ucfirst($sale['payment_status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td class="action-buttons">
                                <!-- Sale Details and Refund temporarily disabled -->
                                <button class="btn-icon print-receipt" title="Print Receipt" data-id="<?php echo $sale['sale_id']; ?>">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn-icon view-sale" title="View Details" data-id="<?php echo $sale['sale_id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if($sale['payment_status'] !== 'refunded'): ?>
                                <button class="btn-icon process-refund" title="Process Refund" data-id="<?php echo $sale['sale_id']; ?>">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="no-records">No sales records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="pagination-controls" class="pagination-controls"></div>
</div>

<!-- 
=== TEMPORARILY DISABLED FEATURES ===
View Sale Modal and Refund Modal are commented out
-->

<!-- View Sale Modal -->
<div id="view-sale-modal" class="modal" style="display:none;">
    <div class="modal-content large">
        <span class="close">&times;</span>
        <div class="sale-detail-header">
            <h2>Sale Details</h2>
            <div id="sale-id-badge" class="sale-id-badge">#SALE-000000</div>
        </div>
        
        <div class="sale-detail-container">
            <div class="sale-detail-info">
                <div class="detail-section">
                    <h3>Basic Information</h3>
                    <table class="detail-table">
                        <tr>
                            <th>Date & Time:</th>
                            <td id="sale-date">-</td>
                        </tr>
                        <tr>
                            <th>Customer:</th>
                            <td id="sale-customer">-</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span id="sale-status" class="status-badge">-</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Method:</th>
                            <td id="sale-payment-method">-</td>
                        </tr>
                        <tr>
                            <th>Reference:</th>
                            <td id="sale-reference">-</td>
                        </tr>
                        <tr>
                            <th>Processed by:</th>
                            <td id="sale-user">-</td>
                        </tr>
                        <tr>
                            <th>Notes:</th>
                            <td id="sale-notes">-</td>
                        </tr>
                    </table>
                </div>
                <div class="detail-section">
                    <h3>Items Purchased</h3>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Discount</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="sale-items">
                            <tr>
                                <td colspan="5" class="text-center">Loading items...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="sale-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="sale-subtotal">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span id="sale-tax">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span id="sale-discount">$0.00</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Total:</span>
                        <span id="sale-total">$0.00</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button id="close-view-sale" class="btn-secondary">Close</button>
            <button id="print-view-sale" class="btn-primary"><i class="fas fa-print"></i> Print Receipt</button>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div id="refund-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Process Refund</h2>
        
        <form id="refund-form">
            <input type="hidden" id="refund-sale-id" name="sale_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="refund-reason">Reason for Refund *</label>
                    <select id="refund-reason" name="reason" required>btn" class="btn-primary"><i class="fas fa-print"></i> Print</button>
                        <option value="">Select Reason</option>   <button id="close-receipt-btn" class="btn-secondary">Close</button>
                        <option value="customer_return">Customer Return</option>          <option value="">Select Reason</option>
                        <option value="damaged_product">Damaged Product</option>              <option value="customer_return">Customer Return</option>
                        <option value="wrong_product">Wrong Product</option>                  <option value="damaged_product">Damaged Product</option>
                        <option value="pricing_error">Pricing Error</option>                        <option value="wrong_product">Wrong Product</option>
                        <option value="other">Other</option>   <option value="pricing_error">Pricing Error</option>
                    </select>>"refund-modal" class="modal" style="display:none;">
                </div>
                <div class="form-group" id="other-reason-group" style="display:none;">
                    <label for="other-reason">Specify Reason *</label>m-group" id="other-reason-group" style="display:none;">Process Refund</h2>
                    <input type="text" id="other-reason" name="other_reason" placeholder="Please specify reason">            <label for="other-reason">Specify Reason *</label>
                </div>="text" id="other-reason" name="other_reason" placeholder="Please specify reason">
            </div>
            </div>
            <div class="form-group">
                <label for="refund-notes">Additional Notes</label>
                <textarea id="refund-notes" name="notes" rows="3"></textarea>
            </div>xtarea>
            
            <div class="refund-items-container">
                <h3>Items to Refund</h3>ed Product</option>
                <table class="refund-items-table">g Product</option>
                    <thead>
                        <tr>
                            <th width="5%">Refund</th>
                            <th width="45%">Item</th>      <th width="5%">Refund</th>
                            <th width="15%">Qty Purchased</th>eason-group" style="display:none;">
                            <th width="15%">Qty to Refund</th><label for="other-reason">Specify Reason *</label>
                            <th width="20%">Amount</th>r="Please specify reason">
                        </tr>      <th width="20%">Amount</th>
                    </thead>      </tr>
                    <tbody id="refund-items">        </thead>
                        <tr>d-items"><div class="form-group">
                            <td colspan="5" class="text-center">Loading items...</td>
                        </tr>...</td>es" name="notes" rows="3"></textarea>
                    </tbody>      </tr>
                </table>        </tbody>
                
                <div class="refund-summary">
                    <div class="summary-row total-row">
                        <span>Total Refund Amount:</span>ass="summary-row total-row">
                        <span id="refund-total">$0.00</span>n>Total Refund Amount:</span>
                    </div>/span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" id="cancel-refund" class="btn-secondary">Cancel</button>actions">
                <button type="submit" id="process-refund-btn" class="btn-danger">Process Refund</button>="button" id="cancel-refund" class="btn-secondary">Cancel</button>
            </div>cess-refund-btn" class="btn-danger">Process Refund</button>d="refund-items">
        </form>
    </div>
</div>

<link rel="stylesheet" href="assets/css/sales.css">
<script src="assets/js/sales.js"></script>
<style>
.feature-notice {
    background-color: #fff3cd;
    color: #856404;
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 5px;
    border-left: 5px solid #ffeeba;    display: flex;    align-items: center;    gap: 10px;}.feature-notice i {    font-size: 18px;}.feature-notice p {    margin: 0;}
</style>

<?php
include 'footer.php';
?>