<?php

session_start();
require_once __DIR__ . "/../config/db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    echo "Access Denied!";
    exit();
}


//if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  //  header("Location: ../pages/login.php");
  //  exit;
//}

$message = "";
$error = "";

$categoryResult = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($name === "" || $price <= 0 || $category_id <= 0 || !isset($_FILES['image'])) {
        $error = "All required fields must be filled.";
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Image upload failed.";
    } else {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        $imageName = basename($_FILES['image']['name']);
        $tmpName = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $error = "Only JPG, JPEG, PNG, WEBP images are allowed.";
        } else {
            $uploadDir = __DIR__ . "/../uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = time() . "_" . preg_replace('/[^A-Za-z0-9._-]/', '_', $imageName);
            $imagePath = $uploadDir . $newFileName;

            if (move_uploaded_file($tmpName, $imagePath)) {
                $dbImagePath = "uploads/" . $newFileName;

                $stmt = $conn->prepare("INSERT INTO products (category_id, name, price, image, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isdss", $category_id, $name, $price, $dbImagePath, $description);

                if ($stmt->execute()) {
                    $message = "Product added successfully.";
                } else {
                    $error = "Database error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Could not save uploaded image.";
            }
        }
    }
}

$products = [];
$result = $conn->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Product</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-wrap { max-width: 1100px; margin: 2rem auto; padding: 0 5%; }
        .product { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 12px; background: #fff; }
        .product img { max-width: 200px; height: auto; display: block; margin-top: 8px; }
        textarea { width: 100%; min-height: 90px; margin-bottom: 1rem; padding: .75rem; border: 1px solid #d1d5db; border-radius: 6px; }
        select, input[type="file"] { width: 100%; margin-bottom: 1rem; }
    </style>
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
        <div class="form-container" style="max-width:600px;">
            <h2>Add Product</h2>

            <?php if ($error !== ""): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <p style="color:green;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>

                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php if ($categoryResult): ?>
                        <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>

                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" required>

                <label for="image">Image</label>
                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" required>

                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>

                <button class="btn-primary" type="submit">Add Product</button>
            </form>
        </div>
    </section>

    <div class="admin-wrap">
        <h2 class="section-title" style="margin-top:0;">All Products</h2>
        <?php foreach ($products as $product): ?>
            <div class="product">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><b>Category:</b> <?= htmlspecialchars($product['category_name']) ?></p>
                <p><b>Price:</b> Rs. <?= number_format((float)$product['price'], 2) ?></p>
                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
