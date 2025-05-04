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

$response = ['success' => false];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check if a specific sale ID is requested
    if (isset($_GET['id'])) {
        $sale_id = intval($_GET['id']);
        
        // Get sale details
        $sale_query = "SELECT s.*, c.name as customer_name, u.full_name as user_name 
                      FROM sales s 
                      LEFT JOIN customers c ON s.customer_id = c.customer_id 
                      LEFT JOIN users u ON s.user_id = u.user_id
                      WHERE s.sale_id = ?";
        
        $stmt = $conn->prepare($sale_query);
        $stmt->bind_param('i', $sale_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $sale = $result->fetch_assoc();
            $response['success'] = true;
            $response['sale'] = $sale;
            
            // If detailed view or receipt requested, get sale items as well
            if (isset($_GET['details']) || isset($_GET['receipt'])) {
                $items_query = "SELECT si.*, p.name 
                              FROM sale_items si
                              LEFT JOIN products p ON si.product_id = p.product_id
                              WHERE si.sale_id = ?";
                
                $stmt = $conn->prepare($items_query);
                $stmt->bind_param('i', $sale_id);
                $stmt->execute();
                $items_result = $stmt->get_result();
                
                $items = [];
                while ($item = $items_result->fetch_assoc()) {
                    $items[] = $item;
                }
                
                $response['items'] = $items;
                
                // If receipt requested, get store settings
                if (isset($_GET['receipt'])) {
                    $settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN 
                                     ('store_name', 'store_address', 'store_phone', 'tax_rate', 'currency', 'receipt_footer')";
                    $settings_result = $conn->query($settings_query);
                    
                    $store_settings = [];
                    while ($setting = $settings_result->fetch_assoc()) {
                        $store_settings[$setting['setting_key']] = $setting['setting_value'];
                    }
                    
                    $response['store'] = [
                        'store_name' => $store_settings['store_name'] ?? 'My POS Store',
                        'store_address' => $store_settings['store_address'] ?? '',
                        'store_phone' => $store_settings['store_phone'] ?? '',
                        'tax_rate' => $store_settings['tax_rate'] ?? '0',
                        'currency' => $store_settings['currency'] ?? 'USD',
                        'receipt_footer' => $store_settings['receipt_footer'] ?? 'Thank you for your business!'
                    ];
                }
            }
        } else {
            $response['message'] = 'Sale not found';
        }
    } else {
        // Filtering for sales list
        $where_clauses = [];
        $params = [];
        $types = '';
        
        // Date range filter
        if (!empty($_GET['date_from'])) {
            $where_clauses[] = "sale_date >= ?";
            $params[] = $_GET['date_from'] . ' 00:00:00';
            $types .= 's';
        }
        
        if (!empty($_GET['date_to'])) {
            $where_clauses[] = "sale_date <= ?";
            $params[] = $_GET['date_to'] . ' 23:59:59';
            $types .= 's';
        }
        
        // Status filter
        if (!empty($_GET['status'])) {
            $where_clauses[] = "payment_status = ?";
            $params[] = $_GET['status'];
            $types .= 's';
        }
        
        // Payment method filter
        if (!empty($_GET['payment_method'])) {
            $where_clauses[] = "payment_method = ?";
            $params[] = $_GET['payment_method'];
            $types .= 's';
        }
        
        // Search term (sale ID, customer name, or reference)
        if (!empty($_GET['search'])) {
            $search_term = '%' . $_GET['search'] . '%';
            $where_clauses[] = "(s.sale_id LIKE ? OR c.name LIKE ?)";
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= 'ss';
        }
        
        // Build WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = "WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Get total count first
        $count_sql = "SELECT COUNT(*) as total 
                     FROM sales s 
                     LEFT JOIN customers c ON s.customer_id = c.customer_id 
                     $where_sql";
        
        $stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $count_result = $stmt->get_result()->fetch_assoc();
        $total_count = $count_result['total'];
        
        // Then get the actual sales data
        $sales_sql = "SELECT s.*, c.name as customer_name, 
                      (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.sale_id) as item_count
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.customer_id
                      $where_sql
                      ORDER BY s.sale_date DESC
                      LIMIT ?, ?";
        
        $stmt = $conn->prepare($sales_sql);
        if (!empty($params)) {
            $params[] = $offset;
            $params[] = $per_page;
            $types .= 'ii';
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $offset, $per_page);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sales = [];
        while ($sale = $result->fetch_assoc()) {
            $sales[] = $sale;
        }
        
        $response['success'] = true;
        $response['sales'] = $sales;
        $response['total_count'] = $total_count;
        $response['page'] = $page;
        $response['per_page'] = $per_page;
        $response['total_pages'] = ceil($total_count / $per_page);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
