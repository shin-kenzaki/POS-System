<?php
session_start();
require_once '../db.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Handle GET request - Fetch product details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    $sql = "SELECT p.*, i.quantity, i.min_stock_level, i.max_stock_level, i.location 
            FROM products p 
            LEFT JOIN inventory i ON p.product_id = i.product_id 
            WHERE p.product_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    
    $stmt->close();
    exit;
}

// Handle POST request - Create or update product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $product_id = isset($_POST['product_id']) && !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $name = trim($_POST['name']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $sku = !empty($_POST['sku']) ? trim($_POST['sku']) : null;
    $barcode = !empty($_POST['barcode']) ? trim($_POST['barcode']) : null;
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $cost_price = floatval($_POST['cost_price']);
    $selling_price = floatval($_POST['selling_price']);
    $tax_rate = !empty($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
    $min_stock_level = !empty($_POST['min_stock_level']) ? intval($_POST['min_stock_level']) : 10;
    $max_stock_level = !empty($_POST['max_stock_level']) ? intval($_POST['max_stock_level']) : 100;
    $location = !empty($_POST['location']) ? trim($_POST['location']) : null;
    
    $conn->begin_transaction();
    
    try {
        // Check if it's an update or new product
        if ($product_id) {
            // Update existing product
            $sql = "UPDATE products SET 
                    name = ?, 
                    category_id = ?, 
                    sku = ?, 
                    barcode = ?, 
                    description = ?, 
                    cost_price = ?, 
                    selling_price = ?, 
                    tax_rate = ? 
                    WHERE product_id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisssdddi", $name, $category_id, $sku, $barcode, $description, $cost_price, $selling_price, $tax_rate, $product_id);
            $stmt->execute();
            
            // Update inventory settings
            $sql = "UPDATE inventory SET 
                    min_stock_level = ?, 
                    max_stock_level = ?, 
                    location = ? 
                    WHERE product_id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $min_stock_level, $max_stock_level, $location, $product_id);
            $stmt->execute();
            
            $message = "Product updated successfully";
        } else {
            // Insert new product
            $sql = "INSERT INTO products (name, category_id, sku, barcode, description, cost_price, selling_price, tax_rate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sisssdd", $name, $category_id, $sku, $barcode, $description, $cost_price, $selling_price, $tax_rate);
            $stmt->execute();
            
            $product_id = $stmt->insert_id;
            
            // Add initial inventory record
            $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : 0;
            $sql = "INSERT INTO inventory (product_id, quantity, min_stock_level, max_stock_level, location) 
                    VALUES (?, ?, ?, ?, ?)";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiis", $product_id, $quantity, $min_stock_level, $max_stock_level, $location);
            $stmt->execute();
            
            // Record inventory transaction for initial quantity
            if ($quantity > 0) {
                $transaction_type = 'adjustment';
                $notes = 'Initial inventory';
                $user_id = $_SESSION['user_id'];
                
                $sql = "INSERT INTO inventory_transactions 
                        (product_id, user_id, transaction_type, quantity_change, before_quantity, after_quantity, notes) 
                        VALUES (?, ?, ?, ?, 0, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisiss", $product_id, $user_id, $transaction_type, $quantity, $quantity, $notes);
                $stmt->execute();
            }
            
            $message = "Product created successfully";
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => $message, 'product_id' => $product_id]);
        
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
