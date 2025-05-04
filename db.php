<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pos_inventory";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if refunds table exists, if not create it
$result = $conn->query("SHOW TABLES LIKE 'refunds'");
if ($result->num_rows == 0) {
    // Create refunds table
    $sql = "CREATE TABLE `refunds` (
        `refund_id` int(11) NOT NULL AUTO_INCREMENT,
        `sale_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `refund_amount` decimal(10,2) NOT NULL,
        `refund_date` datetime NOT NULL DEFAULT current_timestamp(),
        `reason` varchar(255) NOT NULL,
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`refund_id`),
        KEY `sale_id` (`sale_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
        CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating refunds table: " . $conn->error);
    }
    
    // Create refund_items table
    $sql = "CREATE TABLE `refund_items` (
        `refund_item_id` int(11) NOT NULL AUTO_INCREMENT,
        `refund_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`refund_item_id`),
        KEY `refund_id` (`refund_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `refund_items_ibfk_1` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`refund_id`) ON DELETE CASCADE,
        CONSTRAINT `refund_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating refund_items table: " . $conn->error);
    }
}
?>
