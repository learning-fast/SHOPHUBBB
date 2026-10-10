<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit;
}

$id = (int)$_GET['id'];

$conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");

header("Location: manage_users.php");
exit;