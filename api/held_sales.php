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

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve held sales
        if (isset($_GET['id'])) {
            // Get specific held sale
            $held_sale_id = intval($_GET['id']);
            
            // Get held sale header
            $sale_query = "SELECT hs.*, c.name as customer_name 
                          FROM held_sales hs 
                          LEFT JOIN customers c ON hs.customer_id = c.customer_id 
                          WHERE hs.held_sale_id = ?";
            
            $stmt = $conn->prepare($sale_query);
            $stmt->bind_param('i', $held_sale_id);
            $stmt->execute();
            $sale_result = $stmt->get_result();
            
            if ($sale_result->num_rows > 0) {
                $sale = $sale_result->fetch_assoc();
                
                // Get held sale items
                $items_query = "SELECT * FROM held_sale_items WHERE held_sale_id = ?";
                $stmt = $conn->prepare($items_query);
                $stmt->bind_param('i', $held_sale_id);
                $stmt->execute();
                $items_result = $stmt->get_result();
                
                $items = [];
                while ($item = $items_result->fetch_assoc()) {
                    $items[] = [
                        'id' => $item['product_id'],
                        'name' => $item['name'],
                        'price' => (float)$item['unit_price'],
                        'quantity' => (int)$item['quantity'],
                        'total' => (float)$item['subtotal']
                    ];
                }
                
                $customer_name = $sale['customer_name'] ?? 'Walk-in Customer';
                
                $response = [
                    'success' => true,
                    'sale' => [
                        'id' => $sale['held_sale_id'],
                        'items' => $items,
                        'customer' => $sale['customer_id'],
                        'customer_name' => $customer_name,
                        'timestamp' => $sale['created_at'],
                        'note' => $sale['note'],
                        'total' => (float)$sale['total_amount']
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Held sale not found'];
            }
        } else {
            // Get all held sales
            $query = "SELECT hs.*, c.name as customer_name,
                     (SELECT COUNT(*) FROM held_sale_items WHERE held_sale_id = hs.held_sale_id) as item_count
                     FROM held_sales hs
                     LEFT JOIN customers c ON hs.customer_id = c.customer_id 
                     ORDER BY hs.created_at DESC";
            
            $result = $conn->query($query);
            $held_sales = [];
            
            while ($sale = $result->fetch_assoc()) {
                $customer_name = $sale['customer_name'] ?? 'Walk-in Customer';
                
                $held_sales[] = [
                    'id' => 'hold-' . $sale['held_sale_id'], // Keep the 'hold-' prefix for compatibility
                    'customer' => $sale['customer_id'],
                    'customer_name' => $customer_name,
                    'timestamp' => $sale['created_at'],
                    'total_amount' => (float)$sale['total_amount'],
                    'item_count' => (int)$sale['item_count']
                ];
            }
            
            $response = ['success' => true, 'held_sales' => $held_sales];
        }
        break;
        
    case 'POST':
        // Save a new held sale
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['items']) || empty($data['items'])) {
            $response = ['success' => false, 'message' => 'No items in the sale'];
            break;
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Calculate the total amount from items
            $total_amount = 0;
            foreach ($data['items'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            // Create held sale record
            $customer_id = isset($data['customer']) && $data['customer'] !== '0' ? $data['customer'] : NULL;
            $note = $data['note'] ?? NULL;
            
            $sale_query = "INSERT INTO held_sales (user_id, customer_id, note, total_amount) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sale_query);
            $stmt->bind_param('iisd', $user_id, $customer_id, $note, $total_amount);
            $stmt->execute();
            
            $held_sale_id = $conn->insert_id;
            
            // Insert held sale items
            $item_query = "INSERT INTO held_sale_items (held_sale_id, product_id, name, quantity, unit_price, subtotal) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            
            foreach ($data['items'] as $item) {
                $product_id = $item['id'];
                $name = $item['name'];
                $quantity = $item['quantity'];
                $unit_price = $item['price'];
                $subtotal = $item['price'] * $item['quantity'];
                
                $stmt->bind_param('iisids', $held_sale_id, $product_id, $name, $quantity, $unit_price, $subtotal);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            $response = [
                'success' => true, 
                'message' => 'Sale held successfully',
                'held_sale_id' => $held_sale_id
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['success' => false, 'message' => 'Error saving held sale: ' . $e->getMessage()];
        }
        break;
        
    case 'DELETE':
        // Delete a held sale
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            $response = ['success' => false, 'message' => 'No sale ID provided'];
            break;
        }
        
        $sale_id = intval(str_replace('hold-', '', $data['id']));
        
        // Delete the sale (items will be deleted by cascade)
        $query = "DELETE FROM held_sales WHERE held_sale_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $sale_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Held sale deleted successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting held sale'];
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Invalid request method'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
