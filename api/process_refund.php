<?php
// Include database connection
require_once '../db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get refund data
$sale_id = isset($_POST['sale_id']) ? intval($_POST['sale_id']) : 0;
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
$other_reason = isset($_POST['other_reason']) ? $_POST['other_reason'] : '';
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';
$refund_items = isset($_POST['refund_items']) ? json_decode($_POST['refund_items'], true) : [];

// Validate request
if (empty($sale_id)) {
    $response['message'] = 'Sale ID is required';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (empty($reason)) {
    $response['message'] = 'Refund reason is required';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($reason === 'other' && empty($other_reason)) {
    $response['message'] = 'Please specify the reason for refund';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (empty($refund_items)) {
    $response['message'] = 'No items selected for refund';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // First get the sale details
    $sale_query = "SELECT * FROM sales WHERE sale_id = ?";
    $stmt = $conn->prepare($sale_query);
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $sale_result = $stmt->get_result();
    
    if ($sale_result->num_rows === 0) {
        throw new Exception("Sale not found");
    }
    
    $sale = $sale_result->fetch_assoc();
    
    // Check if the sale has already been refunded
    if ($sale['payment_status'] === 'refunded') {
        throw new Exception("This sale has already been refunded");
    }
    
    // Calculate refund amount and validate refunded quantities
    $refund_total = 0;
    $items_to_refund = [];
    
    foreach ($refund_items as $item) {
        if (isset($item['selected']) && $item['selected'] == 1) {
            // Get the original sale item
            $item_query = "SELECT * FROM sale_items WHERE sale_item_id = ?";
            $stmt = $conn->prepare($item_query);
            $stmt->bind_param('i', $item['sale_item_id']);
            $stmt->execute();
            $item_result = $stmt->get_result();
            
            if ($item_result->num_rows === 0) {
                throw new Exception("Sale item not found: " . $item['sale_item_id']);
            }
            
            $sale_item = $item_result->fetch_assoc();
            
            // Validate refund quantity
            $refund_qty = intval($item['quantity']);
            if ($refund_qty <= 0 || $refund_qty > $sale_item['quantity']) {
                throw new Exception("Invalid refund quantity for item: " . $sale_item['product_id']);
            }
            
            // Calculate refund amount for this item
            $item_refund_amount = $refund_qty * $sale_item['unit_price'];
            $refund_total += $item_refund_amount;
            
            $items_to_refund[] = [
                'sale_item_id' => $sale_item['sale_item_id'],
                'product_id' => $sale_item['product_id'],
                'quantity' => $refund_qty,
                'amount' => $item_refund_amount,
                'original_quantity' => $sale_item['quantity']
            ];
        }
    }
    
    if (empty($items_to_refund)) {
        throw new Exception("No items selected for refund");
    }
    
    // Update the sale status
    $update_sale_query = "UPDATE sales SET payment_status = 'refunded', updated_at = NOW() WHERE sale_id = ?";
    $stmt = $conn->prepare($update_sale_query);
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    
    // Create refund record
    $refund_reason = ($reason === 'other') ? $other_reason : $reason;
    
    $refund_query = "INSERT INTO refunds (sale_id, user_id, refund_amount, refund_date, reason, notes) 
                    VALUES (?, ?, ?, NOW(), ?, ?)";
    $stmt = $conn->prepare($refund_query);
    $stmt->bind_param('iidss', $sale_id, $user_id, $refund_total, $refund_reason, $notes);
    $stmt->execute();
    
    $refund_id = $conn->insert_id;
    
    // Insert refund items and restore inventory
    foreach ($items_to_refund as $item) {
        // Insert refund item
        $refund_item_query = "INSERT INTO refund_items (refund_id, product_id, quantity, amount) 
                             VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($refund_item_query);
        $stmt->bind_param('iiid', $refund_id, $item['product_id'], $item['quantity'], $item['amount']);
        $stmt->execute();
        
        // Restore inventory
        $inventory_query = "UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?";
        $stmt = $conn->prepare($inventory_query);
        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt->execute();
        
        // Record inventory transaction
        $before_query = "SELECT quantity FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($before_query);
        $stmt->bind_param('i', $item['product_id']);
        $stmt->execute();
        $before_result = $stmt->get_result();
        $after_quantity = $before_result->fetch_assoc()['quantity'];
        $before_quantity = $after_quantity - $item['quantity'];
        
        $transaction_query = "INSERT INTO inventory_transactions 
                             (product_id, user_id, transaction_type, reference_id, quantity_change, 
                              before_quantity, after_quantity, notes, reason) 
                             VALUES (?, ?, 'return', ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($transaction_query);
        $transaction_note = "Refund from sale #$sale_id";
        $stmt->bind_param('iiiiisss', 
                         $item['product_id'], 
                         $user_id, 
                         $refund_id, 
                         $item['quantity'], 
                         $before_quantity, 
                         $after_quantity, 
                         $transaction_note, 
                         $refund_reason);
        $stmt->execute();
    }
    
    // Commit the transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "Refund processed successfully";
    $response['refund_id'] = $refund_id;
    $response['refund_amount'] = $refund_total;
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
