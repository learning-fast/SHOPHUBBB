<?php
session_start();
include "../config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Fetch user from DB
    $query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Check if password exists
        if (!empty($user['password']) && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            // Role-based redirect
           // Role-based redirect
if ($user['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    // Check if we have a saved product page to go back to
    if (isset($_SESSION['redirect_to'])) {
        $url = $_SESSION['redirect_to'];
        unset($_SESSION['redirect_to']); // Clear it so it's fresh for next time
        header("Location: " . $url);
    } else {
        header("Location: ../index.php");
    }
}
            exit;
        } else {
            $error = "Wrong password";
        }
    } else {
        $error = "User not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopHub - Login</title>
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
            <h2>Login</h2>

            <?php if ($error !== ""): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit" class="btn-primary">Login</button>
            </form>

            <p>Don't have an account? <a href="register.php">Sign Up</a></p>
        </div>
    </section>

    <script src="../js/script.js"></script>
</body>
</html>