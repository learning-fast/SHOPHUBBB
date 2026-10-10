<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
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
    <title>My Orders - ShopHub</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="navbar">
        <a href="../index.php" class="logo">ShopHub</a>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="my_orders.php">My Orders</a>
            <a href="wishlist.php">Wishlist</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container" style="padding-top:2rem; padding-bottom:2rem;">
        <h2 class="section-title" style="margin-top:0;">My Orders</h2>

        <?php if (count($orders) === 0): ?>
            <p>No orders yet.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="product-card" style="padding:1rem; margin-bottom:1rem;">
                    <div class="product-info">
                        <h3>Order #<?= (int)$order['id'] ?></h3>
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
