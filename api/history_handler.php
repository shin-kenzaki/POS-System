<?php
header('Content-Type: application/json');
require_once '../db.php';

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
    
    // Query to get transaction history with user information
    $stmt = $conn->prepare("SELECT 
                          t.*,
                          DATE_FORMAT(t.transaction_date, '%Y-%m-%d %H:%i') as transaction_date_formatted,
                          CASE
                              WHEN t.transaction_type = 'purchase' THEN 'Purchase'
                              WHEN t.transaction_type = 'sale' THEN 'Sale'
                              WHEN t.transaction_type = 'return' THEN 'Return'
                              WHEN t.transaction_type = 'adjustment' THEN 'Adjustment'
                              WHEN t.transaction_type = 'transfer' THEN 'Transfer'
                              WHEN t.transaction_type = 'stock_count' THEN 'Stock Count'
                              ELSE t.transaction_type
                          END as transaction_type,
                          u.username
                          FROM inventory_transactions t
                          LEFT JOIN users u ON t.user_id = u.user_id
                          WHERE t.product_id = ?
                          ORDER BY t.transaction_date DESC");
                          
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
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
