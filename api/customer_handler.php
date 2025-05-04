<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Handle GET requests for customer data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if a specific customer ID is requested
        if (isset($_GET['customer_id'])) {
            $customer_id = intval($_GET['customer_id']);
            
            // Get customer details
            $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
                ]);
                exit;
            }
            
            $customer = $result->fetch_assoc();
            
            // Get recent purchases (last 5)
            $purchases = [];
            $purchase_stmt = $conn->prepare("
                SELECT s.*, COUNT(si.sale_item_id) as item_count 
                FROM sales s
                LEFT JOIN sale_items si ON s.sale_id = si.sale_id
                WHERE s.customer_id = ?
                GROUP BY s.sale_id
                ORDER BY s.sale_date DESC
                LIMIT 5
            ");
            $purchase_stmt->bind_param("i", $customer_id);
            $purchase_stmt->execute();
            $purchase_result = $purchase_stmt->get_result();
            
            while ($row = $purchase_result->fetch_assoc()) {
                $purchases[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'customer' => $customer,
                'purchases' => $purchases
            ]);
            
        } else {
            // Get all customers
            $stmt = $conn->prepare("SELECT * FROM customers ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $customers = [];
            while ($row = $result->fetch_assoc()) {
                $customers[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'customers' => $customers
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle POST requests to create customers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['name'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer name is required'
            ]);
            exit;
        }
        
        // Get form data with sanitization
        $name = trim($_POST['name']);
        $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
        $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
        $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
        
        // Check if email already exists
        if (!empty($email)) {
            $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'A customer with this email already exists'
                ]);
                exit;
            }
        }
        
        // Create new customer
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $address);
        
        if ($stmt->execute()) {
            $customer_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Customer added successfully',
                'customer' => [
                    'id' => $customer_id,
                    'name' => $name,
                    'phone' => $phone
                ]
            ]);
        } else {
            throw new Exception("Error creating customer: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle PUT requests to update customers
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['customer_id']))) {
    try {
        // Parse data from PUT request or from POST with customer_id parameter
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str(file_get_contents("php://input"), $put_data);
            $data = $put_data;
            $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
        } else {
            $data = $_POST;
            $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
        }
        
        // Validate required data
        if (!$customer_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer ID is required'
            ]);
            exit;
        }
        
        if (empty($data['name'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer name is required'
            ]);
            exit;
        }
        
        // Get form data with sanitization
        $name = trim($data['name']);
        $email = !empty($data['email']) ? trim($data['email']) : null;
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $address = !empty($data['address']) ? trim($data['address']) : null;
        $loyalty_points = isset($data['loyalty_points']) ? intval($data['loyalty_points']) : null;
        
        // Check if email already exists for another customer
        if (!empty($email)) {
            $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ? LIMIT 1");
            $check_stmt->bind_param("si", $email, $customer_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'A different customer with this email already exists'
                ]);
                exit;
            }
        }
        
        // Update customer - if loyalty_points is provided, update it too
        if ($loyalty_points !== null) {
            $stmt = $conn->prepare("
                UPDATE customers 
                SET name = ?, email = ?, phone = ?, address = ?, loyalty_points = ?
                WHERE customer_id = ?
            ");
            $stmt->bind_param("ssssis", $name, $email, $phone, $address, $loyalty_points, $customer_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE customers 
                SET name = ?, email = ?, phone = ?, address = ?
                WHERE customer_id = ?
            ");
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
        }
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer updated successfully'
                ]);
            } else {
                // No rows were updated, customer might not exist
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found or no changes made'
                ]);
            }
        } else {
            throw new Exception("Error updating customer: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle DELETE requests to remove customers
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
        
        if (!$customer_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Customer ID is required'
            ]);
            exit;
        }
        
        // Check if customer has any associated sales
        $check_stmt = $conn->prepare("SELECT sale_id FROM sales WHERE customer_id = ? LIMIT 1");
        $check_stmt->bind_param("i", $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete customer with associated sales. Consider deactivating instead.'
            ]);
            exit;
        }
        
        // Delete customer
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Customer deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Customer not found'
                ]);
            }
        } else {
            throw new Exception("Error deleting customer: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// If we get here, it was an invalid request method
echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
]);
?>
