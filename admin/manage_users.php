<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$result = $conn->query("SELECT id, name, email, role FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
</head>
<body>

<h2>Manage Users</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td>
    <?php if ($row['role'] !== 'admin'): ?>
        <a href="delete_user.php?id=<?= $row['id'] ?>" 
           onclick="return confirm('Delete this user?')">
           Delete
        </a>
    <?php else: ?>
        -
    <?php endif; ?>
</td>

            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $row['role'] ?></td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>