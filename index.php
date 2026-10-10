<?php
session_start();
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/shop_agents.php";

// Get selected category and search term
$selectedCategory = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch all categories
$categories = [];
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Build products query dynamically
$products = [];
$query = "SELECT p.*, c.name AS category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id";

$conditions = [];
$params = [];
$types = "";

// Category filter
if ($selectedCategory > 0) {
    $conditions[] = "p.category_id = ?";
    $types .= "i";
    $params[] = $selectedCategory;
}

// Search filter
if (!empty($searchTerm)) {
    $conditions[] = "p.name LIKE ?";
    $types .= "s";
    $params[] = "%" . $searchTerm . "%";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$recommendedProducts = shophub_get_recommendations($conn, [
    'limit' => 4,
    'category_id' => $selectedCategory,
    'search' => $searchTerm,
    'user_id' => (int)($_SESSION['user_id'] ?? 0),
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ShopHub</title>
<link rel="stylesheet" href="/SHOPHUBBB/assets/style.css">
</head>

<body>

<header class="navbar">
    <form method="GET" action="index.php" class="search-form">
        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit">Search</button>
        <?php if ($selectedCategory > 0): ?>
            <input type="hidden" name="category_id" value="<?= $selectedCategory ?>">
        <?php endif; ?>
    </form>

    <nav class="nav-links">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="admin/add_category.php">Admin Category</a>
                <a href="admin/add_product.php">Admin Product</a>
                <a href="admin/manage_orders.php">Manage Orders</a>
                <a href="admin/manage_users.php">Manage Users</a>
            <?php endif; ?>
            <a href="pages/wishlist.php">Wishlist</a>
            <a href="pages/logout.php">Logout</a>
        <?php else: ?>
            <a href="pages/login.php">Login</a>
            <a href="pages/register.php">Signup</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ✅ FIXED HERO (no product loop here) -->
<!-- Hero Section -->
<section class="hero">
    <div class="hero-left">
        <h1>Upgrade Your Lifestyle with Premium Products</h1>
        <p>Exclusive deals on Fashion</p>
        <a href="#products" class="btn-primary">Shop Now</a>
    </div>

    <div class="hero-right">
        <!-- Sale badge image, static -->
        <img src="assets/images/20off.png" alt="" 
             style="position: absolute; top: 200px; right: 200px; width: 300px; z-index: 10;">
        <img src="https://via.placeholder.com/600x400?text=Mega+Sale" alt="Sale">
    </div>
</section>

<div class="container">

    <!-- Categories -->
    <h2 class="section-title">Categories</h2>
    <div class="category-grid">
        <a class="category-card <?= $selectedCategory === 0 ? 'active' : '' ?>" href="index.php<?= !empty($searchTerm) ? '?search=' . urlencode($searchTerm) : '' ?>">
            All
        </a>
        <?php foreach ($categories as $cat): ?>
            <a class="category-card <?= $selectedCategory === (int)$cat['id'] ? 'active' : '' ?>"
               href="index.php?category_id=<?= (int)$cat['id'] ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (count($recommendedProducts) > 0): ?>
        <section class="recommendation-section">
            <div class="section-heading-row">
                <div>
                    <h2 class="section-title compact-title">Recommended For You</h2>
                    <p class="section-subtitle">Smart picks based on your browsing, search, and latest products.</p>
                </div>
            </div>
            <div class="product-grid recommendation-grid">
                <?php shophub_render_product_cards($recommendedProducts); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Products -->
    <h2 class="section-title" id="products">Products</h2>
    <div class="product-grid">
        <?php if (count($products) === 0): ?>
            <p>No products found<?= !empty($searchTerm) ? " for \"$searchTerm\"" : "" ?><?= $selectedCategory > 0 ? " in this category" : "" ?>.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image-wrapper">

                        <!-- ✅ FIX: badge only for ONE product -->
                        <?php if ($product['id'] == 1): ?>
                            <img src="assets/images/20off.png" class="discount-badge-img">
                        <?php endif; ?>

                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>

                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= htmlspecialchars($product['category_name']) ?></p>
                        <p class="price">Rs. <?= number_format((float)$product['price'], 2) ?></p>
                        <a class="btn-primary" href="pages/product.php?id=<?= (int)$product['id'] ?>">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php include __DIR__ . "/includes/chat_widget.php"; ?>
<script src="/SHOPHUBBB/assets/script.js"></script>
</body>
</html>
