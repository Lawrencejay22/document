<?php
session_start();
require_once '../PHP/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login%20form/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'General';
$error = '';
$success = '';

// Fetch current user details
try {
    $stmt = $pdo->prepare("SELECT username, email, role, created_at, profile_picture FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $current_user = $stmt->fetch();
} catch (PDOException $e) {
    // Automatically add the column if it's missing!
    try {
        $pdo->exec("ALTER TABLE users ADD profile_picture VARCHAR(255) NULL DEFAULT NULL");
        
        // Retry the fetch
        $stmt = $pdo->prepare("SELECT username, email, role, created_at, profile_picture FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        $current_user = $stmt->fetch();
    } catch (PDOException $e2) {
        $error = "Failed to fetch user details: " . $e2->getMessage();
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Handle Profile Picture Upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = basename($_FILES['profile_picture']['name']);
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
            $upload_dir = '../uploads/profiles/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
                $stmt->bindParam(':profile_picture', $dest_path);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                
                $_SESSION['profile_picture'] = $dest_path;
                $current_user['profile_picture'] = $dest_path;
                $success .= "Profile picture updated! ";
            } else {
                $error .= "Failed to upload profile picture. ";
            }
        } else {
            $error .= "Invalid file type. Allowed: jpg, jpeg, png, gif. ";
        }
    }

    if (!empty($new_email)) {
        try {
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET email = :email, password = :password WHERE id = :id");
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $error = "Passwords do not match.";
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :id");
            }

            if (empty($error) && isset($stmt)) {
                $stmt->bindParam(':email', $new_email);
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $success = "Profile updated successfully!";
                $current_user['email'] = $new_email; // Update local variable for display
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Email already exists!';
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = "Email cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="document-track.css">
    <title>DocHub - User Profile</title>
    <style>
        .profile-container {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            max-width: 650px;
            margin: 0 auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .profile-header .avatar-large {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--theme-color), #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: 700;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
        }
        
        .profile-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .profile-header p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 4px;
        }

        .profile-form .input-group {
            margin-bottom: 24px;
        }

        .profile-form label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .profile-form input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #f8fafc;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            outline: none;
        }

        .profile-form input:focus {
            border-color: var(--theme-color);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .profile-form input:disabled {
            background: rgba(15, 23, 42, 0.3);
            color: #64748b;
            cursor: not-allowed;
            border-color: transparent;
        }
    </style>
</head>

<body>
    <div class="background-circles">
        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>
    </div>
    <div class="app-container">
        <!-- ============= sidebar ============= -->
        <aside class="sidebar">
            <div class="logo-div">
                <i class="fa-solid fa-folder-open logo-icon"></i>
                <h2>Docker-Up</h2>
            </div>

            <div class="sidebar-section">
                <p class="section-title">NAVIGATION</p>
                <nav>
                    <ul>
                        <li>
                            <a href="document-track.php"><i class="fa-solid fa-home"></i> Dashboard</a>
                        </li>
                        <li class="active">
                            <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- ============= dashboard ============= -->
        <main class="main-content">
            <header class="top-bar">
                <div style="color: white; font-weight: 500;">
                    Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= htmlspecialchars($_SESSION['role']) ?>)
                </div>
                <div style="flex:1;"></div>
                <button class="btn-primary" id="shareBtn" style="margin-right: 15px;"><i class="fa-solid fa-plus"></i> Share Document</button>
                <div class="user-profile" style="cursor: pointer; transition: all 0.3s;" onclick="window.location.href='profile.php'" onmouseover="this.style.background='rgba(59, 130, 246, 0.2)'" onmouseout="this.style.background='rgba(15, 23, 42, 0.6)'">
                    <div class="profile-avatar" style="overflow: hidden;">
                        <?php if (!empty($_SESSION['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                        <span class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'General') ?></span>
                    </div>
                    <a href="../PHP/logout.php" class="btn-logout" title="Logout" onclick="event.stopPropagation();"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </div>
                <div class="search-bar">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" placeholder="Search documents...">
                </div>
            </header>

            <section class="dashboard-section" id="global">
                <?php if ($error): ?>
                    <div style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="avatar-large" style="overflow: hidden;">
                            <?php if (!empty($current_user['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($current_user['profile_picture']) ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?= htmlspecialchars($current_user['username']) ?></h2>
                            <p><i class="fa-solid fa-id-badge"></i> Role: <?= htmlspecialchars($current_user['role']) ?></p>
                            <p><i class="fa-regular fa-calendar-days"></i> Joined: <?= date('F j, Y', strtotime($current_user['created_at'])) ?></p>
                        </div>
                    </div>

                    <form action="profile.php" method="POST" class="profile-form" enctype="multipart/form-data">
                        <div class="input-group">
                            <label for="username">Username (Cannot be changed)</label>
                            <input type="text" id="username" value="<?= htmlspecialchars($current_user['username']) ?>" disabled>
                        </div>

                        <div class="input-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($current_user['email']) ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="padding: 10px;">
                        </div>
                        
                        <h4 style="margin: 35px 0 20px; color: var(--text-dark); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Security</h4>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Leave these fields blank if you don't want to change your password.</p>
                        
                        <div class="input-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>

                        <div class="form-actions" style="margin-top: 35px;">
                            <button type="submit" name="update_profile" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 16px; font-weight: 600;">Save Profile Changes</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
