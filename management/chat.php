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

// Auto-create messages table if it doesn't exist
try {
    $createTableQuery = "CREATE TABLE IF NOT EXISTS `messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `sender_id` int(11) NOT NULL,
      `receiver_id` int(11) NOT NULL,
      `message_text` text NOT NULL,
      `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($createTableQuery);
} catch (PDOException $e) {
    // Ignore errors gracefully
}

// Fetch all users except the logged-in user
try {
    $stmt = $pdo->prepare("SELECT id, username, role, profile_picture FROM users WHERE id != :id ORDER BY username ASC");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="chat.css">
    <title>DocHub - Chat</title>
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
                        <li>
                            <a href="document-track.php"><i class="fa-solid fa-table-columns" style="color: var(--theme-color); margin-right: 8px;"></i> Dashboard</a>
                        </li>
                        <li class="active">
                            <a href="chat.php"><i class="fa-solid fa-comments" style="color: var(--color-purple); margin-right: 8px;"></i> Chats</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="sidebar-section" style="margin-top: 24px;">
                <p class="section-title">CATEGORIES</p>
                <nav>
                    <ul>
                        <li>
                            <a href="document-track.php"><i class="fa-solid fa-globe"></i> All Documents</a>
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

        <!-- ============= Main Content ============= -->
        <main class="main-content">
            <header class="top-bar">
                <div style="color: white; font-weight: 500; font-size: 20px;">
                    Messages
                </div>
                <div style="flex:1;"></div>
                <div class="user-profile" onclick="window.location.href='profile.php'">
                    <div class="profile-avatar">
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
            </header>

            <div class="chat-container">
                <!-- Left Pane: User List -->
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        Conversations
                    </div>
                    <div class="user-list">
                        <?php foreach ($users as $u): ?>
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
                        <?php if (empty($users)): ?>
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

                        <div class="chat-messages" id="chatMessages">
                            <!-- Messages will be loaded here via JS -->
                        </div>

                        <div class="chat-input-area">
                            <input type="hidden" id="receiverId">
                            <input type="text" id="messageInput" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                            <button class="btn-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentReceiverId = null;
        let chatInterval = null;

        function selectUser(element, id, username, role, profilePic) {
            // Update UI Selection
            document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            currentReceiverId = id;
            document.getElementById('receiverId').value = id;
            document.getElementById('noChatSelected').style.display = 'none';
            document.getElementById('activeChat').style.display = 'flex';

            document.getElementById('activeChatName').textContent = username;
            document.getElementById('activeChatRole').textContent = role;

            let avatarHtml = '';
            if (profilePic) {
                avatarHtml = `<img src="${profilePic}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">`;
            } else {
                avatarHtml = username.charAt(0).toUpperCase();
            }
            document.getElementById('activeChatAvatar').innerHTML = avatarHtml;

            // Load messages immediately
            fetchMessages();

            // Set up polling to refresh messages
            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(fetchMessages, 3000);
            
            // Focus input
            document.getElementById('messageInput').focus();
        }

        async function fetchMessages() {
            if (!currentReceiverId) return;
            
            try {
                const response = await fetch(`fetch_messages.php?receiver_id=${currentReceiverId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    const messagesContainer = document.getElementById('chatMessages');
                    // Check if scrolled to bottom before updating
                    const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 50;
                    
                    messagesContainer.innerHTML = '';
                    
                    data.messages.forEach(msg => {
                        const isSent = msg.sender_id == <?= $user_id ?>;
                        const msgClass = isSent ? 'sent' : 'received';
                        
                        const msgDiv = document.createElement('div');
                        msgDiv.className = `message ${msgClass}`;
                        msgDiv.innerHTML = `
                            <div class="bubble">${escapeHtml(msg.message_text)}</div>
                            <div class="message-time">${msg.time}</div>
                        `;
                        messagesContainer.appendChild(msgDiv);
                    });
                    
                    // Auto-scroll to bottom if it was at the bottom
                    if (isScrolledToBottom || data.messages.length > 0) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const messageText = input.value.trim();
            const receiverId = document.getElementById('receiverId').value;
            
            if (!messageText || !receiverId) return;
            
            // Clear input
            input.value = '';
            
            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `receiver_id=${receiverId}&message=${encodeURIComponent(messageText)}`
                });
                
                const data = await response.json();
                if (data.status === 'success') {
                    // Fetch instantly to show the new message
                    fetchMessages();
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error sending message:', error);
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }
    </script>
</body>

</html>
