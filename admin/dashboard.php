<?php
session_start();
require_once __DIR__ . "/../config/db.php";  

// Protect page: only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "Access Denied!";
    exit();
}

// Fetch dashboard metrics safely
$totalUsersResult = $conn->query("SELECT COUNT(*) as c FROM users");
$totalUsers = $totalUsersResult ? $totalUsersResult->fetch_assoc()['c'] : 0;

$totalOrdersResult = $conn->query("SELECT COUNT(*) as c FROM orders");
$totalOrders = $totalOrdersResult ? $totalOrdersResult->fetch_assoc()['c'] : 0;

$totalProductsResult = $conn->query("SELECT COUNT(*) as c FROM products");
$totalProducts = $totalProductsResult ? $totalProductsResult->fetch_assoc()['c'] : 0;

// Correct revenue column
$totalRevenueResult = $conn->query("SELECT SUM(total_amount) as total FROM orders");
$totalRevenue = $totalRevenueResult ? $totalRevenueResult->fetch_assoc()['total'] : 0;
$totalRevenue = $totalRevenue ?? 0; // ensures 0 if NULL
$adminNotice = $_SESSION['admin_notice'] ?? "";
unset($_SESSION['admin_notice']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .logo {
    margin-top: 20px;
    text-align: center;
}

.logo img {
    width: 150px;
    height: auto;
}
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 { color: #333; margin-top: 40px; text-align: center; }
        .metrics { display: flex; flex-wrap: wrap; justify-content: center; margin-top: 20px; gap: 20px; }
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            min-width: 150px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-weight: 600;
        }
        ul { list-style: none; padding: 0; width: 100%; max-width: 400px; margin-top: 40px; }
        li { margin-bottom: 15px; }
        li a {
            display: block;
            background-color: #00BFFF;
            color: white;
            text-decoration: none;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s ease, transform 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        li a:hover { background-color: #0099CC; transform: translateY(-2px); }
        li a[href="logout.php"] { background-color: #ff4444; margin-top: 20px; }
        li a[href="logout.php"]:hover { background-color: #cc0000; }
        @media (max-width: 480px) { ul { width: 90%; } .metrics { flex-direction: column; align-items: center; } }
    </style>
</head>
<body>

<h2><b>Welcome, Admin!<br>Admin Dashboard</b></h2>
<?php if ($adminNotice !== ""): ?>
    <p style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; padding:12px; border-radius:8px; max-width:600px; text-align:center;">
        <?= htmlspecialchars($adminNotice) ?>
    </p>
<?php endif; ?>
<div class="logo">
    <img src="../assets/images/logo.png" alt="ShopHub Logo">
</div>

<div class="metrics">
    <div class="card">Total Users<br><?= $totalUsers ?></div>
    <div class="card">Total Products<br><?= $totalProducts ?></div>
    <div class="card">Total Orders<br><?= $totalOrders ?></div>
    <div class="card">Total Revenue<br>Rs. <?= number_format($totalRevenue, 2) ?></div>
</div>

<ul>
    <li><a href="add_product.php">Add Product</a></li>
    <li><a href="manage_product.php">Manage Products</a></li>
    <li><a href="manage_users.php">Manage Users</a></li>
    <li><a href="manage_orders.php">Manage Orders</a></li>
    <li><a href="ai_insights.php">AI Insights</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>
