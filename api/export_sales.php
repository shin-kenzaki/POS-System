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

// Build the WHERE clause based on filters
$where_clauses = [];
$params = [];
$types = '';

// Date range filter
if (!empty($_GET['date_from'])) {
    $where_clauses[] = "s.sale_date >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
    $types .= 's';
}

if (!empty($_GET['date_to'])) {
    $where_clauses[] = "s.sale_date <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
    $types .= 's';
}

// Status filter
if (!empty($_GET['status'])) {
    $where_clauses[] = "s.payment_status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Payment method filter
if (!empty($_GET['payment_method'])) {
    $where_clauses[] = "s.payment_method = ?";
    $params[] = $_GET['payment_method'];
    $types .= 's';
}

// Search term
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

// SQL query to get all sales matching the filters
$sql = "SELECT 
            s.sale_id,
            s.sale_date,
            c.name AS customer_name,
            s.payment_method,
            s.payment_status,
            s.subtotal,
            s.tax_amount,
            s.discount_amount,
            s.total_amount,
            u.full_name AS cashier_name,
            (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.sale_id) AS item_count
        FROM 
            sales s
        LEFT JOIN 
            customers c ON s.customer_id = c.customer_id
        LEFT JOIN 
            users u ON s.user_id = u.user_id
        $where_sql
        ORDER BY 
            s.sale_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate CSV filename with date
$filename = 'sales_export_' . date('Y-m-d_His') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 encoding
fprintf($output, "\xEF\xBB\xBF");

// Add CSV header row
fputcsv($output, [
    'Sale ID',
    'Date',
    'Customer',
    'Items',
    'Payment Method',
    'Status',
    'Subtotal',
    'Tax',
    'Discount',
    'Total',
    'Cashier'
]);

// Add sales data
while ($sale = $result->fetch_assoc()) {
    fputcsv($output, [
        '#SALE-' . str_pad($sale['sale_id'], 6, '0', STR_PAD_LEFT),
        date('Y-m-d H:i:s', strtotime($sale['sale_date'])),
        $sale['customer_name'] ?? 'Walk-in Customer',
        $sale['item_count'],
        ucfirst(str_replace('_', ' ', $sale['payment_method'])),
        ucfirst($sale['payment_status']),
        number_format($sale['subtotal'], 2),
        number_format($sale['tax_amount'], 2),
        number_format($sale['discount_amount'], 2),
        number_format($sale['total_amount'], 2),
        $sale['cashier_name'] ?? 'System'
    ]);
}

// Close the output stream
fclose($output);
exit;
