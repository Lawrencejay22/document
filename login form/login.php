<?php
session_start();
require_once '../PHP/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, profile_picture FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                
                header("Location: ../management/document-track.php");
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        } catch (PDOException $e) {
            // Automatically add the column if it's missing!
            try {
                $pdo->exec("ALTER TABLE users ADD profile_picture VARCHAR(255) NULL DEFAULT NULL");
                
                // Retry the fetch
                $stmt = $pdo->prepare("SELECT id, username, password, role, profile_picture FROM users WHERE username = :username");
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Password is correct, start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;
                    
                    header("Location: ../management/document-track.php");
                    exit();
                } else {
                    $error = "Invalid username or password!";
                }
            } catch (PDOException $e2) {
                $error = "Error: " . $e2->getMessage();
            }
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <title>Login Page</title>
</head>

<body>
    <div class="background-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
    
    <div class="login-container">
        <form action="login.php" method="POST" id="loginForm">
            <div class="form-header">
                <h3>Welcome Back</h3>
                <p>Please enter your details to sign in.</p>
                <?php if ($error): ?>
                    <p style="color: #ef4444; margin-top: 10px;"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <p style="color: #10b981; margin-top: 10px;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <div class="input-field">
                    <input type="text" id="username" name="username" required placeholder="Enter your username">
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <div class="input-field">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-login">Login</button>
            </div>
            
            <div class="links">
                <a href="register.php">Don't have an account? Register</a>
                <a href="forgot-password.html">Forgot Password?</a>
                <a href="index.html" class="home-link">Go back to home page</a>
            </div>

            <div class="social-login">
                <p>Or continue with</p>
                <div class="icons">
                    <a href="https://myaccount.google.com/signin" class="social-icon"><i class="fa-brands fa-google"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
        </form>
    </div>
</body>

</html>
