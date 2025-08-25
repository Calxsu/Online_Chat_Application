<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Room.php';
require_once 'classes/Message.php';
require_once 'classes/Report.php';
require_once 'classes/Admin.php';

$user = new User();
$auth = new Auth();
$room = new Room();
$message = new Message();
$report = new Report();
$admin = new Admin();

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$username = $isLoggedIn ? $_SESSION['username'] : '';
$isAdmin = $isLoggedIn ? $user->isAdmin($userId) : false;

if ($isLoggedIn) {
    $user->updateLastSeen($userId);
}

if (isset($_GET['logout'])) {
    $auth->logout();
    header('Location: index.php');
    exit;
}

$alertHtml = '';
if (isset($_GET['error'])) {
    $alertHtml = '<div class="auth-alert auth-alert--error">' . htmlspecialchars($_GET['error']) . '</div>';
} elseif (isset($_GET['registered'])) {
    $alertHtml = '<div class="auth-alert auth-alert--success">Account created successfully!</div>';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp - Professional Messaging</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

    <div class="toast-container" id="toast-container"></div>

    <?php if (!$isLoggedIn): ?>
        <!-- HTML MODULE: auth_form -->
        <!-- Auth Form Include -->
        <div class="auth-wrapper">
            <div class="auth-container">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i data-lucide="message-circle" style="width: 24px; height: 24px; color: white;"></i>
                    </div>
                    <h1 class="auth-title">ChatApp</h1>
                    <p class="auth-subtitle">Messaging platform</p>
                </div>

                <div class="auth-tabs">
                    <button id="signin-tab-btn" class="auth-tab auth-tab--active">Sign In</button>
                    <button id="register-tab-btn" class="auth-tab">Register</button>
                </div>

                <div class="auth-content">
                    <?php echo $alertHtml; ?>

                    <form id="signin-form" class="auth-form" method="POST" action="handlers/auth_handler.php">
                        <input type="hidden" name="action" value="login">

                        <div class="auth-form-group">
                            <label class="auth-label">Email or Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="Enter your email or username" required>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="auth-btn auth-btn--primary">Sign In</button>
                    </form>

                    <form id="register-form" class="auth-form auth-form--hidden" method="POST" action="handlers/auth_handler.php">
                        <input type="hidden" name="action" value="register">

                        <div class="auth-form-group">
                            <label class="auth-label">Username</label>
                            <input type="text" name="username" class="auth-input" placeholder="Choose a username" required minlength="3">
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Email Address</label>
                            <input type="email" name="email" class="auth-input" placeholder="your@email.com" required>
                        </div>

                        <div class="auth-form-group">
                            <label class="auth-label">Password</label>
                            <input type="password" name="password" class="auth-input" placeholder="Create a secure password" required minlength="6">
                        </div>

                        <button type="submit" class="auth-btn auth-btn--primary">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- HTML MODULE: auth_form - END -->
    <?php else: ?>
        <!-- HTML MODULE: admin + chat + room - BEGIN -->
        <div class="app-layout">
            <header class="app-header">
                <div class="header-user-section header-left">
                    <button id="mobile-menu-btn" class="header-btn" style="display:none; margin-right:0.5rem;">
                        <i data-lucide="menu" style="width:18px; height:18px;"></i>
                    </button>
                    <div class="header-avatar"><?php echo strtoupper($username[0] ?? 'U'); ?></div>
                    <div class="header-user-info">
                        <div class="header-username"><?php echo htmlspecialchars($username); ?></div>
                        <div class="header-role"><?php echo $isAdmin ? 'Administrator' : 'Member'; ?></div>
                    </div>
                </div>

                <!-- HTML MODULE: room (sidebar) - END -->
                <div class="header-actions">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
                        <i data-lucide="sun" style="width: 18px; height: 18px;"></i>
                    </button>

                    <?php if ($isAdmin): ?>
                        <button id="header-admin-btn" class="header-btn header-btn--primary">
                            <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                            Admin Panel
                        </button>
                    <?php endif; ?>
                    <a href="?logout=1" class="header-btn header-btn--danger">
                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        Logout
                    </a>
                </div>
            </header>

            <!-- HTML MODULE: room (sidebar) - BEGIN -->
            <div class="chat-layout">
                <!-- Sidebar -->
                <div class="sidebar-container sidebar">
                    <div class="sidebar-header">
                        <button class="sidebar-create-btn">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                            Create Room
                        </button>
                    </div>

                    <div class="sidebar-content">
                        <div class="sidebar-section-header">
                            <h3 class="sidebar-section-title">Available Rooms</h3>
                            <span class="sidebar-count-badge">0</span>
                        </div>

                        <div class="sidebar-rooms-list">
                            <div class="sidebar-empty">
                                <div class="sidebar-empty-icon">
                                    <i data-lucide="message-square" style="width: 28px; height: 28px;"></i>
                                </div>
                                <p><strong>Loading rooms...</strong><br>Please wait while we fetch available rooms.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile-only footer: Admin / Theme / Logout (visible on small screens) -->
                    <div class="sidebar-mobile-footer" aria-hidden="true">
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <?php if ($isAdmin): ?>
                                <button id="mobile-admin-btn" class="header-btn" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                    <i data-lucide="shield" style="width:14px; height:14px;"></i>
                                    Admin
                                </button>
                            <?php endif; ?>
                            <button id="mobile-theme-btn" class="header-btn" title="Toggle theme" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                <i data-lucide="sun" style="width:14px; height:14px;"></i>
                                Theme
                            </button>
                        </div>

                        <a id="mobile-logout" href="?logout=1" class="header-btn header-btn--danger" style="padding:0.6rem 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                            <i data-lucide="log-out" style="width:14px; height:14px;"></i>
                            Logout
                        </a>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="chat-main-container">
                    <div id="welcome-screen" class="chat-welcome-screen">
                        <div class="chat-welcome-icon">
                            <i data-lucide="message-circle" style="width: 40px; height: 40px;"></i>
                        </div>
                        <h2 class="chat-welcome-title">Welcome to ChatApp</h2>
                        <p class="chat-welcome-subtitle">Select a room from the sidebar to start chatting, or create a new room to begin conversations.</p>
                        <button id="chat-welcome-create-btn" class="chat-welcome-button">
                            <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                            Create Room
                        </button>
                    </div>

                    <div id="chat-interface" class="chat-interface" style="display: none;">
                        <div class="chat-header">
                            <div class="chat-header-title">
                                <div class="chat-status-indicator"></div>
                                <button id="mobile-back-btn" class="header-btn" style="display:none; margin-right:0.4rem; align-items:center;">
                                    <i data-lucide="chevron-left" style="width:1rem; height:1rem;"></i>
                                </button>
                                <span id="chat-room-name" style="font-size:0.95rem;">Room Name</span>
                            </div>
                            <!-- Removed chat-header-actions div with the two buttons -->
                        </div>

                        <div id="chat-messages" class="chat-messages-area">
                            <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        </div>

                        <div class="chat-input-container">
                            <form id="chat-message-form">
                                <div class="chat-input-wrapper">
                                    <textarea id="chat-message-input" class="chat-input-field" placeholder="Type your message..." rows="1"></textarea>
                                    <div class="chat-input-actions">
                                        <input type="file" id="file-input" class="chat-file-input" accept="image/*,.pdf">
                                        <button type="button" id="file-btn" class="chat-file-btn">
                                            <i data-lucide="paperclip" style="width: 18px; height: 18px;"></i>
                                        </button>
                                        <button type="submit" class="chat-send-button">
                                            <i data-lucide="send" style="width: 18px; height: 18px;"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HTML MODULE: admin + chat + room - END -->

        <!-- Create Room Modal -->
        <div id="createRoomModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Create New Room</h3>
                    <button class="modal-close" data-close>&times;</button>
                </div>
                <form id="createRoomForm">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Room Name</label>
                        <input type="text" id="room-name" placeholder="Enter room name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;" required>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Visibility</label>
                        <select id="room-visibility" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px;">
                            <option value="public">Public - Anyone can join</option>
                            <option value="private">Private - Invitation only</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="header-btn" data-close>Cancel</button>
                        <button type="submit" class="header-btn header-btn--primary">Create Room</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <!-- This script block must remain in index.php as it contains PHP variables -->
    <script>
        // Global variables
        window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        window.currentUserId = <?php echo json_encode($userId); ?>;
        window.currentUsername = <?php echo json_encode($username); ?>;
        window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    </script>

    <!-- JavaScript Files -->
    <!-- JavaScript Files -->
    <script src="assets/js/globals.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/ui.js"></script>
    <script src="assets/js/room-persistence.js"></script>
    <script src="assets/js/room-manager.js"></script> <!-- ADD THIS LINE -->
    <script src="assets/js/jquery-bundle.js"></script>
    <script src="assets/js/chat-messages.js"></script>
    <script src="assets/js/admin-reports.js"></script>
    <script src="assets/js/init.js"></script>


</body>

</html>