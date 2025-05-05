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

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get the sale data from the request body
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    $response['message'] = 'No items in the sale';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Start a database transaction to ensure all operations succeed or fail together
$conn->begin_transaction();

try {
    // 1. Create a sales record
    $customer_id = isset($data['customer']) && $data['customer'] !== '0' ? $data['customer'] : null;
    $payment_method = $data['payment_method'];
    $subtotal = $data['subtotal'];
    $tax_amount = $data['tax_amount'];
    $discount_amount = $data['discount_amount'];
    $total_amount = $data['total_amount'];
    $notes = isset($data['notes']) ? $data['notes'] : null;
    
    // Use NULL for customer_id if it's empty or '0'
    if ($customer_id === '0' || $customer_id === 0) {
        $customer_id = null;
    }
    
    $sale_query = "INSERT INTO sales (user_id, customer_id, subtotal, tax_amount, discount_amount, 
                   total_amount, payment_method, notes)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sale_query);
    
    if (!$stmt) {
        throw new Exception("Error preparing sales statement: " . $conn->error);
    }
    
    $stmt->bind_param(
        'iiddddss',
        $user_id,
        $customer_id,
        $subtotal,
        $tax_amount,
        $discount_amount,
        $total_amount,
        $payment_method,
        $notes
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing sales insert: " . $stmt->error);
    }
    
    $sale_id = $conn->insert_id;
    
    // 2. Create sale items records and update inventory
    foreach ($data['items'] as $item) {
        $product_id = $item['id'];
        $quantity = $item['quantity'];
        $unit_price = $item['price'];
        $discount = 0; // Could be added as a feature later
        
        // Get product details and current inventory
        $product_query = "SELECT p.cost_price, p.tax_rate, i.quantity 
                          FROM products p 
                          JOIN inventory i ON p.product_id = i.product_id 
                          WHERE p.product_id = ?";
        
        $stmt_product = $conn->prepare($product_query);
        if (!$stmt_product) {
            throw new Exception("Error preparing product query: " . $conn->error);
        }
        
        $stmt_product->bind_param('i', $product_id);
        $stmt_product->execute();
        $product_result = $stmt_product->get_result();
        
        if ($product_result->num_rows === 0) {
            throw new Exception("Product not found: ID " . $product_id);
        }
        
        $product_data = $product_result->fetch_assoc();
        $current_stock = $product_data['quantity'];
        
        if ($current_stock < $quantity) {
            throw new Exception("Insufficient stock for product ID " . $product_id);
        }
        
        // Use tax amount from the frontend if available, otherwise calculate it
        $item_tax = isset($item['taxAmount']) ? $item['taxAmount'] : ($unit_price * $quantity * $product_data['tax_rate']);
        $subtotal = $unit_price * $quantity;
        
        // Insert sale item
        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, discount, tax_amount, subtotal) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_items = $conn->prepare($item_query);
        if (!$stmt_items) {
            throw new Exception("Error preparing sale items statement: " . $conn->error);
        }
        
        $stmt_items->bind_param(
            'iiidddd', 
            $sale_id, 
            $product_id, 
            $quantity, 
            $unit_price, 
            $discount, 
            $item_tax, 
            $subtotal
        );
        
        if (!$stmt_items->execute()) {
            throw new Exception("Error inserting sale item: " . $stmt_items->error);
        }
        
        // Update inventory
        $inventory_update_query = "UPDATE inventory SET quantity = quantity - ? WHERE product_id = ?";
        $stmt_inventory = $conn->prepare($inventory_update_query);
        if (!$stmt_inventory) {
            throw new Exception("Error preparing inventory update: " . $conn->error);
        }
        
        $stmt_inventory->bind_param('ii', $quantity, $product_id);
        if (!$stmt_inventory->execute()) {
            throw new Exception("Error updating inventory: " . $stmt_inventory->error);
        }
        
        // Record inventory transaction
        $new_quantity = $current_stock - $quantity;
        $transaction_note = "Sale #$sale_id";
        $transaction_type = 'sale';
        $negative_quantity = -$quantity; // Negative for sales
        
        $inventory_transaction_query = "INSERT INTO inventory_transactions 
                                       (product_id, user_id, transaction_type, reference_id, quantity_change, 
                                        before_quantity, after_quantity, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_inventory_transaction = $conn->prepare($inventory_transaction_query);
        if (!$stmt_inventory_transaction) {
            throw new Exception("Error preparing inventory transaction: " . $conn->error);
        }
        
        $stmt_inventory_transaction->bind_param(
            'iisiiiss',
            $product_id,
            $user_id,
            $transaction_type,
            $sale_id,
            $negative_quantity,
            $current_stock,
            $new_quantity,
            $transaction_note
        );
        
        if (!$stmt_inventory_transaction->execute()) {
            throw new Exception("Error recording inventory transaction: " . $stmt_inventory_transaction->error);
        }
    }
    
    // 3. Create a payment record
    $payment_query = "INSERT INTO payments (sale_id, amount, payment_method, payment_reference, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $payment_reference = isset($data['payment_reference']) ? $data['payment_reference'] : null;
    
    $stmt_payment = $conn->prepare($payment_query);
    if (!$stmt_payment) {
        throw new Exception("Error preparing payment statement: " . $conn->error);
    }
    
    $stmt_payment->bind_param(
        'idsssi',
        $sale_id,
        $total_amount,
        $payment_method,
        $payment_reference,
        $notes,
        $user_id
    );
    
    if (!$stmt_payment->execute()) {
        throw new Exception("Error recording payment: " . $stmt_payment->error);
    }
    
    // If there was a held sale that's being processed, delete it
    if (isset($data['held_sale_id'])) {
        $held_sale_id = $data['held_sale_id'];
        $delete_held = $conn->prepare("DELETE FROM held_sales WHERE held_sale_id = ?");
        if ($delete_held) {
            $delete_held->bind_param('i', $held_sale_id);
            $delete_held->execute();
        }
    }
    
    // All operations completed successfully, commit the transaction
    $conn->commit();
    
    // Create a receipt ID (could be formatted based on business requirements)
    $receipt_id = 'RCPT-' . date('Ymd') . '-' . $sale_id;
    
    $response = [
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale_id' => $sale_id,
        'receipt_id' => $receipt_id,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    // Something went wrong, rollback the transaction
    $conn->rollback();
    $response = [
        'success' => false,
        'message' => 'Error processing sale: ' . $e->getMessage()
    ];
} finally {
    // Close prepared statements if they exist
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_items)) $stmt_items->close();
    if (isset($stmt_inventory)) $stmt_inventory->close();
    if (isset($stmt_inventory_transaction)) $stmt_inventory_transaction->close();
    if (isset($stmt_payment)) $stmt_payment->close();
    if (isset($stmt_product)) $stmt_product->close();
    if (isset($delete_held)) $delete_held->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
