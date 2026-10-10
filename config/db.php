<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect("localhost", "root", "", "shophub");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
