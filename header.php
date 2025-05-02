<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_authenticated']) || $_SESSION['is_authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenzaki Systems - POS System</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/notification.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <?php if ($current_page === 'dashboard.php'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>KS-POS System</h2>
            </div>
            <ul class="nav-links">
                <li class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </li>
                <li class="<?php echo ($current_page === 'pos.php') ? 'active' : ''; ?>">
                    <a href="pos.php"><i class="fas fa-cash-register"></i> Point of Sale</a>
                </li>
                <li class="<?php echo ($current_page === 'sales.php') ? 'active' : ''; ?>">
                    <a href="sales.php"><i class="fas fa-shopping-cart"></i> Sales</a>
                </li>
                <li class="<?php echo ($current_page === 'inventory.php') ? 'active' : ''; ?>">
                    <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
                </li>
                <li class="<?php echo ($current_page === 'customers.php') ? 'active' : ''; ?>">
                    <a href="customers.php"><i class="fas fa-users"></i> Customers</a>
                </li>
                <li class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>">
                    <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                </li>
                <li class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                </li>
            </ul>
            <div class="logout">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <main class="main-content">
            <header class="top-bar" style="display: flex; align-items: center; min-height: 70px;">
                <div class="search-bar">
                    <input type="text" placeholder="Search...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="user-info" style="display: flex; align-items: center;">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge"><?php echo isset($pendingOrders) ? $pendingOrders : '0'; ?></span>
                    </div>
                    <div class="profile">
                        <img src="default-avatar.jpg" alt="User">
                        <span><?php echo $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'; ?></span>
                    </div>
                </div>
            </header>
