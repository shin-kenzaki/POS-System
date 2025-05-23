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
    $adjustment_type = $_POST['adjustment_type']; // 'add', 'remove', or 'set'
    $quantity = intval($_POST['quantity']);
    $reason_code = $_POST['reason'];
    $other_reason = isset($_POST['other_reason']) ? trim($_POST['other_reason']) : '';
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
    
    // Map reason_code to transaction_type and db_adjustment_type
    $transaction_type = 'adjustment'; // default
    $db_adjustment_type = null;
    
    switch ($reason_code) {
        case 'purchase':
            $transaction_type = 'purchase';
            break;
        case 'return':
            $transaction_type = 'return';
            break;
        case 'stock_count':
            $transaction_type = 'stock_count';
            break;
        case 'damaged':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'damage';
            break;
        case 'expired':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'expiry';
            break;
        case 'theft':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'theft';
            break;
        case 'lost':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'loss';
            break;
        case 'found':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'found';
            break;
        case 'correction':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'correction';
            break;
        case 'quality_issue':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'quality_issue';
            break;
        case 'other':
            $transaction_type = 'adjustment';
            $db_adjustment_type = 'other';
            break;
    }
    
    // If notes are empty, provide a generic note based on adjustment type and reason
    if (empty($notes)) {
        switch ($adjustment_type) {
            case 'add':
                $notes = "Added $quantity items.";
                break;
            case 'remove':
                $notes = "Removed $quantity items.";
                break;
            case 'set':
                $notes = "Stock level manually set to $quantity.";
                break;
        }
    }
    
    // Add transaction record - now includes proper adjustment_type field and reason
    $transaction_stmt = $conn->prepare("INSERT INTO inventory_transactions 
                                     (product_id, user_id, transaction_type, reference_id, 
                                     quantity_change, before_quantity, after_quantity, 
                                     notes, reason, adjustment_type) 
                                     VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)");
                                     
    $transaction_stmt->bind_param("iisiissss", 
                              $product_id, 
                              $user_id, 
                              $transaction_type, 
                              $quantity_change, 
                              $current_quantity, 
                              $new_quantity, 
                              $notes,
                              $reason_text,
                              $db_adjustment_type);
                              
    if (!$transaction_stmt->execute()) {
        throw new Exception("Failed to record transaction: " . $transaction_stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'new_quantity' => $new_quantity,
        'product_id' => $product_id,
        'transaction_type' => $transaction_type,
        'adjustment_type' => $db_adjustment_type
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
