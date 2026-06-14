<?php
session_start();

// Database configuration (Update these with your actual XAMPP settings)
$host = 'localhost';
$dbname = 'document_management';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get POST data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Use department as role if provided, else default to General
        $role = $_POST['department'] ?? 'General';

        try {
            // Prepare the SQL statement
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            
            
            if ($stmt->execute()) {
                // Registration successful, redirect to login page
                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header("Location: ../login form/login.html");
                exit();
            }
        } catch (PDOException $e) {
            // Check for duplicate entry (e.g., username or email already exists)
            if ($e->getCode() == 23000) {
                echo "<script>alert('Username or Email already exists!'); window.history.back();</script>";
            } else {
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            }
        }
    } else {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
    }
}
?>
