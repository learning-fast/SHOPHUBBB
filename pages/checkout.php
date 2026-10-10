<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id'])) {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    header("Location: login.php?redirect=product.php?id=" . $productId);
    exit;
}
if ($_SESSION['role'] === 'admin') {
    $_SESSION['admin_notice'] = "Admin accounts cannot checkout products. Please use a customer account for shopping.";
    header("Location: ../admin/dashboard.php");
    exit;
}
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$quantity = (int)($_GET['quantity'] ?? $_POST['quantity'] ?? 1);
$quantity = max(1, $quantity);

if ($productId <= 0) {
    header("Location: ../index.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$productResult = $stmt->get_result();
$product = $productResult ? $productResult->fetch_assoc() : null;

if (!$product) {
    header("Location: ../index.php");
    exit;
}

$error = "";
$success = "";
$orderId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($customerName === '' || $phone === '' || $address === '') {
        $error = 'All fields are required.';
    } else {
        $total = (float)$product['price'] * $quantity;

        $conn->begin_transaction();
        try {
            $orderStmt = $conn->prepare("INSERT INTO orders (user_id, customer_name, phone, address, total_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $orderStmt->bind_param("isssd", $_SESSION['user_id'], $customerName, $phone, $address, $total);
            $orderStmt->execute();
            $orderId = $conn->insert_id;

            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $price = (float)$product['price'];
            $itemStmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
            $itemStmt->execute();

            $conn->commit();
            $success = "Order placed successfully. Order ID: #" . $orderId;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Could not place order. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ShopHub</title>
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
        <div class="form-container" style="max-width:600px; margin:0 auto;">
            <h2>Checkout</h2>

            <?php if ($error !== ''): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <p style="color:green;"><?= htmlspecialchars($success) ?></p>
                <p><a href="my_orders.php">Go to My Orders</a></p>
            <?php endif; ?>

            <p><b>Product:</b> <?= htmlspecialchars($product['name']) ?></p>
            <p><b>Quantity:</b> <?= (int)$quantity ?></p>
            <p><b>Total:</b> Rs. <?= number_format((float)$product['price'] * $quantity, 2) ?></p>

            <?php if ($success === ''): ?>
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
                    <input type="hidden" name="quantity" value="<?= (int)$quantity ?>">

                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name" required>

                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" required>

                    <label for="address">Address</label>
                    <textarea id="address" name="address" required style="width:100%; min-height:90px; margin-bottom:1rem; padding:.75rem; border:1px solid #d1d5db; border-radius:6px;"></textarea>

                    <button class="btn-primary" type="submit">Place Order</button>
                </form>
                <select name="payment_method">
    <option value="cod">Cash on Delivery</option>
    <option value="demo">Online Payment </option>
</select>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
