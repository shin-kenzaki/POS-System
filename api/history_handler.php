<?php
session_start();
require_once '../db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Handle GET request - Fetch inventory transaction history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    // Validate product ID
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $sql = "SELECT 
                t.transaction_id,
                t.product_id,
                p.name AS product_name,
                t.transaction_type,
                t.quantity_change,
                t.before_quantity,
                t.after_quantity,
                t.transaction_date,
                DATE_FORMAT(t.transaction_date, '%Y-%m-%d %H:%i:%s') AS transaction_date_formatted,
                t.reference_id,
                t.notes,
                u.username
            FROM 
                inventory_transactions t
            JOIN 
                products p ON t.product_id = p.product_id
            LEFT JOIN 
                users u ON t.user_id = u.user_id
            WHERE 
                t.product_id = ?
            ORDER BY 
                t.transaction_date DESC, t.transaction_id DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        // Format transaction type for display
        switch ($row['transaction_type']) {
            case 'purchase':
                $row['transaction_type'] = 'Purchase';
                break;
            case 'sale':
                $row['transaction_type'] = 'Sale';
                break;
            case 'return':
                $row['transaction_type'] = 'Return';
                break;
            case 'adjustment':
                $row['transaction_type'] = 'Adjustment';
                break;
            case 'stock_count':
                $row['transaction_type'] = 'Stock Count';
                break;
            case 'damaged':
                $row['transaction_type'] = 'Damaged/Expired';
                break;
            case 'transfer':
                $row['transaction_type'] = 'Transfer';
                break;
            default:
                $row['transaction_type'] = ucfirst($row['transaction_type']);
        }
        
        $transactions[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $transactions]);
    $stmt->close();
    exit;
}

// If we get here, it's an invalid request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
