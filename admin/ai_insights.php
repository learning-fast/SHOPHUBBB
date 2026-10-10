<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/shop_features.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access Denied!";
    exit();
}

shophub_ensure_feature_tables($conn);

$chatLogs = $conn->query("SELECT * FROM chat_logs ORDER BY id DESC LIMIT 10");
$popularViews = $conn->query("
    SELECT p.name, COUNT(v.id) AS views
    FROM product_views v
    JOIN products p ON p.id = v.product_id
    GROUP BY p.id, p.name
    ORDER BY views DESC
    LIMIT 10
");
$wishlistStats = $conn->query("
    SELECT p.name, COUNT(w.id) AS saves
    FROM wishlist w
    JOIN products p ON p.id = w.product_id
    GROUP BY p.id, p.name
    ORDER BY saves DESC
    LIMIT 10
");
$reviewStats = $conn->query("
    SELECT p.name, ROUND(AVG(r.rating), 1) AS rating, COUNT(r.id) AS reviews
    FROM reviews r
    JOIN products p ON p.id = r.product_id
    GROUP BY p.id, p.name
    ORDER BY rating DESC, reviews DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - ShopHub</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-insights { padding-top: 2rem; padding-bottom: 3rem; }
        .insight-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
        .insight-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .insight-card h3 { margin-bottom: .75rem; }
        .insight-card table { width: 100%; border-collapse: collapse; }
        .insight-card td, .insight-card th { border-bottom: 1px solid #e5e7eb; padding: .5rem; text-align: left; font-size: .9rem; vertical-align: top; }
    </style>
</head>
<body>
    <header class="navbar">
        <a href="dashboard.php" class="logo">ShopHub Admin</a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="../index.php">Shop</a>
        </nav>
    </header>

    <main class="container admin-insights">
        <h2 class="section-title" style="margin-top:0;">AI Insights</h2>
        <div class="insight-grid">
            <section class="insight-card">
                <h3>Recent Chatbot Logs</h3>
                <table>
                    <tr><th>Message</th><th>Intent</th></tr>
                    <?php while ($row = $chatLogs->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['message']) ?></td>
                            <td><?= htmlspecialchars($row['intent']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </section>

            <section class="insight-card">
                <h3>Most Viewed Products</h3>
                <table>
                    <tr><th>Product</th><th>Views</th></tr>
                    <?php while ($row = $popularViews->fetch_assoc()): ?>
                        <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= (int)$row['views'] ?></td></tr>
                    <?php endwhile; ?>
                </table>
            </section>

            <section class="insight-card">
                <h3>Wishlist Saves</h3>
                <table>
                    <tr><th>Product</th><th>Saves</th></tr>
                    <?php while ($row = $wishlistStats->fetch_assoc()): ?>
                        <tr><td><?= htmlspecialchars($row['name']) ?></td><td><?= (int)$row['saves'] ?></td></tr>
                    <?php endwhile; ?>
                </table>
            </section>

            <section class="insight-card">
                <h3>Top Rated Products</h3>
                <table>
                    <tr><th>Product</th><th>Rating</th><th>Reviews</th></tr>
                    <?php while ($row = $reviewStats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['rating']) ?></td>
                            <td><?= (int)$row['reviews'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </section>
        </div>
    </main>
</body>
</html>
