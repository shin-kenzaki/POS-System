<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Handle GET requests for product data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);
        
        // If only requesting image URL for a product
        if (isset($_GET['get_image']) && $_GET['get_image'] === 'true') {
            try {
                $stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    
                    $image_url = $row['image_url'];
                    
                    // Check if image exists and add proper path if not absolute
                    if ($image_url) {
                        // If it's not an absolute URL (doesn't start with http/https)
                        if (strpos($image_url, 'http') !== 0) {
                            // Don't modify the path if it already has 'img/' in it
                            if (strpos($image_url, 'img/') !== 0 && strpos($image_url, '/img/') !== 0) {
                                $image_url = 'img/' . $image_url;
                            }
                        }
                        
                        echo json_encode([
                            'success' => true,
                            'image_url' => $image_url
                        ]);
                    } else {
                        // Return null instead of a placeholder so we can display icon
                        echo json_encode([
                            'success' => true,
                            'image_url' => null
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => true,
                        'image_url' => null
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            exit;
        }
        
        // Get product details
        $stmt = $conn->prepare("SELECT p.*, i.quantity, i.min_stock_level, i.max_stock_level, 
                                i.location, i.last_restock_date, c.name as category_name 
                                FROM products p 
                                LEFT JOIN inventory i ON p.product_id = i.product_id 
                                LEFT JOIN categories c ON p.category_id = c.category_id
                                WHERE p.product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $response = [
                'success' => true,
                'data' => $product
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Product not found'
            ];
        }
        
        echo json_encode($response);
        exit;
    }
}

// Handle POST requests to create or update products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['name', 'cost_price', 'selling_price'];
        $validation_errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $validation_errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate numeric fields
        $numeric_fields = ['cost_price', 'selling_price', 'tax_rate', 'min_stock_level', 'max_stock_level'];
        foreach ($numeric_fields as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '' && !is_numeric($_POST[$field])) {
                $validation_errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
            }
        }
        
        // Return validation errors if any
        if (!empty($validation_errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Validation failed',
                'validation_errors' => $validation_errors
            ]);
            exit;
        }
        
        // Start transaction to ensure consistency between products and inventory tables
        $conn->begin_transaction();
        
        // Get form data with sanitization
        $name = trim($_POST['name']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $sku = !empty($_POST['sku']) ? trim($_POST['sku']) : null;
        $barcode = !empty($_POST['barcode']) ? trim($_POST['barcode']) : null;
        $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
        $cost_price = floatval($_POST['cost_price']);
        $selling_price = floatval($_POST['selling_price']);
        $tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
        
        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            // Create img directory if it doesn't exist
            $upload_dir = '../img/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
            }
            
            // Validate file size (max 2MB)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                throw new Exception('File size exceeds the limit (2MB).');
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Move uploaded file to destination
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                throw new Exception('Failed to upload image.');
            }
            
            // Store relative path in database
            $image_url = 'img/' . $file_name;
        }
        
        // Determine if we're updating an existing product
        $product_id = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        
        if ($product_id) {
            // If updating, check if we need to update the image
            if ($image_url === null && isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                // Get current image to delete
                $get_image_stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                $get_image_stmt->bind_param("i", $product_id);
                $get_image_stmt->execute();
                $result = $get_image_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                        unlink('../' . $product['image_url']);
                    }
                }
                
                $image_url = null; // Set to null to clear in database
            } else if ($image_url === null) {
                // Keep existing image if no new upload and not removing
                $get_image_stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
                $get_image_stmt->bind_param("i", $product_id);
                $get_image_stmt->execute();
                $result = $get_image_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    $image_url = $product['image_url']; // Keep current image
                }
            }
            
            // Update existing product
            $stmt = $conn->prepare("UPDATE products SET 
                name = ?, 
                category_id = ?, 
                description = ?, 
                sku = ?, 
                barcode = ?, 
                cost_price = ?, 
                selling_price = ?, 
                tax_rate = ?,
                image_url = ?, 
                updated_at = CURRENT_TIMESTAMP
                WHERE product_id = ?");
                
            $stmt->bind_param("sisssddsi", 
                $name, 
                $category_id, 
                $description, 
                $sku, 
                $barcode, 
                $cost_price, 
                $selling_price, 
                $tax_rate, 
                $image_url, 
                $product_id
            );
            
            if ($stmt->execute()) {
                // Update inventory settings if they exist
                if (isset($_POST['min_stock_level']) || isset($_POST['max_stock_level']) || isset($_POST['location'])) {
                    $min_stock = isset($_POST['min_stock_level']) ? intval($_POST['min_stock_level']) : 10;
                    $max_stock = isset($_POST['max_stock_level']) ? intval($_POST['max_stock_level']) : 100;
                    $location = isset($_POST['location']) ? trim($_POST['location']) : null;
                    
                    $inventory_stmt = $conn->prepare("UPDATE inventory SET 
                        min_stock_level = ?, 
                        max_stock_level = ?, 
                        location = ?,
                        updated_at = CURRENT_TIMESTAMP
                        WHERE product_id = ?");
                        
                    $inventory_stmt->bind_param("iisi", $min_stock, $max_stock, $location, $product_id);
                    $inventory_stmt->execute();
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Product updated successfully',
                    'product_id' => $product_id
                ];
            } else {
                throw new Exception("Error updating product: " . $stmt->error);
            }
        } else {
            // Create new product
            $stmt = $conn->prepare("INSERT INTO products 
                (name, category_id, description, sku, barcode, cost_price, selling_price, tax_rate, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
            $stmt->bind_param("sisssddds", 
                $name, 
                $category_id, 
                $description, 
                $sku, 
                $barcode, 
                $cost_price, 
                $selling_price, 
                $tax_rate, 
                $image_url
            );
            
            if ($stmt->execute()) {
                $product_id = $conn->insert_id;
                
                // Create inventory record
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
                $min_stock = isset($_POST['min_stock_level']) ? intval($_POST['min_stock_level']) : 10;
                $max_stock = isset($_POST['max_stock_level']) ? intval($_POST['max_stock_level']) : 100;
                $location = isset($_POST['location']) ? trim($_POST['location']) : null;
                
                $inventory_stmt = $conn->prepare("INSERT INTO inventory 
                    (product_id, quantity, min_stock_level, max_stock_level, location) 
                    VALUES (?, ?, ?, ?, ?)");
                    
                $inventory_stmt->bind_param("iiiis", 
                    $product_id, 
                    $quantity, 
                    $min_stock, 
                    $max_stock, 
                    $location
                );
                
                if ($inventory_stmt->execute()) {
                    // If initial quantity > 0, record it as an inventory transaction
                    if ($quantity > 0) {
                        $user_id = $_SESSION['user_id'] ?? null;
                        
                        $transaction_stmt = $conn->prepare("INSERT INTO inventory_transactions 
                            (product_id, user_id, transaction_type, quantity_change, before_quantity, after_quantity, notes) 
                            VALUES (?, ?, 'purchase', ?, 0, ?, 'Initial stock')");
                            
                        $transaction_stmt->bind_param("iiii", 
                            $product_id, 
                            $user_id, 
                            $quantity, 
                            $quantity
                        );
                        
                        $transaction_stmt->execute();
                    }
                    
                    $response = [
                        'success' => true,
                        'message' => 'Product added successfully',
                        'product_id' => $product_id
                    ];
                } else {
                    throw new Exception("Error creating inventory record: " . $inventory_stmt->error);
                }
            } else {
                throw new Exception("Error creating product: " . $stmt->error);
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $response = [
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
            'db_error' => $conn->error
        ];
        
        http_response_code(500);
        echo json_encode($response);
    }
    
    exit;
}

// Handle other request methods
$response = [
    'success' => false,
    'message' => 'Invalid request method'
];

http_response_code(405);
echo json_encode($response);
?>
