<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');

    if ($name === "") {
        $error = "Category name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            $message = "Category added successfully.";
        } else {
            $error = "Error adding category: " . $stmt->error;
        }
        $stmt->close();
    }
}

$categories = [];
$result = $conn->query("SELECT * FROM categories ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Category</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header class="navbar">
        <a href="../index.php" class="logo">ShopHub</a>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="add_category.php">Add Category</a>
            <a href="add_product.php">Add Product</a>
            <a href="../pages/logout.php">Logout</a>
        </nav>
    </header>

    <section class="form-section">
        <div class="form-container">
            <h2>Add Category</h2>

            <?php if ($error !== ""): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <p style="color:green;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="name">Category Name:</label>
                <input type="text" id="name" name="name" required>
                <button class="btn-primary" type="submit">Add Category</button>
            </form>

            <h3 style="margin-top:20px;">All Categories</h3>
            <?php foreach ($categories as $cat): ?>
                <p><?= htmlspecialchars($cat['name']) ?></p>
            <?php endforeach; ?>
        </div>
    </section>
</body>
</html>
