<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Only allow admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch all orders with user names
$orders = [];
$stmt = $conn->prepare("
    SELECT orders.*, users.name 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    ORDER BY orders.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="navbar">
        <a href="dashboard.php" class="logo">Admin Panel</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="orders.php">Orders</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>

    <div class="container" style="padding-top:2rem; padding-bottom:2rem;">
        <h2 class="section-title" style="margin-top:0;">All Orders</h2>

        <?php if (count($orders) === 0): ?>
            <p>No orders yet.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="product-card" style="padding:1rem; margin-bottom:1rem;">
                    <div class="product-info">
                        <h3>Order #<?= (int)$order['id'] ?> - User: <?= htmlspecialchars($order['name']) ?></h3>
                        <p><b>Status:</b> <?= htmlspecialchars($order['status']) ?></p>
                        <p><b>Total:</b> Rs. <?= number_format((float)$order['total_amount'], 2) ?></p>
                        <p><b>Date:</b> <?= htmlspecialchars($order['created_at']) ?></p>
                        <p><b>Delivery:</b> <?= htmlspecialchars($order['address']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>