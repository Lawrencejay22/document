<?php
session_start();
require_once '../PHP/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Get department from form, default to 'General'
    $role = $_POST['department'] ?? 'General';

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Username or Email already exists!';
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all fields.';
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
    <title>Register</title>
</head>

<body>
    <div class="background-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
    
    <div class="login-container">
        <form action="register.php" method="POST" id="registerForm">
            <div class="form-header">
                <h3>Create Account</h3>
                <p>Please enter your details to register.</p>
                <?php if ($error): ?>
                    <p style="color: #ef4444; margin-top: 10px;"><?= htmlspecialchars($error) ?></p>
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
                <label for="email">Email</label>
                <div class="input-field">
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                    <i class="fa-solid fa-envelope"></i>
                </div>
            </div>

            <div class="input-group">
                <label for="department">Department</label>
                <div class="input-field">
                    <select id="department" name="department" required style="width: 100%; padding: 14px 16px 14px 45px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #f8fafc; font-size: 15px; font-family: inherit; appearance: none; outline: none; cursor: pointer;">
                        <option value="" disabled selected style="background: #1e293b;">Select your department</option>
                        <option value="Public" style="background: #1e293b;">🌐 Public (All users can view and download)</option>
                        <option value="Private" style="background: #1e293b;">🔒 Private (Only the uploader can view and access)</option>
                        <option value="Faculty Only" style="background: #1e293b;">🏫 Faculty Only</option>
                        <option value="HR Only" style="background: #1e293b;">👔 HR Only</option>
                        <option value="Registrar Only" style="background: #1e293b;">🎓 Registrar Only</option>
                        <option value="Finance Only" style="background: #1e293b;">💰 Finance Only</option>
                        <option value="Admin" style="background: #1e293b;">👑 Admin</option>
                    </select>
                    <i class="fa-solid fa-building"></i>
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
                <button type="submit" class="btn-login">Register</button>
            </div>

            <div class="links">
                <a href="login.php">Already have an account? Login</a>
                <a href="index.html" class="home-link">Go back to home page</a>
            </div>

            <div class="social-login">
                <p>Or continue with</p>
                <div class="icons">
                    <a href="https://myaccount.google.com/signin" class="social-icon"><i
                            class="fa-brands fa-google"></i></a>
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
