<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Validate required parameters
    $required_fields = ['product_id', 'adjustment_type', 'quantity', 'reason'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }
    
    $product_id = intval($_POST['product_id']);
    $adjustment_type = $_POST['adjustment_type'];
    $quantity = intval($_POST['quantity']);
    $reason = $_POST['reason'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    if ($quantity <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Quantity must be greater than zero'
        ]);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get current quantity
    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Product inventory not found");
    }
    
    $row = $result->fetch_assoc();
    $current_quantity = $row['quantity'];
    $new_quantity = $current_quantity;
    $quantity_change = 0;
    
    // Calculate new quantity based on adjustment type
    switch ($adjustment_type) {
        case 'add':
            $new_quantity = $current_quantity + $quantity;
            $quantity_change = $quantity;
            break;
            
        case 'remove':
            $new_quantity = $current_quantity - $quantity;
            if ($new_quantity < 0) {
                $new_quantity = 0; // Prevent negative inventory
                $quantity_change = -$current_quantity;
            } else {
                $quantity_change = -$quantity;
            }
            break;
            
        case 'set':
            $new_quantity = $quantity;
            $quantity_change = $quantity - $current_quantity;
            break;
            
        default:
            throw new Exception("Invalid adjustment type");
    }
    
    // Update inventory quantity
    $stmt = $conn->prepare("UPDATE inventory SET 
                            quantity = ?, 
                            last_restock_date = CASE WHEN ? > 0 THEN CURRENT_TIMESTAMP ELSE last_restock_date END, 
                            updated_at = CURRENT_TIMESTAMP 
                            WHERE product_id = ?");
    $stmt->bind_param("iii", $new_quantity, $quantity_change, $product_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update inventory: " . $stmt->error);
    }
    
    // Map reason to transaction_type
    $transaction_type = 'adjustment'; // default
    switch ($reason) {
        case 'purchase':
            $transaction_type = 'purchase';
            break;
        case 'return':
            $transaction_type = 'return';
            break;
        case 'damaged':
            $transaction_type = 'adjustment';
            break;
        case 'correction':
            $transaction_type = 'adjustment';
            break;
        case 'stock_count':
            $transaction_type = 'stock_count';
            break;
    }
    
    // Add transaction record
    $transaction_stmt = $conn->prepare("INSERT INTO inventory_transactions 
                                     (product_id, user_id, transaction_type, quantity_change, before_quantity, after_quantity, notes) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
                                     
    $transaction_stmt->bind_param("iisiiis", 
                              $product_id, 
                              $user_id, 
                              $transaction_type, 
                              $quantity_change, 
                              $current_quantity, 
                              $new_quantity, 
                              $notes);
                              
    if (!$transaction_stmt->execute()) {
        throw new Exception("Failed to record transaction: " . $transaction_stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_quantity' => $new_quantity,
        'product_id' => $product_id
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
