<?php
session_start();
include "../config/db.php";

// Protect admin page
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    echo "Access Denied!";
    exit();
}

// Delete product if ?delete=id is passed
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE product_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("DELETE FROM reviews WHERE product_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("DELETE FROM product_views WHERE product_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: manage_product.php");
    exit();
}

// Fetch all products
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>

<h2>Manage Products</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Actions</th>
    </tr>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= $row['price'] ?></td>
            <td>
                <a href="edit_product.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="manage_product.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<a href="dashboard.php">Back to Dashboard</a>
