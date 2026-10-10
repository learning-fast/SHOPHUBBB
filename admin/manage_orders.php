<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$result = $conn->query("
    SELECT o.id, u.name, o.total_amount, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
</head>
<body>

<h2>Manage Orders</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>Order ID</th>
        <th>User</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>
        <tr><td>
    <a href="delete_order.php?id=<?= $row['id'] ?>"
       onclick="return confirm('Delete this order?')">
       Delete
    </a>
    </td>
            <td>#<?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>Rs. <?= number_format($row['total_amount'], 2) ?></td>
            <td><?= $row['status'] ?? 'Pending' ?></td>
            <td><?= $row['created_at'] ?></td>
            <td>
<form method="POST" action="update_status.php">
    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
    <select name="status">
        <option <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
        <option <?= $row['status']=='Shipped'?'selected':'' ?>>Shipped</option>
        <option <?= $row['status']=='Delivered'?'selected':'' ?>>Delivered</option>
    </select>
    <button type="submit">Update</button>
</form>
</td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>