<!-- filepath: c:\xampp\htdocs\POS-System\includes\view_product_modal.php -->
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
                                <th>Storage Location:</th>
                                <td id="view-product-location">-</td>
                            </tr>
                        </table>
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
                        
                        <div class="price-breakdown-container">
                            <h4>Price Breakdown</h4>
                            <div class="price-breakdown-chart">
                                <div id="cost-segment" class="cost-segment">Cost</div>
                                <div id="margin-segment" class="margin-segment">Margin</div>
                                <div id="tax-segment" class="tax-segment">Tax</div>
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
        
        <!-- Print Template (Hidden) -->
        <div id="print-template" style="display:none;">
            <!-- ...existing code... -->
        </div>
    </div>
</div>