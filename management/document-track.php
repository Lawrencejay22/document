<?php
session_start();
require_once '../PHP/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login form/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'General';
$error = '';
$success = '';

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
        $filename = basename($_FILES['file']['name']);
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['file']['size'];
        
        $size_str = number_format($filesize / 1048576, 2) . ' MB';

        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $filename);
            $upload_dir = '../uploads/';
            
            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dest_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO documents (title, description, filename, file_path, category, uploaded_by, file_size) VALUES (:title, :description, :filename, :file_path, :category, :uploaded_by, :file_size)");
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':filename', $filename);
                    $stmt->bindParam(':file_path', $dest_path);
                    $stmt->bindParam(':category', $category);
                    $stmt->bindParam(':uploaded_by', $user_id);
                    $stmt->bindParam(':file_size', $size_str);
                    $stmt->execute();
                    $success = "Document uploaded successfully!";
                } catch (PDOException $e) {
                    $error = "Database Error: " . $e->getMessage();
                }
            } else {
                $error = "Failed to move the uploaded file.";
            }
        } else {
            $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
        }
    } else {
        $error = "Error uploading file. Please try again.";
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    $doc_id = $_POST['delete_id'] ?? 0;
    try {
        $stmt = $pdo->prepare("SELECT file_path, uploaded_by FROM documents WHERE id = :id");
        $stmt->bindParam(':id', $doc_id);
        $stmt->execute();
        $doc = $stmt->fetch();
        
        if ($doc) {
            if ($doc['uploaded_by'] == $user_id || $user_role === 'Admin') {
                if (file_exists($doc['file_path'])) {
                    unlink($doc['file_path']);
                }
                $del_stmt = $pdo->prepare("DELETE FROM documents WHERE id = :id");
                $del_stmt->bindParam(':id', $doc_id);
                $del_stmt->execute();
                $success = "Document deleted successfully.";
            } else {
                $error = "You do not have permission to delete this document.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error deleting document: " . $e->getMessage();
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_doc'])) {
    $doc_id = $_POST['edit_id'] ?? 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT file_path, uploaded_by FROM documents WHERE id = :id");
        $stmt->bindParam(':id', $doc_id);
        $stmt->execute();
        $doc = $stmt->fetch();
        
        if ($doc) {
            if ($doc['uploaded_by'] == $user_id || $user_role === 'Admin') {
                
                // Handle optional file replacement
                if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
                    $filename = basename($_FILES['file']['name']);
                    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                    $filesize = $_FILES['file']['size'];
                    $size_str = number_format($filesize / 1048576, 2) . ' MB';

                    if (in_array(strtolower($filetype), $allowed)) {
                        $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $filename);
                        $upload_dir = '../uploads/';
                        $dest_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest_path)) {
                            // Delete the old file from disk
                            if (file_exists($doc['file_path'])) {
                                unlink($doc['file_path']);
                            }
                            
                            $upd_stmt = $pdo->prepare("UPDATE documents SET title = :title, description = :description, category = :category, filename = :filename, file_path = :file_path, file_size = :file_size WHERE id = :id");
                            $upd_stmt->bindParam(':filename', $filename);
                            $upd_stmt->bindParam(':file_path', $dest_path);
                            $upd_stmt->bindParam(':file_size', $size_str);
                        } else {
                            throw new Exception("Failed to upload the new file.");
                        }
                    } else {
                        throw new Exception("Invalid file type.");
                    }
                } else {
                    // Update metadata only
                    $upd_stmt = $pdo->prepare("UPDATE documents SET title = :title, description = :description, category = :category WHERE id = :id");
                }
                
                if (isset($upd_stmt)) {
                    $upd_stmt->bindParam(':title', $title);
                    $upd_stmt->bindParam(':description', $description);
                    $upd_stmt->bindParam(':category', $category);
                    $upd_stmt->bindParam(':id', $doc_id);
                    $upd_stmt->execute();
                    $success = "Document updated successfully.";
                }
            } else {
                $error = "You do not have permission to edit this document.";
            }
        }
    } catch (Exception $e) {
        $error = "Error updating document: " . $e->getMessage();
    }
}

// Redirect after POST to prevent form resubmission on refresh
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($success)) {
        $_SESSION['flash_success'] = $success;
    }
    if (!empty($error)) {
        $_SESSION['flash_error'] = $error;
    }
    header("Location: document-track.php");
    exit();
}

// Fetch documents from DB
try {
    $query = "SELECT d.*, u.username FROM documents d JOIN users u ON d.uploaded_by = u.id ORDER BY d.upload_date DESC";
    $stmt = $pdo->query($query);
    $documents = $stmt->fetchAll();
} catch (PDOException $e) {
    $documents = [];
    $error = "Failed to fetch documents: " . $e->getMessage();
}

// Fetch all users except the logged-in user for chat
try {
    $stmt = $pdo->prepare("SELECT id, username, role, profile_picture FROM users WHERE id != :id ORDER BY username ASC");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $chat_users = $stmt->fetchAll();
} catch (PDOException $e) {
    $chat_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="document-track.css">
    <link rel="stylesheet" href="chat.css">
    <title>DocHub - Document Tracker</title>
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
                <p class="section-title">MAIN MENU</p>
                <nav>
                    <ul>
                        <li class="active" id="navDashboard">
                            <a href="#"><i class="fa-solid fa-table-columns" style="color: var(--theme-color); margin-right: 8px;"></i> Dashboard</a>
                        </li>
                        <li id="navChats">
                            <a href="#"><i class="fa-solid fa-comments" style="color: var(--color-purple); margin-right: 8px;"></i> Chats</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="sidebar-section" style="margin-top: 24px;">
                <p class="section-title">CATEGORIES</p>
                <nav>
                    <ul>
                        <li>
                            <a href="#"><i class="fa-solid fa-globe"></i> All Documents</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-earth-americas"
                                    style="color: var(--color-green); margin-right: 8px;"></i> Public</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-lock"
                                    style="color: var(--color-gray); margin-right: 8px;"></i> Private</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-chalkboard-user"
                                    style="color: var(--color-blue); margin-right: 8px;"></i> Faculty Only</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-users"
                                    style="color: var(--color-purple); margin-right: 8px;"></i> HR Only</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-graduation-cap"
                                    style="color: var(--color-yellow); margin-right: 8px;"></i> Registrar Only</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-solid fa-coins"
                                    style="color: var(--color-teal); margin-right: 8px;"></i> Finance Only</a>
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
                <button class="btn-primary" onclick="window.location.reload()" style="margin-right: 15px; background-color: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
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
                <div class="table-container">
                    <table class="document-table">
                        <thead>
                            <tr>
                                <th>Document Details</th>
                                <th>Shared By</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="document-list">
                            <?php foreach($documents as $doc): ?>
                                <?php
                                    // Role-based access logic
                                    $show_doc = true;
                                    if ($doc['category'] === 'Private' && $doc['uploaded_by'] != $user_id) {
                                        $show_doc = false;
                                    } elseif ($doc['category'] === 'Faculty Only' && !in_array($user_role, ['Faculty Only', 'Admin'])) {
                                        $show_doc = false;
                                    } elseif ($doc['category'] === 'HR Only' && !in_array($user_role, ['HR Only', 'Admin'])) {
                                        $show_doc = false;
                                    } elseif ($doc['category'] === 'Registrar Only' && !in_array($user_role, ['Registrar Only', 'Admin'])) {
                                        $show_doc = false;
                                    } elseif ($doc['category'] === 'Finance Only' && !in_array($user_role, ['Finance Only', 'Admin'])) {
                                        $show_doc = false;
                                    }

                                    if ($show_doc):
                                        $badgeClass = 'badge-green';
                                        $badgeText = strtoupper($doc['category']);
                                        $iconClass = 'fa-file pdf'; // default
                                        if ($doc['category'] === 'Private') { $badgeClass = 'badge-gray'; $iconClass = 'fa-lock lock'; }
                                        elseif ($doc['category'] === 'Faculty Only') { $badgeClass = 'badge-blue'; }
                                        elseif ($doc['category'] === 'HR Only') { $badgeClass = 'badge-purple'; }
                                        elseif ($doc['category'] === 'Registrar Only') { $badgeClass = 'badge-yellow'; $iconClass = 'fa-file-excel excel'; }
                                        elseif ($doc['category'] === 'Finance Only') { $badgeClass = 'badge-teal'; }
                                ?>
                                <tr>
                                    <td>
                                        <div class="doc-info">
                                            <i class="fa-solid <?= $iconClass ?> doc-icon"></i>
                                            <div>
                                                <h4><?= htmlspecialchars($doc['title']) ?></h4>
                                                <p class="desc"><?= htmlspecialchars($doc['description']) ?></p>
                                                <p class="filename"><i class="fa-regular <?= $iconClass ?>"></i> <?= htmlspecialchars($doc['filename']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="shared-by">
                                            <div class="avatar" style="background: var(--primary-color);"><?= strtoupper(substr($doc['username'], 0, 2)) ?></div>
                                            <div>
                                                <p class="name"><?= htmlspecialchars($doc['username']) ?> shared</p>
                                                <p class="time"><?= date('H:i \o\n M d, Y', strtotime($doc['upload_date'])) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                                    <td><?= htmlspecialchars($doc['file_size']) ?></td>
                                    <td>
                                        <div class="action-group">
                                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn-action view" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" class="btn-action download" title="Download" download><i class="fa-solid fa-download"></i></a>
                                            <?php if ($doc['uploaded_by'] == $user_id || $user_role === 'Admin'): ?>
                                                <button type="button" class="btn-action edit" title="Edit" onclick="openEditModal(<?= $doc['id'] ?>, '<?= htmlspecialchars(addslashes($doc['title'])) ?>', '<?= htmlspecialchars(addslashes($doc['description'])) ?>', '<?= htmlspecialchars(addslashes($doc['category'])) ?>')"><i class="fa-solid fa-edit"></i></button>
                                                <form action="document-track.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this document? This cannot be undone.');">
                                                    <input type="hidden" name="delete_id" value="<?= $doc['id'] ?>">
                                                    <button type="submit" name="delete_doc" class="btn-action delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (empty($documents)): ?>
                                <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No documents available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="chat-container" id="chatSection" style="display: none; height: calc(100vh - 150px); margin-top: 20px;">
                    <!-- Left Pane: User List -->
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">Conversations</div>
                        <div class="user-list">
                            <?php foreach ($chat_users as $u): ?>
                                <div class="user-item" onclick="selectUser(this, <?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['role'])) ?>', '<?= !empty($u['profile_picture']) ? htmlspecialchars(addslashes($u['profile_picture'])) : '' ?>')">
                                    <div class="chat-avatar">
                                        <?php if (!empty($u['profile_picture'])): ?>
                                            <img src="<?= htmlspecialchars($u['profile_picture']) ?>" alt="Profile">
                                        <?php else: ?>
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-details">
                                        <h4><?= htmlspecialchars($u['username']) ?></h4>
                                        <p><?= htmlspecialchars($u['role']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($chat_users)): ?>
                                <div style="padding: 20px; color: var(--text-muted); text-align: center;">No other users found.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Pane: Chat Window -->
                    <div class="chat-window">
                        <div id="noChatSelected" class="no-chat-selected">
                            <i class="fa-regular fa-comments"></i>
                            <p>Select a user to start chatting</p>
                        </div>
                        <div id="activeChat" style="display: none; flex: 1; flex-direction: column; height: 100%;">
                            <div class="chat-header">
                                <div class="chat-avatar" id="activeChatAvatar"></div>
                                <div class="user-details">
                                    <h4 id="activeChatName">Username</h4>
                                    <p id="activeChatRole">Role</p>
                                </div>
                            </div>
                            <div class="chat-messages" id="chatMessages" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px;"></div>
                            <div class="chat-input-area">
                                <input type="hidden" id="receiverId">
                                <input type="text" id="messageInput" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                                <button class="btn-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Share New Document</h3>
                <button class="close-modal"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="uploadForm" action="document-track.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="docTitle">Document Title</label>
                    <input type="text" id="docTitle" name="title" required placeholder="Enter document title">
                </div>
                <div class="input-group">
                    <label for="docDesc">Description</label>
                    <textarea id="docDesc" name="description" rows="3" required placeholder="Enter description"></textarea>
                </div>
                <div class="input-group">
                    <label for="docCategory">Access Visibility</label>
                    <select id="docCategory" name="category" required>
                        <option value="Public">🌐 Public (All users can view and download)</option>
                        <option value="Private">🔒 Private (Only the uploader can view and access)</option>
                        <option value="Faculty Only">🏫 Faculty Only</option>
                        <option value="HR Only">👔 HR Only</option>
                        <option value="Registrar Only">🎓 Registrar Only</option>
                        <option value="Finance Only">💰 Finance Only</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="docFile">File</label>
                    <input type="file" id="docFile" name="file" required>
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" name="upload" class="btn-primary">Upload Document</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Document</h3>
                <button type="button" class="close-modal" onclick="closeEditModal()"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="editForm" action="document-track.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editDocId" name="edit_id">
                <div class="input-group">
                    <label for="editDocTitle">Document Title</label>
                    <input type="text" id="editDocTitle" name="title" required placeholder="Enter document title">
                </div>
                <div class="input-group">
                    <label for="editDocDesc">Description</label>
                    <textarea id="editDocDesc" name="description" rows="3" required placeholder="Enter description"></textarea>
                </div>
                <div class="input-group">
                    <label for="editDocCategory">Access Visibility</label>
                    <select id="editDocCategory" name="category" required>
                        <option value="Public">🌐 Public (All users can view and download)</option>
                        <option value="Private">🔒 Private (Only the uploader can view and access)</option>
                        <option value="Faculty Only">🏫 Faculty Only</option>
                        <option value="HR Only">👔 HR Only</option>
                        <option value="Registrar Only">🎓 Registrar Only</option>
                        <option value="Finance Only">💰 Finance Only</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="editDocFile">Update File (Optional)</label>
                    <input type="file" id="editDocFile" name="file">
                    <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 4px;">Leave empty to keep the current file.</small>
                </div>
                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" name="edit_doc" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="document-track.js?v=<?= time() ?>"></script>
    <script>
        function openEditModal(id, title, desc, category) {
            document.getElementById('editDocId').value = id;
            document.getElementById('editDocTitle').value = title;
            document.getElementById('editDocDesc').value = desc;
            document.getElementById('editDocCategory').value = category;
            document.getElementById('editModal').classList.add('active');
        }
        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }
    </script>
</body>

</html>
