<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Check if this is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Check if product_id is provided
if (!isset($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

try {
    $product_id = intval($_GET['product_id']);
    
    // Query to get transaction history
    $query = "SELECT 
                t.transaction_id,
                t.product_id,
                t.transaction_type,
                t.adjustment_type,
                t.quantity_change,
                t.before_quantity,
                t.after_quantity,
                t.transaction_date,
                DATE_FORMAT(t.transaction_date, '%Y-%m-%d %H:%i:%s') as transaction_date_formatted,
                t.notes,
                t.reference_id,
                u.username
            FROM 
                inventory_transactions t
            LEFT JOIN 
                users u ON t.user_id = u.user_id
            WHERE 
                t.product_id = ?
            ORDER BY 
                t.transaction_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    
    while($row = $result->fetch_assoc()) {
        // Format adjustment_type for display if available
        if (!empty($row['adjustment_type'])) {
            $row['adjustment_type'] = ucfirst($row['adjustment_type']);
        }
        
        // Parse transaction notes to extract reason if available
        if (!empty($row['notes'])) {
            // Try to extract reason from notes using regex
            if (preg_match('/Reason: ([^\n]+)/i', $row['notes'], $matches)) {
                $row['reason'] = $matches[1];
            }
        }
        
        $transactions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transactions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
