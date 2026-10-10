<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/shop_agents.php";
require_once __DIR__ . "/../includes/shop_features.php";

shophub_ensure_feature_tables($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    header("Location: wishlist.php");
    exit;
}

$items = shophub_fetch_products($conn, "
    SELECT p.*, c.name AS category_name, w.created_at AS saved_at
    FROM wishlist w
    JOIN products p ON p.id = w.product_id
    JOIN categories c ON c.id = p.category_id
    WHERE w.user_id = ?
    ORDER BY w.id DESC
", "i", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - ShopHub</title>
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

    <main class="container" style="padding-top:2rem;">
        <h2 class="section-title" style="margin-top:0;">My Wishlist</h2>

        <?php if (count($items) === 0): ?>
            <p>Your wishlist is empty.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($items as $product): ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p><?= htmlspecialchars($product['category_name']) ?></p>
                            <p class="price">Rs. <?= number_format((float)$product['price'], 2) ?></p>
                            <a class="btn-primary" href="product.php?id=<?= (int)$product['id'] ?>">View Details</a>
                            <a class="btn-link-danger" href="wishlist.php?remove=<?= (int)$product['id'] ?>" onclick="return confirm('Remove this item from wishlist?')">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
