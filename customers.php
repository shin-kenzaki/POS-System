<?php
include 'header.php';
?>
<div class="dashboard-content">
    <div class="customers-header">
        <h1>Customer Management</h1>
        <button id="add-customer-btn" class="btn-primary"><i class="fas fa-plus"></i> Add Customer</button>
    </div>

    <div class="filter-container">
        <div class="search-wrapper">
            <input type="text" id="customer-search" placeholder="Search customers...">
            <div class="search-icon"><i class="fas fa-search"></i></div>
        </div>
        <div class="filter-options">
            <select id="loyalty-filter">
                <option value="">All Loyalty Levels</option>
                <option value="high">High (100+ points)</option>
                <option value="medium">Medium (50-99 points)</option>
                <option value="low">Low (1-49 points)</option>
                <option value="none">None (0 points)</option>
            </select>
        </div>
    </div>

    <div class="customers-container">
        <div class="customers-table-container">
            <table class="customers-table" id="customers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Loyalty Points</th>
                        <th>Address</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="loading-row">
                        <td colspan="8" class="text-center">Loading customers...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="pagination-controls" class="pagination-controls"></div>
    </div>

    <!-- Add/Edit Customer Modal -->
    <div id="customer-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title">Add New Customer</h2>
            <form id="customer-form">
                <input type="hidden" id="customer-id" name="customer_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer-name">Full Name *</label>
                        <input type="text" id="customer-name" name="name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer-email">Email</label>
                        <input type="email" id="customer-email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="customer-phone">Phone</label>
                        <input type="tel" id="customer-phone" name="phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer-address">Address</label>
                        <textarea id="customer-address" name="address" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="form-row" id="loyalty-points-container">
                    <div class="form-group">
                        <label for="customer-loyalty">Loyalty Points</label>
                        <input type="number" id="customer-loyalty" name="loyalty_points" min="0" value="0">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancel-customer">Cancel</button>
                    <button type="submit" class="btn-primary" id="save-customer">Save Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Customer Modal -->
    <div id="view-customer-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="customer-detail-header">
                <h2 id="view-customer-name">Customer Name</h2>
                <div class="loyalty-badge" id="loyalty-badge">0 Points</div>
            </div>
            
            <div class="customer-details">
                <div class="detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value" id="view-customer-email">-</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value" id="view-customer-phone">-</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value" id="view-customer-address">-</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Created:</div>
                    <div class="detail-value" id="view-customer-created">-</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Last Updated:</div>
                    <div class="detail-value" id="view-customer-updated">-</div>
                </div>
            </div>
            
            <div class="customer-purchases" id="customer-purchases">
                <h3>Recent Purchases</h3>
                <div class="purchases-container">
                    <p class="no-purchases">No recent purchase history</p>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" id="close-view-customer">Close</button>
                <button type="button" class="btn-primary" id="edit-from-view">Edit Customer</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Confirm Deletion</h2>
            <p>Are you sure you want to delete customer <strong id="delete-customer-name"></strong>?</p>
            <p class="warning-text">This action cannot be undone.</p>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" id="cancel-delete">Cancel</button>
                <button type="button" class="btn-danger" id="confirm-delete">Delete Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS and JS for customer management -->
<link rel="stylesheet" href="assets/css/customers.css">
<script src="assets/js/customers.js"></script>

<?php
include 'footer.php';
?>