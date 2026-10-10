<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id = (int)$_GET['id'];

$conn->query("DELETE FROM orders WHERE id = $id");

header("Location: manage_orders.php");
exit;