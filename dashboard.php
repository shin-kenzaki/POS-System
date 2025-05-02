<?php
// Include database connection
require_once 'db.php';

// Placeholder data - in a real system, this would come from database queries
$dailySales = 3542.75;
$monthlySales = 78654.20;
$lowStockItems = 12;
$pendingOrders = 8;
$topSellingProducts = [
    ['name' => 'Product A', 'sales' => 127],
    ['name' => 'Product B', 'sales' => 96],
    ['name' => 'Product C', 'sales' => 84],
    ['name' => 'Product D', 'sales' => 71],
    ['name' => 'Product E', 'sales' => 65],
];

// Include the header (which also handles authentication)
include 'header.php';
?>

<div class="dashboard-content">
    <h1>Dashboard Overview</h1>
    
    <div class="stats-cards">
        <div class="card">
            <div class="card-inner">
                <h3>Today's Sales</h3>
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h2>$<?php echo number_format($dailySales, 2); ?></h2>
            <p>↑ 12% from yesterday</p>
        </div>
        
        <div class="card">
            <div class="card-inner">
                <h3>Monthly Sales</h3>
                <i class="fas fa-chart-line"></i>
            </div>
            <h2>$<?php echo number_format($monthlySales, 2); ?></h2>
            <p>↑ 8% from last month</p>
        </div>
        
        <div class="card">
            <div class="card-inner">
                <h3>Low Stock Items</h3>
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2><?php echo $lowStockItems; ?></h2>
            <p>Items need restocking</p>
        </div>
        
        <div class="card">
            <div class="card-inner">
                <h3>Pending Orders</h3>
                <i class="fas fa-clock"></i>
            </div>
            <h2><?php echo $pendingOrders; ?></h2>
            <p>Orders need processing</p>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart">
            <h3>Sales Overview</h3>
            <canvas id="salesChart"></canvas>
        </div>
        
        <div class="chart">
            <h3>Top Selling Products</h3>
            <canvas id="productsChart"></canvas>
        </div>
    </div>
    
    <div class="recent-activity">
        <h3>Recent Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#ORD-001</td>
                    <td>John Smith</td>
                    <td>2023-07-05 14:30</td>
                    <td>$245.99</td>
                    <td><span class="status completed">Completed</span></td>
                </tr>
                <tr>
                    <td>#ORD-002</td>
                    <td>Sarah Johnson</td>
                    <td>2023-07-05 12:25</td>
                    <td>$189.50</td>
                    <td><span class="status pending">Pending</span></td>
                </tr>
                <tr>
                    <td>#ORD-003</td>
                    <td>Michael Brown</td>
                    <td>2023-07-04 16:45</td>
                    <td>$452.75</td>
                    <td><span class="status completed">Completed</span></td>
                </tr>
                <tr>
                    <td>#ORD-004</td>
                    <td>Emily Davis</td>
                    <td>2023-07-04 10:15</td>
                    <td>$78.25</td>
                    <td><span class="status pending">Pending</span></td>
                </tr>
                <tr>
                    <td>#ORD-005</td>
                    <td>James Wilson</td>
                    <td>2023-07-01 09:10</td>
                    <td>$175.00</td>
                    <td><span class="status refunded">Refunded</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include the footer
include 'footer.php';
?>
