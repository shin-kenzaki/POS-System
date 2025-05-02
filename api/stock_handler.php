<?php
session_start();
require_once '../db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Handle POST request - Process stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $reason = $_POST['reason'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validate inputs
    if ($product_id <= 0 || $quantity <= 0 || empty($adjustment_type)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    // Get current stock level
    $sql = "SELECT quantity FROM inventory WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found in inventory']);
        exit;
    }
    
    $current_stock = $result->fetch_assoc()['quantity'];
    $new_quantity = 0;
    $quantity_change = 0;
    
    // Calculate new quantity based on adjustment type
    switch ($adjustment_type) {
        case 'add':
            $new_quantity = $current_stock + $quantity;
            $quantity_change = $quantity;
            break;
            
        case 'remove':
            if ($current_stock < $quantity) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot remove more than available stock. Current stock: ' . $current_stock
                ]);
                exit;
            }
            $new_quantity = $current_stock - $quantity;
            $quantity_change = -$quantity;
            break;
            
        case 'set':
            $new_quantity = $quantity;
            $quantity_change = $quantity - $current_stock;
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid adjustment type']);
            exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update inventory quantity
        $sql = "UPDATE inventory SET quantity = ?, last_restock_date = CURRENT_TIMESTAMP WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $new_quantity, $product_id);
        $stmt->execute();
        
        // Record transaction
        $transaction_type = ($reason === 'other') ? 'adjustment' : $reason;
        
        $sql = "INSERT INTO inventory_transactions 
                (product_id, user_id, transaction_type, quantity_change, before_quantity, after_quantity, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisiiis", $product_id, $user_id, $transaction_type, $quantity_change, $current_stock, $new_quantity, $notes);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Stock adjusted successfully', 
            'new_quantity' => $new_quantity
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    
    exit;
}

// If we get here, it's an invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
