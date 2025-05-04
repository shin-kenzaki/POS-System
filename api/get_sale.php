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

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$sale_id = intval($_GET['sale_id']);

// Get sale details
$sale_query = "SELECT s.*, c.name AS customer_name, u.full_name AS cashier_name
               FROM sales s
               LEFT JOIN customers c ON s.customer_id = c.customer_id
               LEFT JOIN users u ON s.user_id = u.user_id
               WHERE s.sale_id = ?";
               
$stmt = $conn->prepare($sale_query);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();

if ($sale_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_query = "SELECT si.*, p.name
                FROM sale_items si
                JOIN products p ON si.product_id = p.product_id
                WHERE si.sale_id = ?";
                
$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Add items to sale data
$sale['items'] = $items;

// Return sale data as JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'sale' => $sale]);
