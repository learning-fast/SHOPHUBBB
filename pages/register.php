<?php
session_start();
include "../config/db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($name === "" || $email === "" || $passwordRaw === "" || $confirmPassword === "") {
        $error = "All fields are required.";
    } elseif ($passwordRaw !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
        if (mysqli_query($conn, $query)) {
            $message = "Registration successful. You can login now.";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopHub - Sign Up</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <a href="../index.php" class="logo">ShopHub</a>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Signup</a>
        </nav>
    </header>

    <button class="back-arrow" onclick="history.back()" title="Go Back">&#8592;</button>

    <section class="form-section">
        <div class="form-container">
            <h2>Sign Up</h2>

            <?php if ($error !== ""): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($message !== ""): ?>
                <p style="color:green;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <form id="signupForm" method="POST" action="">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>

                <button type="submit" class="btn-primary">Sign Up</button>
            </form>

            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </section>
</body>
</html>
