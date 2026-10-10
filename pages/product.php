<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/shop_agents.php";
require_once __DIR__ . "/../includes/shop_features.php";

shophub_ensure_feature_tables($conn);

// 1. Get Product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: ../index.php");
    exit;
}

// 2. Fetch Product
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? 
    LIMIT 1
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// 3. If not found
if (!$product) {
    header("Location: ../index.php");
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
shophub_log_product_view($conn, $productId, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'wishlist') {
    if ($isAdmin) {
        $_SESSION['product_message'] = "Admin accounts manage products and cannot use customer wishlist.";
        header("Location: product.php?id=" . $productId);
        exit();
    }

    if ($userId <= 0) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }

    $isSaved = shophub_toggle_wishlist($conn, $userId, $productId);
    $_SESSION['product_message'] = $isSaved ? "Added to wishlist." : "Removed from wishlist.";
    header("Location: product.php?id=" . $productId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'review') {
    if ($isAdmin) {
        $_SESSION['product_message'] = "Admin accounts cannot post customer reviews.";
        header("Location: product.php?id=" . $productId . "#reviews");
        exit();
    }

    if ($userId <= 0) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }

    shophub_save_review(
        $conn,
        $productId,
        $userId,
        (int)($_POST['rating'] ?? 5),
        trim($_POST['comment'] ?? '')
    );
    $_SESSION['product_message'] = "Review saved. Thank you for your feedback.";
    header("Location: product.php?id=" . $productId . "#reviews");
    exit();
}

// 4. Handle Buy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'buy') {
    if ($isAdmin) {
        $_SESSION['product_message'] = "Admin accounts cannot buy products. Please use a customer account for shopping.";
        header("Location: product.php?id=" . $productId);
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }

    // Get form data
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $delivery = $_POST['delivery'] ?? 'standard';
    $payment = $_POST['payment'] ?? 'cod';

    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Handle size for clothing categories
    $size = NULL;
    if (stripos($product['category_name'], 'cloth') !== false) {
        $size = $_POST['size'] ?? NULL;
        if (!$size) {
            $error = "Please select size.";
        }
    }

    if (!$customer_name || !$phone || !$address) {
        $error = "Please fill all required fields.";
    }

    if (empty($error)) {

        // Price calculation
        $totalPrice = $product['price'] * $quantity;
        if ($delivery === 'express') $totalPrice += 50;

        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (user_id, customer_name, phone, address, total_amount, status, quantity, size, payment_method, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $status = "pending";

        $stmt->bind_param(
            "isssdsiss",
            $_SESSION['user_id'],
            $customer_name,
            $phone,
            $address,
            $totalPrice,
            $status,
            $quantity,
            $size,
            $payment
        );

        if ($stmt->execute()) {
            $orderId = $conn->insert_id;

            if (shophub_table_exists($conn, "order_items")) {
                $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $unitPrice = (float)$product['price'];
                $itemStmt->bind_param("iiid", $orderId, $productId, $quantity, $unitPrice);
                $itemStmt->execute();
            }

            $_SESSION['success'] = "Order placed successfully!";
            header("Location: my_orders.php");
            exit();
        } else {
            $error = "Order failed: " . $stmt->error;
        }
    }
}

// 5. Image Handling
$imageName = $product['image'] ?? 'default.jpg';
$imageName = basename($imageName);
$imagePath = "../uploads/" . $imageName;

if (!file_exists($imagePath) || empty($imageName)) {
    $imagePath = "../uploads/default.jpg";
}

$isWishlisted = shophub_is_wishlisted($conn, $userId, $productId);
$reviewSummary = shophub_get_review_summary($conn, $productId);
$reviews = shophub_get_reviews($conn, $productId);
$productMessage = $_SESSION['product_message'] ?? "";
unset($_SESSION['product_message']);

$relatedProducts = shophub_get_recommendations($conn, [
    'limit' => 4,
    'exclude_id' => $productId,
    'category_id' => (int)$product['category_id'],
    'price' => (float)$product['price'],
    'user_id' => $userId,
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ShopHub - <?= htmlspecialchars($product['name']) ?></title>
<link rel="stylesheet" href="../assets/style.css">
</head>

<script>
function openModal() {
    document.getElementById("orderModal").style.display = "block";
}

function closeModal() {
    document.getElementById("orderModal").style.display = "none";
}
</script>

<body>

<header class="navbar">
    <a href="../index.php" class="logo">ShopHub</a>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if ($isAdmin): ?>
                <a href="../admin/dashboard.php">Admin Dashboard</a>
            <?php else: ?>
                <a href="my_orders.php">My Orders</a>
                <a href="wishlist.php">Wishlist</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>

<section class="product-detail">
<div class="container">

<!-- IMAGE -->
<div class="product-image">
    <img src="<?= htmlspecialchars($imagePath) ?>"
         alt="<?= htmlspecialchars($product['name']) ?>"
         style="width:300px; border-radius:8px;">
</div>

<!-- INFO -->
<div class="product-info">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p>Category: <?= htmlspecialchars($product['category_name']) ?></p>
    <p><?= htmlspecialchars($product['description']) ?></p>
    <p class="product-rating">
        <?= $reviewSummary['total'] > 0 ? str_repeat("*", (int)round($reviewSummary['average'])) : "No ratings yet" ?>
        <?php if ($reviewSummary['total'] > 0): ?>
            <?= $reviewSummary['average'] ?>/5 from <?= $reviewSummary['total'] ?> review<?= $reviewSummary['total'] === 1 ? "" : "s" ?>
        <?php endif; ?>
    </p>
    <h2>Rs. <?= number_format($product['price'], 2) ?></h2>

    <?php if ($productMessage !== ""): ?>
        <p class="success-message"><?= htmlspecialchars($productMessage) ?></p>
    <?php endif; ?>

    <!-- ERROR -->
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- BUTTON -->
    <?php if ($isAdmin): ?>
        <div class="admin-product-notice">
            Admin view only. Use the admin panel to manage this product.
            <a href="../admin/manage_product.php">Manage Products</a>
        </div>
    <?php else: ?>
        <div class="product-actions">
            <button onclick="openModal()">Buy Now</button>
            <form method="POST" action="product.php?id=<?= $productId ?>&action=wishlist">
                <button type="submit" class="btn-secondary"><?= $isWishlisted ? "Remove Wishlist" : "Add Wishlist" ?></button>
            </form>
        </div>
    <?php endif; ?>

<!-- MODAL -->
<div id="orderModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">

    <div style="background:#fff; width:400px; margin:100px auto; padding:20px; border-radius:8px; position:relative;">
        
        <h2>Order Details</h2>

        <form action="product.php?id=<?= $productId ?>&action=buy" method="POST">

            <input type="text" name="customer_name" placeholder="Your Name" required><br><br>
            <input type="text" name="phone" placeholder="Phone Number" required><br><br>
            <textarea name="address" placeholder="Address" required></textarea><br><br>

            <!-- SIZE ONLY FOR CLOTHING -->
            <?php if (stripos($product['category_name'], 'cloth') !== false): ?>
                <label>Size:</label>
                <select name="size" required>
                    <option value="">Select Size</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                </select><br><br>
            <?php endif; ?>

            <label>Quantity:</label>
            <input type="number" name="quantity" value="1" min="1"><br><br>

            <label>Delivery:</label>
            <select name="delivery">
                <option value="standard">Standard</option>
                <option value="express">Express (+Rs.50)</option>
            </select><br><br>

            <label>Payment:</label>
            <select name="payment">
                <option value="cod">Cash on Delivery</option>
                <option value="online">Online Payment</option>
            </select><br><br>

            <button type="submit">Confirm Order</button>
            <button type="button" onclick="closeModal()">Cancel</button>

        </form>

    </div>
</div>

</div>
</section>

<section class="container product-feedback" id="reviews">
    <div class="feedback-grid">
        <div class="review-panel">
            <h2>Customer Reviews</h2>
            <?php if (count($reviews) === 0): ?>
                <p>No reviews yet. Be the first to review this product.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <strong><?= htmlspecialchars($review['user_name']) ?></strong>
                        <span><?= str_repeat("*", (int)$review['rating']) ?> <?= (int)$review['rating'] ?>/5</span>
                        <?php if (trim((string)$review['comment']) !== ""): ?>
                            <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="review-panel">
            <h2>Write a Review</h2>
            <?php if ($isAdmin): ?>
                <p>Admin accounts cannot write customer reviews.</p>
            <?php elseif ($userId > 0): ?>
                <form method="POST" action="product.php?id=<?= $productId ?>&action=review" class="review-form">
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Average</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Bad</option>
                    </select>

                    <label for="comment">Comment</label>
                    <textarea id="comment" name="comment" placeholder="Share your experience"></textarea>

                    <button class="btn-primary" type="submit">Save Review</button>
                </form>
            <?php else: ?>
                <p><a href="login.php">Login</a> to write a review or add this product to your wishlist.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (count($relatedProducts) > 0): ?>
    <section class="container recommendation-section">
        <div class="section-heading-row">
            <div>
                <h2 class="section-title compact-title">You May Also Like</h2>
                <p class="section-subtitle">Related picks from the same style and price range.</p>
            </div>
        </div>
        <div class="product-grid recommendation-grid">
            <?php shophub_render_product_cards($relatedProducts, "../"); ?>
        </div>
    </section>
<?php endif; ?>

<?php include __DIR__ . "/../includes/chat_widget.php"; ?>
<script src="../assets/script.js"></script>
</body>
</html>
