<?php
// Include database connection
require_once '../db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '<div class="error-message">User not authenticated</div>';
    exit;
}

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    echo '<div class="error-message">Sale ID is required</div>';
    exit;
}

$sale_id = intval($_GET['sale_id']);

// Get sale details
$sale_query = "SELECT s.*, c.name AS customer_name, u.full_name AS cashier_name
               FROM sales s
               LEFT JOIN customers c ON s.customer_id = c.customer_id
               LEFT JOIN users u ON s.user_id = u.user_id
               WHERE s.sale_id = ?";
               
$stmt = $conn->prepare($sale_query);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();

if ($sale_result->num_rows === 0) {
    echo '<div class="error-message">Sale not found</div>';
    exit;
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_query = "SELECT si.*, p.name
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                WHERE si.sale_id = ?";
                
$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Get store settings
$settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_name', 'store_address', 'store_phone', 'receipt_footer')";
$settings_result = $conn->query($settings_query);
$settings = [];

while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Generate receipt HTML
?>

<div class="receipt">
    <div class="receipt-header">
        <h2><?php echo htmlspecialchars($settings['store_name'] ?? 'My POS Store'); ?></h2>
        <p><?php echo htmlspecialchars($settings['store_address'] ?? ''); ?></p>
        <p>Tel: <?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?></p>
        <p>-------------------------------------</p>
        <p>Receipt #: SALE-<?php echo str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT); ?></p>
        <p>Date: <?php echo date('Y-m-d H:i', strtotime($sale['sale_date'])); ?></p>
        <p>Cashier: <?php echo htmlspecialchars($sale['cashier_name'] ?? 'System'); ?></p>
        <?php if ($sale['customer_name']): ?>
            <p>Customer: <?php echo htmlspecialchars($sale['customer_name']); ?></p>
        <?php endif; ?>
        <p>-------------------------------------</p>
    </div>
    
    <div class="receipt-items">
        <table style="width: 100%;">
            <tr>
                <th style="text-align: left;">Item</th>
                <th style="text-align: right;">Qty</th>
                <th style="text-align: right;">Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
            <?php while ($item = $items_result->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: left;"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td style="text-align: right;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;">$<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td style="text-align: right;">$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
    
    <div class="receipt-summary">
        <p>Subtotal: $<?php echo number_format($sale['subtotal'], 2); ?></p>
        <?php if ($sale['tax_amount'] > 0): ?>
            <p>Tax: $<?php echo number_format($sale['tax_amount'], 2); ?></p>
        <?php endif; ?>
        <?php if ($sale['discount_amount'] > 0): ?>
            <p>Discount: $<?php echo number_format($sale['discount_amount'], 2); ?></p>
        <?php endif; ?>
        <p class="receipt-total">Total: $<?php echo number_format($sale['total_amount'], 2); ?></p>
        <p>Paid via: <?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></p>
        <?php if ($sale['payment_status'] === 'refunded'): ?>
            <p class="refunded-status">*** REFUNDED ***</p>
        <?php endif; ?>
    </div>
    
    <div class="receipt-footer">
        <p><?php echo htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for shopping with us!'); ?></p>
        <?php if ($sale['payment_status'] === 'refunded'): ?>
            <p>This sale has been refunded.</p>
        <?php endif; ?>
    </div>
</div>
