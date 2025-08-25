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

    <script>
        // Global variables
        window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        window.currentUserId = <?php echo json_encode($userId); ?>;
        window.currentUsername = <?php echo json_encode($username); ?>;
        window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

        // State variables
        let currentRoomId = null;
        let currentRoomInfo = null;
        let lastMessageId = 0;
        let pollingInterval = null;
        let pollPaused = false;
        let adminPollingInterval = null;
        let roomsPollingInterval = null;
        let canSend = true;
        let displayedMessageIds = new Set();
        let lastReportCount = 0;
        let joinedRooms = new Set();
        let lastRoomsCount = 0;
        let selectedFile = null;
        let isSending = false;
        let allRooms = [];

        // Enhanced Toast notification function
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            // Split message into title and content
            const messages = message.split('. ');
            const title = messages[0];
            const content = messages.slice(1).join('. ');

            toast.innerHTML = `
                <div class="toast-icon">
                    <i data-lucide="${getToastIcon(type)}" style="width: 20px; height: 20px;"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    ${content ? `<div class="toast-message">${content}</div>` : ''}
                </div>
                <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 300);">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
            `;

            container.appendChild(toast);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (container.contains(toast)) {
                        container.removeChild(toast);
                    }
                }, 300);
            }, 5000);
        }

        // JS MODULE: auth_form
        // Theme Management
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            const themeIcon = document.querySelector('#theme-toggle i');
            if (themeIcon) {
                themeIcon.setAttribute('data-lucide', theme === 'light' ? 'moon' : 'sun');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
            showToast(`Switched to ${newTheme} theme`, 'info');
        }

        // Helper functions
        function getToastIcon(type) {
            switch (type) {
                case 'success':
                    return 'check-circle';
                case 'error':
                    return 'x-circle';
                case 'warning':
                    return 'alert-triangle';
                default:
                    return 'info';
            }
        }

        function escapeHtml(text) {
            if (!text || text === null || text === undefined) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ENHANCED ROOM PERSISTENCE - Works after logout/login
        function saveJoinedRooms() {
            if (window.currentUserId) {
                const joinedArray = [...joinedRooms];
                const storageKey = `chatapp_joined_rooms_${window.currentUserId}`;
                localStorage.setItem(storageKey, JSON.stringify({
                    userId: window.currentUserId,
                    username: window.currentUsername,
                    rooms: joinedArray,
                    timestamp: Date.now()
                }));
                console.log('Saved joined rooms for user', window.currentUserId, ':', joinedArray);
            }
        }

        function loadJoinedRooms() {
            if (window.currentUserId) {
                const storageKey = `chatapp_joined_rooms_${window.currentUserId}`;
                const stored = localStorage.getItem(storageKey);

                if (stored) {
                    try {
                        const data = JSON.parse(stored);

                        // Verify this data belongs to current user
                        if (data.userId == window.currentUserId) {
                            joinedRooms = new Set(data.rooms || []);
                            console.log('Loaded joined rooms for user', window.currentUserId, ':', [...joinedRooms]);
                        } else {
                            console.log('Room data belongs to different user, clearing...');
                            joinedRooms = new Set();
                        }
                    } catch (e) {
                        console.error('Error parsing joinedRooms:', e);
                        joinedRooms = new Set();
                    }
                } else {
                    joinedRooms = new Set();
                }
            }

            // Auto-add user to rooms they created
            if (allRooms.length > 0) {
                let addedCreatorRooms = false;
                allRooms.forEach(room => {
                    if (room.created_by == window.currentUserId && !joinedRooms.has(room.id.toString())) {
                        joinedRooms.add(room.id.toString());
                        addedCreatorRooms = true;
                        console.log('Auto-joined creator room:', room.name);
                    }
                });

                if (addedCreatorRooms) {
                    saveJoinedRooms();
                }
            }
        }

        // Room list update function (moved to global scope)
        function updateRoomsList(rooms) {
            const container = document.querySelector('.sidebar-rooms-list');
            if (!container) return;

            if (rooms.length === 0) {
                container.innerHTML = `
                    <div class="sidebar-empty">
                        <div class="sidebar-empty-icon">
                            <i data-lucide="message-square" style="width: 28px; height: 28px;"></i>
                        </div>
                        <p><strong>No rooms yet!</strong><br>Create your first room to start chatting with others.</p>
                    </div>
                `;
            } else {
                // Render rooms using data attributes instead of inline onclick handlers
                container.innerHTML = rooms.map(room => {
                    const isJoined = joinedRooms.has(room.id.toString());
                    const isCreator = room.created_by == window.currentUserId;
                    const isBanned = room.is_banned == 1 || room.is_banned === true;

                    const roomNameEscaped = escapeHtml(room.name);

                    // Show banned badge prominently with consistent size
                    const bannedBadge = isBanned ? `<span class="sidebar-room-badge" style="background: var(--error); color: white; font-weight: 700; height: 26px; padding: 0.35rem 0.65rem; font-size: 0.75rem;"><i data-lucide="ban" style="width: 12px; height: 12px;"></i>BANNED</span>` : '';
                    const creatorBadge = isCreator && !isBanned ? `<span class="sidebar-room-badge sidebar-room-badge--creator"><i data-lucide="crown" style="width: 12px; height: 12px;"></i>Creator</span>` : '';

                    // Don't show join/leave buttons for banned rooms
                    const joinLeaveBtn = isBanned ? '' : (isCreator ? '' : (isJoined ?
                        `<button class="sidebar-room-btn sidebar-room-btn--leave" data-action="leave" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Leave Room">Leave</button>` :
                        `<button class="sidebar-room-btn sidebar-room-btn--join" data-action="join" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Join Room">Join</button>`
                    ));

                    // Don't allow reporting already banned rooms
                    const reportBtn = (!isCreator && !isBanned) ? `<button class="sidebar-room-btn sidebar-room-btn--report" data-action="report" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Report Room"><i data-lucide="flag" style="width: 12px; height: 12px;"></i></button>` : '';

                    // No invites for banned rooms
                    const inviteBtn = (isCreator && room.visibility === 'private' && !isBanned) ? `<button class="sidebar-room-btn sidebar-room-btn--invite" data-action="invite" data-room-id="${room.id}" data-room-name="${roomNameEscaped}" title="Invite Users">Invite</button>` : '';

                    return `
                        <div class="sidebar-room ${isJoined ? 'sidebar-room--joined' : ''} ${isBanned ? 'sidebar-room--banned' : ''}" 
                             data-room-id="${room.id}" 
                             data-room-name="${roomNameEscaped}"
                             data-banned="${isBanned ? '1' : '0'}">
                            <div class="sidebar-room-header">
                                <div class="sidebar-room-name" style="${isBanned ? 'text-decoration: line-through; opacity: 0.7;' : ''}">${roomNameEscaped}</div>
                                <div class="sidebar-room-actions">
                                    ${bannedBadge}
                                    ${creatorBadge}
                                    ${joinLeaveBtn}
                                    ${reportBtn}
                                    ${inviteBtn}
                                </div>
                            </div>
                            <div class="sidebar-room-details">
                                <span class="sidebar-room-badge ${room.visibility === 'private' ? 'sidebar-room-badge--private' : ''}">
                                    <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 12px; height: 12px;"></i>
                                    ${room.visibility.charAt(0).toUpperCase() + room.visibility.slice(1)}
                                </span>
                                <span>by ${escapeHtml(room.creator_name)}</span>
                                ${isJoined && !isCreator && !isBanned ? '<span style="color: var(--success); font-weight: 500;"><i data-lucide="check" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>Joined</span>' : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                // Attach event listeners for actions
                setTimeout(() => {
                    // Join buttons
                    container.querySelectorAll('button[data-action="join"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            joinRoom(id, name);
                        });
                    });

                    // Leave buttons
                    container.querySelectorAll('button[data-action="leave"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            leaveRoom(id, name);
                        });
                    });

                    // Report buttons
                    container.querySelectorAll('button[data-action="report"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            reportRoom(id, name);
                        });
                    });

                    // Invite buttons
                    container.querySelectorAll('button[data-action="invite"]').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const id = btn.getAttribute('data-room-id');
                            const name = btn.getAttribute('data-room-name');
                            openInviteModal(id, name);
                        });
                    });
                }, 0);
            }

            // Update room count badge
            const countBadge = document.querySelector('.sidebar-count-badge');
            if (countBadge) {
                countBadge.textContent = rooms.length;
            }

            // Re-render lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // JS MODULE: room
        // Enhanced room management
        function loadRooms() {
            $.ajax({
                url: 'handlers/room_handler.php?action=get_rooms',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Rooms loaded:', response);
                    if (Array.isArray(response)) {
                        allRooms = response;
                        loadJoinedRooms();
                        updateRoomsList(response);
                    } else {
                        console.error('Invalid rooms response:', response);
                        showToast('Failed to load rooms. Invalid response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load rooms:', error);
                    showToast('Failed to load rooms', 'error');
                }
            });
        }

        // Join/Leave Room Functions with Enhanced Persistence
        function joinRoom(roomId, roomName) {
            console.log('Joining room:', roomId, roomName);

            joinedRooms.add(roomId.toString());
            saveJoinedRooms();

            showToast(`Joined "${roomName}"`, 'success');
            loadRooms();

            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }

            console.log('Successfully joined room:', roomName);
        }

        function leaveRoom(roomId, roomName) {
            const room = allRooms.find(r => r.id == roomId);
            if (room && room.created_by == window.currentUserId) {
                showToast('Room creators cannot leave their own rooms', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to leave "${roomName}"?`)) return;

            console.log('Leaving room:', roomId, roomName);

            joinedRooms.delete(roomId.toString());
            saveJoinedRooms();

            if (currentRoomId == roomId) {
                document.getElementById('chat-interface').style.display = 'none';
                document.getElementById('welcome-screen').style.display = 'flex';
                currentRoomId = null;
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            showToast(`Left "${roomName}"`, 'info');
            loadRooms();

            console.log('Successfully left room:', roomName);
        }

        function createRoom() {
            const name = document.getElementById('room-name').value.trim();
            const visibility = document.getElementById('room-visibility').value;

            if (!name) {
                showToast('Please enter a room name', 'warning');
                return;
            }

            const createBtn = document.querySelector('#createRoomModal .header-btn--primary');
            const originalText = createBtn.textContent;
            createBtn.disabled = true;
            createBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Creating...';

            $.ajax({
                url: 'handlers/room_handler.php',
                method: 'POST',
                data: {
                    action: 'create',
                    name: name,
                    visibility: visibility
                },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    console.log('Create room response:', response);

                    if (response && response.success === true) {
                        showToast(`Room "${name}" created successfully!`, 'success');
                        document.getElementById('createRoomModal').classList.remove('show');
                        document.getElementById('createRoomForm').reset();

                        if (response.room_id) {
                            const roomId = response.room_id.toString();
                            joinedRooms.add(roomId);
                            saveJoinedRooms();

                            setTimeout(() => {
                                showToast(`You've been automatically added to "${name}"`, 'info');
                            }, 1000);
                        }

                        setTimeout(() => {
                            loadRooms();
                        }, 500);
                    } else {
                        const errorMsg = response?.error || 'Failed to create room';
                        console.error('Room creation failed:', errorMsg);

                        // Check for specific ban error
                        if (errorMsg.includes('banned') || errorMsg.includes('Banned')) {
                            showToast('You are banned and cannot create rooms', 'error');
                            // Close the modal
                            document.getElementById('createRoomModal').classList.remove('show');
                        } else {
                            showToast(errorMsg, 'error');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Create room AJAX error:', {
                        status,
                        error,
                        responseText: xhr.responseText
                    });

                    let errorMessage = 'Failed to create room';
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again.';
                    }

                    showToast(errorMessage, 'error');
                },
                complete: function() {
                    createBtn.disabled = false;
                    createBtn.textContent = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
        }

        // Invite modal for private rooms
        function openInviteModal(roomId, roomName) {
            currentRoomId = roomId;
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'inviteModal';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">Invite to ${escapeHtml(roomName)}</h3>
                        <button class="modal-close" onclick="closeInviteModal()">&times;</button>
                    </div>
                    <div style="padding: 1rem;">
                        <label style="display:block; margin-bottom:0.5rem; font-weight:600;">Username to invite</label>
                        <input id="invite-username-input" type="text" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:6px;" placeholder="Enter username...">
                        <div style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
                            <button class="header-btn" onclick="closeInviteModal()">Cancel</button>
                            <button class="header-btn header-btn--primary" onclick="submitInvite()">Send Invite</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.getElementById('invite-username-input').focus();
        }

        function closeInviteModal() {
            const m = document.getElementById('inviteModal');
            if (m) m.remove();
        }

        function submitInvite() {
            const input = document.getElementById('invite-username-input');
            if (!input) return;
            const username = input.value.trim();
            if (!username) {
                showToast('Please enter a username', 'warning');
                return;
            }

            $.post('handlers/room_handler.php', {
                action: 'invite',
                room_id: currentRoomId,
                username: username
            }, function(response) {
                if (response && response.success) {
                    showToast('User invited successfully', 'success');
                    closeInviteModal();
                    setTimeout(loadRooms, 500);
                } else {
                    showToast(response.error || 'Failed to invite user', 'error');
                }
            }, 'json').fail(function() {
                showToast('Failed to invite user', 'error');
            });
        }

        function selectRoom(roomId, roomName) {
            console.log('Selecting room:', roomId, roomName);

            const room = allRooms.find(r => r.id == roomId);
            const isCreator = room && room.created_by == window.currentUserId;
            const isJoined = joinedRooms.has(roomId.toString());
            const isBanned = room && (room.is_banned == 1 || room.is_banned === true);

            // COMPLETELY BLOCK ACCESS TO BANNED ROOMS - NO ONE CAN ACCESS
            if (isBanned) {
                showToast('This room is banned. No one can access it anymore.', 'error');
                return; // Stop here - don't allow entering the room
            }

            // Check if user has access
            if (!isCreator && !isJoined) {
                showToast('Please join this room first', 'warning');
                return;
            }

            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }

            currentRoomId = roomId;
            currentRoomInfo = room;

            document.querySelectorAll('.sidebar-room').forEach(room => {
                room.classList.remove('sidebar-room--active');
            });

            const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
            if (selectedRoom) {
                selectedRoom.classList.add('sidebar-room--active');
            }

            document.getElementById('welcome-screen').style.display = 'none';
            document.getElementById('chat-interface').style.display = 'flex';
            const chatRoomNameEl = document.getElementById('chat-room-name');
            if (chatRoomNameEl) {
                chatRoomNameEl.textContent = roomName;
            }

            // Enable chat input normally (since banned rooms can't be accessed at all now)
            const messageInput = document.getElementById('chat-message-input');
            const sendBtn = document.querySelector('.chat-send-button');
            const fileBtn = document.getElementById('file-btn');
            const inputWrapper = document.querySelector('.chat-input-wrapper');

            if (messageInput) {
                messageInput.disabled = false;
                messageInput.placeholder = 'Type your message...';
                messageInput.style.opacity = '1';
            }
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.style.cursor = 'pointer';
            }
            if (fileBtn) {
                fileBtn.disabled = false;
                fileBtn.style.opacity = '1';
                fileBtn.style.cursor = 'pointer';
            }
            if (inputWrapper) {
                inputWrapper.style.background = '';
                inputWrapper.style.borderColor = '';
            }

            if (window.innerWidth <= 768) {
                const mobileBackBtn = document.getElementById('mobile-back-btn');
                if (mobileBackBtn) mobileBackBtn.style.display = 'inline-flex';
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
            }

            loadMessages(roomId);
            startMessagePolling(roomId);
        }

        // ENHANCED MOBILE FUNCTIONALITY
        function initializeMobileFeatures() {
            console.log('Initializing mobile features...');
            if (!document.querySelector('.sidebar-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.addEventListener('click', closeMobileSidebar);
                document.body.appendChild(overlay);

                console.log('Sidebar overlay added');
            }
        }

        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const isOpen = sidebar.classList.contains('show');

            console.log(isOpen ? 'Closing' : 'Opening', 'mobile sidebar');

            if (isOpen) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
            } else {
                sidebar.classList.add('show');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
                const mobileFooter = document.querySelector('.sidebar-mobile-footer');
                if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'false');
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            const mobileFooter = document.querySelector('.sidebar-mobile-footer');
            if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
        }

        // JS MODULE: chat
        // File Upload Functions
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File too large (max 5MB)', 'error');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Invalid file type. Only JPEG, PNG, GIF, and PDF allowed', 'error');
                    return;
                }

                selectedFile = file;
                showFilePreview(file);
            }
        }

        function showFilePreview(file) {
            const preview = document.createElement('div');
            preview.className = 'file-preview';
            preview.innerHTML = `
                <i data-lucide="paperclip" style="width: 16px; height: 16px;"></i>
                <span>${file.name} (${formatFileSize(file.size)})</span>
                <button class="file-preview-remove" onclick="removeFilePreview()">
                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                </button>
            `;

            const inputContainer = document.querySelector('.chat-input-container');
            if (inputContainer) {
                const existingPreview = inputContainer.querySelector('.file-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }

                inputContainer.insertBefore(preview, inputContainer.firstChild);
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function removeFilePreview() {
            selectedFile = null;
            const preview = document.querySelector('.file-preview');
            if (preview) {
                preview.remove();
            }
            const fileInput = document.getElementById('file-input');
            if (fileInput) {
                fileInput.value = '';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Message Functions
        function loadMessages(roomId) {
            $.get('handlers/message_handler.php', {
                    action: 'get',
                    room_id: roomId,
                    limit: 50
                })
                .done(function(messages) {
                    displayMessages(messages);
                    if (messages.length > 0) {
                        lastMessageId = Math.max(...messages.map(m => m.id));
                    }
                })
                .fail(function() {
                    showToast('Failed to load messages', 'error');
                });
        }

        function displayMessages(messages) {
            const container = document.getElementById('chat-messages');
            if (!container) return;

            // Filter out system messages or check for real content
            const realMessages = messages.filter(m => !m.is_system);

            if (!messages || messages.length === 0 || realMessages.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                        <i data-lucide="message-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
            } else {
                // Reverse messages for correct chronological order (oldest first)
                const reversedMessages = [...messages].reverse();
                container.innerHTML = reversedMessages.map(message => createMessageHTML(message)).join('');
                displayedMessageIds.clear();
                messages.forEach(m => {
                    if (m && m.id !== undefined && m.id !== null) displayedMessageIds.add(m.id);
                });

                if (messages.length > 0) {
                    lastMessageId = Math.max(...messages.map(m => m.id));
                }

                scrollToBottom();
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function createMessageHTML(message) {
            if (message.is_deleted) {
                const isOwnDeleted = message.user_id == window.currentUserId;
                const ownClass = isOwnDeleted ? ' message--own' : '';
                return `
                    <div class="message message-deleted${ownClass}" data-message-id="${message.id}" data-user-id="${message.user_id || ''}" tabindex="0">
                        <div class="message-content" style="opacity: 0.6; font-style: italic; color: var(--gray-500);">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 0.5rem;"></i>
                            <em>Message deleted</em>
                        </div>
                        <div class="message-meta">
                            <div class="message-info">
                                <span class="message-author">${escapeHtml(message.username || '')}</span>
                                <span class="message-time">${message.created_at || ''}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            const isOwn = message.user_id == window.currentUserId;

            return `
                <div class="message ${isOwn ? 'message--own' : ''}" data-message-id="${message.id}" data-user-id="${message.user_id || ''}" tabindex="0">
                    <div class="message-content">
                        ${message.message ? escapeHtml(message.message) : ''}
                        ${message.attachment ? createAttachmentHTML(message.attachment) : ''}
                    </div>
                    <div class="message-meta">
                        <div class="message-info">
                            <span class="message-author">${escapeHtml(message.username)}</span>
                            <span class="message-time">${message.created_at}</span>
                        </div>
                        <div class="message-actions">
                            ${!isOwn ? `<button class="message-action-btn" onclick="reportMessage(${message.id}, '${escapeHtml(message.username).replace(/'/g, '\\\'')}')" title="Report message">
                                <i data-lucide="flag" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                            ${!isOwn ? `<button class="message-action-btn" onclick="reportUser(${message.user_id}, '${escapeHtml(message.username).replace(/'/g, '\\\'')}')" title="Report user">
                                <i data-lucide="user-x" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                            ${isOwn || window.isAdmin ? `<button class="message-action-btn message-delete" onclick="deleteMessage(${message.id})" title="Delete message">
                                <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                            </button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        function createAttachmentHTML(attachment) {
            const ext = attachment.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                return `
                    <div class="message-attachment">
                        <img src="uploads/${attachment}" alt="Attachment" onclick="showImageModal('uploads/${attachment}', '${attachment}')">
                    </div>
                `;
            }
            return `
                <div class="message-attachment">
                    <a href="uploads/${attachment}" target="_blank" style="display: flex; align-items: center; gap: 0.5rem; color: var(--accent); text-decoration: none;">
                        <i data-lucide="paperclip" style="width: 16px; height: 16px;"></i>
                        ${attachment}
                    </a>
                </div>
            `;
        }

        function sendMessage() {
            const input = document.getElementById('chat-message-input');
            const sendBtn = document.querySelector('.chat-send-button');
            const message = input ? input.value.trim() : '';

            // Check if room is banned (double-check before sending)
            if (currentRoomInfo && (currentRoomInfo.is_banned == 1 || currentRoomInfo.is_banned === true)) {
                showToast('Cannot send messages in a banned room', 'error');
                return;
            }

            if ((!message && !selectedFile) || !currentRoomId || isSending) return;

            isSending = true;
            const originalIcon = sendBtn ? sendBtn.innerHTML : '';
            if (sendBtn) {
                sendBtn.innerHTML = '<i data-lucide="loader-2" style="width: 18px; height: 18px; animation: spin 1s linear infinite;"></i>';
                sendBtn.classList.add('chat-send-button--loading');
                sendBtn.disabled = true;
            }

            const tempId = 'temp-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
            const container = document.getElementById('chat-messages');

            // Remove "no messages" placeholder if it exists
            const placeholder = container.querySelector('div[style*="text-align: center"]');
            if (placeholder) {
                placeholder.remove();
            }

            try {
                const isOwn = true;
                const optimisticHTML = `
                    <div class="message message--own message-optimistic" data-temp-id="${tempId}" data-user-id="${window.currentUserId || ''}" tabindex="0">
                        <div class="message-content">
                            ${escapeHtml(message || (selectedFile ? '[Attachment]' : ''))}
                        </div>
                        <div class="message-meta">
                            <div class="message-info">
                                <span class="message-author">${escapeHtml(window.currentUsername || '')}</span>
                                <span class="message-time">Sending...</span>
                            </div>
                            <div style="padding-left:0.5rem; color:var(--gray-400); font-size:0.8rem;">Sending</div>
                        </div>
                    </div>
                `;
                if (container) {
                    container.insertAdjacentHTML('beforeend', optimisticHTML);
                    scrollToBottom();
                }

                displayedMessageIds.add(tempId);
                try {
                    const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                    if (optEl) {
                        const tempContent = message || (selectedFile ? ('__file__:' + (selectedFile.name || 'attachment')) : '');
                        optEl.setAttribute('data-temp-content', tempContent);
                    }
                } catch (e) {
                    console.warn('Could not tag optimistic element', e);
                }

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollPaused = true;
                }
            } catch (e) {
                console.warn('Failed to render optimistic message', e);
            }

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('room_id', currentRoomId);
            if (message) formData.append('message', message);
            if (selectedFile) formData.append('attachment', selectedFile);

            $.ajax({
                url: 'handlers/message_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        let saved = null;
                        if (response.message) {
                            saved = response.message;
                        } else if (response.message_id) {
                            saved = {
                                id: response.message_id,
                                message: message,
                                username: window.currentUsername,
                                user_id: window.currentUserId,
                                created_at: new Date().toLocaleString(),
                                attachment: response.attachment || null
                            };
                        }

                        if (saved) {
                            const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                            if (optEl) {
                                optEl.outerHTML = createMessageHTML(saved);
                            } else if (container) {
                                if (!displayedMessageIds.has(saved.id)) {
                                    container.insertAdjacentHTML('beforeend', createMessageHTML(saved));
                                }
                            }

                            if (displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                            if (saved.id !== undefined && saved.id !== null) {
                                displayedMessageIds.add(saved.id);
                                lastMessageId = Math.max(lastMessageId, saved.id);
                            }
                        }

                        if (input) {
                            input.value = '';
                            input.style.height = 'auto';
                        }
                        removeFilePreview();
                        showToast('Message sent successfully!', 'success');
                    } else {
                        const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                        if (optEl) {
                            optEl.classList.add('message-send-failed');
                            const meta = optEl.querySelector('.message-meta');
                            if (meta) meta.insertAdjacentHTML('beforeend', `<div style="color:var(--error); padding-left:0.5rem;">Failed</div>`);
                        }
                        showToast(response.error || 'Failed to send message', 'error');
                    }
                },
                error: function() {
                    const optEl = document.querySelector(`[data-temp-id="${tempId}"]`);
                    if (optEl) {
                        optEl.classList.add('message-send-failed');
                        const meta = optEl.querySelector('.message-meta');
                        if (meta) meta.insertAdjacentHTML('beforeend', `<div style="color:var(--error); padding-left:0.5rem;">Failed</div>`);
                    }
                    showToast('Failed to send message', 'error');
                },
                complete: function() {
                    isSending = false;
                    if (sendBtn) {
                        sendBtn.innerHTML = originalIcon;
                        sendBtn.classList.remove('chat-send-button--loading');
                        sendBtn.disabled = false;
                    }

                    setTimeout(function() {
                        if (pollPaused) {
                            pollPaused = false;
                            if (currentRoomId) {
                                startMessagePolling(currentRoomId);
                            }
                        }
                    }, 700);

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
            });
        }

        function matchAndReplaceOptimistic(serverMsg) {
            try {
                if (!serverMsg) return false;
                const container = document.getElementById('chat-messages');
                if (!container) return false;

                const possibleTempContent = serverMsg.message || (serverMsg.attachment ? ('__file__:' + serverMsg.attachment) : '');
                if (!possibleTempContent) return false;

                const optimisticEls = container.querySelectorAll('.message-optimistic');
                for (let el of optimisticEls) {
                    const tempContent = el.getAttribute('data-temp-content');
                    if (!tempContent) continue;
                    if (tempContent === possibleTempContent) {
                        el.outerHTML = createMessageHTML(serverMsg);
                        const tempId = el.getAttribute('data-temp-id');
                        if (tempId && displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                        if (serverMsg.id !== undefined && serverMsg.id !== null) displayedMessageIds.add(serverMsg.id);
                        return true;
                    }
                }

                for (let el of optimisticEls) {
                    const ownerId = el.getAttribute('data-user-id') || el.getAttribute('data-userid') || null;
                    if (ownerId && ownerId.toString() === (window.currentUserId || '').toString()) {
                        el.outerHTML = createMessageHTML(serverMsg);
                        const tempId = el.getAttribute('data-temp-id');
                        if (tempId && displayedMessageIds.has(tempId)) displayedMessageIds.delete(tempId);
                        if (serverMsg.id !== undefined && serverMsg.id !== null) displayedMessageIds.add(serverMsg.id);
                        return true;
                    }
                }
            } catch (e) {
                console.warn('matchAndReplaceOptimistic failed', e);
            }
            return false;
        }

        function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;

            $.post('handlers/message_handler.php', {
                    action: 'delete',
                    message_id: messageId
                })
                .done(function(response) {
                    if (response.success) {
                        markMessageDeleted(messageId, response.username || null, response.deleted_at || null);
                        showToast('Message deleted', 'success');
                    } else {
                        showToast(response.error || 'Failed to delete message', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to delete message', 'error');
                });
        }

        function markMessageDeleted(messageId, username = null, deletedAt = null) {
            const selector = `[data-message-id="${messageId}"]`;
            const el = document.querySelector(selector);
            const timestamp = deletedAt || new Date().toLocaleString();

            let isOwn = false;
            let ownerId = null;
            if (el) {
                if (el.dataset && el.dataset.userId) ownerId = el.dataset.userId;
                else ownerId = el.getAttribute && el.getAttribute('data-user-id');

                if (ownerId) {
                    isOwn = ownerId == window.currentUserId;
                } else {
                    isOwn = el.classList && el.classList.contains('message--own') || (username && username === window.currentUsername);
                }
            } else {
                if (username && username === window.currentUsername) {
                    isOwn = true;
                    ownerId = window.currentUserId;
                }
            }

            const ownClass = isOwn ? ' message--own' : '';
            const dataUserIdAttr = ownerId ? ` data-user-id="${ownerId}"` : '';

            const deletedHTML = `
                <div class="message message-deleted${ownClass}" data-message-id="${messageId}"${dataUserIdAttr} tabindex="0">
                    <div class="message-content" style="opacity: 0.6; font-style: italic; color: var(--gray-500);">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px; margin-right: 0.5rem;"></i>
                        <em>Message deleted</em>
                    </div>
                    <div class="message-meta">
                        <div class="message-info">
                            <span class="message-author">${escapeHtml(username || '')}</span>
                            <span class="message-time">${timestamp}</span>
                        </div>
                    </div>
                </div>
            `;

            if (el) {
                el.outerHTML = deletedHTML;
            } else {
                const container = document.getElementById('chat-messages');
                if (container) container.insertAdjacentHTML('beforeend', deletedHTML);
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function startMessagePolling(roomId) {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            pollingInterval = setInterval(() => {
                if (pollPaused) return;
                if (currentRoomId === roomId) {
                    // Check if room is banned (stop polling if banned)
                    if (currentRoomInfo && (currentRoomInfo.is_banned == 1 || currentRoomInfo.is_banned === true)) {
                        clearInterval(pollingInterval);
                        pollPaused = true;
                        return;
                    }

                    $.get('handlers/message_handler.php', {
                            action: 'poll',
                            room_id: roomId,
                            last_id: lastMessageId
                        })
                        .done(function(newMessages) {
                            if (newMessages && newMessages.length > 0) {
                                appendNewMessages(newMessages);
                                const numericIds = newMessages.map(m => m.id).filter(i => i !== undefined && i !== null && !isNaN(i));
                                if (numericIds.length) {
                                    lastMessageId = Math.max(lastMessageId, ...numericIds);
                                }
                            }
                        })
                        .fail(function(xhr, status, error) {
                            // Log polling errors for debugging (remove or comment out in production)
                            console.error('Polling error:', { status, error, responseText: xhr && xhr.responseText });
                        });
                }
            }, 3000);
        }

        function appendNewMessages(messages) {
            const container = document.getElementById('chat-messages');
            if (container) {
                // Remove "no messages" placeholder if it exists
                const placeholder = container.querySelector('div[style*="text-align: center"]');
                if (placeholder) {
                    placeholder.remove();
                }

                let appended = false;
                messages.forEach(message => {
                    if (message && message.id !== undefined && displayedMessageIds.has(message.id)) return;

                    const replaced = matchAndReplaceOptimistic(message);
                    if (replaced) {
                        appended = true;
                        return;
                    }

                    const messageHTML = createMessageHTML(message);
                    container.insertAdjacentHTML('beforeend', messageHTML);
                    appended = true;

                    if (message && message.id !== undefined) displayedMessageIds.add(message.id);
                });

                if (appended) scrollToBottom();

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        function scrollToBottom() {
            const container = document.getElementById('chat-messages');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function showImageModal(src, filename) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">${escapeHtml(filename)}</h3>
                        <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <div style="text-align: center; padding: 1rem;">
                        <img src="${src}" alt="${escapeHtml(filename)}" style="max-width: 100%; max-height: 60vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // JS MODULE: admin
        // Report Functions
        function showReportModal(type, entityId, entityName) {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'reportModal';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i data-lucide="flag" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                            Report ${type.charAt(0).toUpperCase() + type.slice(1)}
                        </h3>
                        <button class="modal-close" onclick="closeReportModal()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <form id="reportForm">
                        <div style="margin-bottom: 1rem;">
                            <p style="color: var(--gray-600); margin-bottom: 1rem;">
                                You are reporting: <strong>${escapeHtml(entityName)}</strong>
                            </p>
                            
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Report</label>
                            <select id="report-reason" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; margin-bottom: 1rem;" required>
                                <option value="">Select a reason...</option>
                                <option value="spam">Spam</option>
                                <option value="harassment">Harassment</option>
                                <option value="inappropriate_content">Inappropriate Content</option>
                                <option value="hate_speech">Hate Speech</option>
                                <option value="impersonation">Impersonation</option>
                                <option value="violence">Violence or Threats</option>
                                <option value="misinformation">Misinformation</option>
                                <option value="copyright">Copyright Violation</option>
                                <option value="other">Other</option>
                            </select>
                            
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Additional Details (Optional)</label>
                            <textarea id="report-details" placeholder="Provide additional context..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; min-height: 100px; resize: vertical;"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                            <button type="button" class="header-btn" onclick="closeReportModal()">Cancel</button>
                            <button type="submit" class="header-btn header-btn--danger">
                                <i data-lucide="flag" style="width: 14px; height: 14px;"></i>
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(modal);

            document.getElementById('reportForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const reason = document.getElementById('report-reason').value;
                const details = document.getElementById('report-details').value.trim();

                if (!reason) {
                    showToast('Please select a reason for the report', 'warning');
                    return;
                }

                const fullReason = details ? `${reason}: ${details}` : reason;
                submitReport(type, entityId, fullReason);
            });

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closeReportModal() {
            const modal = document.getElementById('reportModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                }, 300);
            }
        }

        function submitReport(type, entityId, reason) {
            const submitBtn = document.querySelector('#reportForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Submitting...';

            $.post('handlers/report_handler.php', {
                    action: 'create',
                    type: type,
                    entity_id: entityId,
                    reason: reason
                })
                .done(function(response) {
                    if (response.success) {
                        showToast('Report submitted successfully. Thank you for helping keep our community safe!', 'success');
                        closeReportModal();
                    } else {
                        showToast(response.error || 'Failed to submit report', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to submit report. Please try again.', 'error');
                })
                .always(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function reportMessage(messageId, username) {
            showReportModal('message', messageId, `Message by ${username}`);
        }

        function reportUser(userId, username) {
            showReportModal('user', userId, `User: ${username}`);
        }

        function reportRoom(roomId, roomName) {
            showReportModal('room', roomId, `Room: ${roomName}`);
        }

        // Admin Panel Implementation
        function showAdminPanel() {
            if (!window.isAdmin) {
                showToast('Access denied - Admin only', 'error');
                return;
            }

            const modal = document.createElement('div');
            modal.className = 'modal-overlay show';
            modal.id = 'adminModal';
            modal.innerHTML = `
                <div class="modal" style="max-width: 900px; max-height: 80vh;">
                    <div class="modal-header">
                        <h3 class="modal-title">
                            <i data-lucide="shield" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                            Admin Panel
                        </h3>
                        <button class="modal-close" onclick="closeAdminModal()">
                            <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                        </button>
                    </div>
                    <div style="max-height: 70vh; overflow-y: auto;">
                        <div style="border-bottom: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                            <div style="display: flex; gap: 1rem; padding-bottom: 1rem;">
                                <button id="admin-tab-reports" class="header-btn header-btn--primary admin-tab-btn" onclick="showAdminReports()">
                                    <i data-lucide="flag" style="width: 16px; height: 16px;"></i>
                                    Reports
                                </button>
                                <button id="admin-tab-users" class="header-btn admin-tab-btn" onclick="showAdminUsers()">
                                    <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                                    Users
                                </button>
                                <button id="admin-tab-rooms" class="header-btn admin-tab-btn" onclick="showAdminRooms()">
                                    <i data-lucide="message-square" style="width: 16px; height: 16px;"></i>
                                    Rooms
                                </button>
                            </div>
                        </div>
                        <div id="admin-content">
                            <div id="admin-loading" style="text-align: center; padding: 3rem;">
                                <i data-lucide="loader-2" style="width: 32px; height: 32px; animation: spin 1s linear infinite; margin-bottom: 1rem;"></i>
                                <p>Loading admin data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            showAdminReports();

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function showAdminReports() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-reports').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading reports...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_reports')
                .done(function(reports) {
                    const content = document.getElementById('admin-content');
                    if (reports.length === 0) {
                        content.innerHTML = `
                            <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                <i data-lucide="check-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; color: var(--success);"></i>
                                <p><strong>No pending reports</strong><br>All reports have been processed.</p>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `
                            <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px; border-left: 4px solid var(--warning);">
                                <p style="color: var(--gray-700); font-size: 0.875rem; margin: 0;">
                                    <i data-lucide="alert-triangle" style="width: 16px; height: 16px; margin-right: 0.5rem; color: var(--warning);"></i>
                                    <strong style="color: var(--warning);">${reports.length}</strong> pending report${reports.length !== 1 ? 's' : ''} require your attention
                                </p>
                            </div>
                            ${reports.map(report => `
                                <div style="border: 1px solid var(--gray-200); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; background: var(--error); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i data-lucide="${getReportIcon(report.report_type)}" style="width: 20px; height: 20px;"></i>
                                            </div>
                                            <div>
                                                <h4 style="margin: 0; color: var(--gray-900); font-size: 1.125rem;">
                                                    ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)} Report
                                                </h4>
                                                <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
                                                    Reported ${formatReportDate(report.created_at)} by ${escapeHtml(report.reporter_name || 'Unknown')}
                                                </p>
                                            </div>
                                        </div>
                                        <span style="background: var(--error); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                            PENDING
                                        </span>
                                    </div>

                                    <div style="background: var(--gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                        <div style="margin-bottom: 1rem;">
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="target" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reported Entity
                                            </label>
                                            <p style="margin: 0; color: var(--gray-900); font-weight: 500;">${escapeHtml(report.entity_name || 'Unknown')}</p>
                                        </div>
                                        
                                        <div style="margin-bottom: ${report.message_content ? '1rem' : '0'};">
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="message-circle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reason for Report
                                            </label>
                                            <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: 6px; padding: 0.75rem;">
                                                <p style="margin: 0; color: var(--gray-900); font-weight: 500; line-height: 1.5;">
                                                    "${escapeHtml(report.reason)}"
                                                </p>
                                            </div>
                                        </div>

                                        ${report.message_content ? `
                                        <div>
                                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                                <i data-lucide="message-square" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                Reported Message ${report.room_name ? `(in "${escapeHtml(report.room_name)}")` : ''}
                                            </label>
                                            <div style="background: var(--white); border: 1px solid var(--gray-300); border-left: 4px solid var(--warning); border-radius: 6px; padding: 0.75rem;">
                                                <p style="margin: 0; color: var(--gray-800); font-style: italic; line-height: 1.5;">
                                                    "${escapeHtml(report.message_content)}"
                                                </p>
                                                ${report.message_date ? `
                                                <p style="margin: 0.5rem 0 0 0; color: var(--gray-500); font-size: 0.8125rem;">
                                                    <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                                    Sent: ${new Date(report.message_date).toLocaleString()}
                                                </p>
                                                ` : ''}
                                            </div>
                                        </div>
                                        ` : ''}
                                    </div>

                                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                                        <button class="header-btn header-btn--danger" onclick="processReport(${report.id}, 'ban')" style="background: var(--error); color: white; border-color: var(--error);">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)}
                                        </button>
                                        ${report.report_type === 'user' ? `
                                        <button class="header-btn" onclick="processReport(${report.id}, 'warning')" style="background: var(--warning); color: white; border-color: var(--warning);">
                                            <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                                            Warn User
                                        </button>
                                        ` : ''}
                                        <button class="header-btn" onclick="processReport(${report.id}, 'reject')" style="background: var(--gray-500); color: white; border-color: var(--gray-500);">
                                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        `;
                    }

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p><strong>Failed to load reports</strong><br>Please try again later.</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function getReportIcon(type) {
            switch (type) {
                case 'user':
                    return 'user-x';
                case 'room':
                    return 'home';
                case 'message':
                    return 'message-square';
                default:
                    return 'flag';
            }
        }

        function formatReportDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffHours < 1) return 'just now';
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
        }

        function showAdminUsers() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-users').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading users...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_users')
                .done(function(users) {
                    const content = document.getElementById('admin-content');
                    content.innerHTML = `
                        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                                <i data-lucide="users" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                                <strong>${users.length}</strong> registered user${users.length !== 1 ? 's' : ''}
                            </p>
                        </div>
                        ${users.map(user => `
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 32px; height: 32px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                ${escapeHtml(user.username).charAt(0).toUpperCase()}
                                            </div>
                                            ${escapeHtml(user.username)}
                                            ${user.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                        </h4>
                                        <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <i data-lucide="mail" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            ${escapeHtml(user.email)}
                                        </p>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                            <i data-lucide="alert-triangle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            ${user.warnings} warning${user.warnings !== 1 ? 's' : ''} • 
                                            <i data-lucide="calendar" style="width: 12px; height: 12px; margin-left: 0.5rem; margin-right: 0.25rem;"></i>
                                            Joined: ${new Date(user.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                    ${!user.is_banned && user.id != window.currentUserId ? `
                                        <button class="header-btn header-btn--danger" onclick="banUser(${user.id}, '${escapeHtml(user.username).replace(/'/g, '\\\'')}')" title="Ban User">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban User
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p>Failed to load users</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function showAdminRooms() {
            document.querySelectorAll('.admin-tab-btn').forEach(btn => {
                btn.classList.remove('header-btn--primary');
            });
            document.getElementById('admin-tab-rooms').classList.add('header-btn--primary');

            document.getElementById('admin-content').innerHTML = `
                <div id="admin-loading" style="text-align: center; padding: 2rem;">
                    <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
                    <p>Loading rooms...</p>
                </div>
            `;

            $.get('handlers/admin_handler.php?action=get_rooms')
                .done(function(rooms) {
                    const content = document.getElementById('admin-content');
                    content.innerHTML = `
                        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                                <i data-lucide="message-square" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                                <strong>${rooms.length}</strong> room${rooms.length !== 1 ? 's' : ''} created
                            </p>
                        </div>
                        ${rooms.map(room => `
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                            <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 16px; height: 16px;"></i>
                                            ${escapeHtml(room.name)}
                                            ${room.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                        </h4>
                                        <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <i data-lucide="user" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            Created by ${escapeHtml(room.creator_name)}
                                        </p>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                            <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            Created: ${new Date(room.created_at).toLocaleDateString()} • 
                                            <span style="text-transform: capitalize;">${room.visibility}</span> room
                                        </p>
                                    </div>
                                    ${!room.is_banned ? `
                                        <button class="header-btn header-btn--danger" onclick="banRoom(${room.id}, '${escapeHtml(room.name).replace(/'/g, '\\\'')}')" title="Ban Room">
                                            <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                            Ban Room
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('')}
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .fail(function() {
                    document.getElementById('admin-content').innerHTML = `
                        <div style="text-align: center; padding: 3rem; color: var(--error);">
                            <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                            <p>Failed to load rooms</p>
                        </div>
                    `;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function processReport(reportId, decision) {
            const actionNames = {
                'ban': 'ban this entity',
                'warning': 'issue a warning',
                'reject': 'reject this report'
            };

            const actionName = actionNames[decision] || decision;

            if (!confirm(`Are you sure you want to ${actionName}?`)) {
                return;
            }

            const notes = prompt('Add admin notes (optional):') || '';
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Processing...';

            $.post('handlers/admin_handler.php', {
                    action: 'process_report',
                    report_id: reportId,
                    decision: decision,
                    notes: notes
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || 'Report processed successfully', 'success');
                        showAdminReports();
                        // Refresh rooms to show ban status
                        loadRooms();
                    } else {
                        showToast(response.error || 'Failed to process report', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to process report', 'error');
                })
                .always(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
        }

        function banUser(userId, username) {
            if (!confirm(`Are you sure you want to permanently ban user "${username}"?\n\nThis will:\n• Ban the user permanently (cannot login again)\n• Ban all rooms they created\n• Delete all their messages\n\nThis action cannot be undone.`)) return;

            $.post('handlers/admin_handler.php', {
                    action: 'ban_user',
                    user_id: userId
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || `User "${username}" has been permanently banned`, 'success');
                        // Refresh the admin panel
                        showAdminUsers();
                        // Refresh rooms to show ban status
                        loadRooms();
                    } else {
                        showToast(response.error || 'Failed to ban user', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to ban user', 'error');
                });
        }

        function banRoom(roomId, roomName) {
            if (!confirm(`Are you sure you want to permanently ban room "${roomName}"?\n\nThis will prevent anyone from sending messages in this room.\n\nThis action cannot be undone.`)) return;

            $.post('handlers/admin_handler.php', {
                    action: 'ban_room',
                    room_id: roomId
                })
                .done(function(response) {
                    if (response.success) {
                        showToast(response.message || `Room "${roomName}" has been permanently banned`, 'success');
                        // Refresh the admin panel and rooms list
                        showAdminRooms();
                        loadRooms();

                        // If we're currently in the banned room, exit it
                        if (currentRoomId == roomId) {
                            document.getElementById('chat-interface').style.display = 'none';
                            document.getElementById('welcome-screen').style.display = 'flex';
                            currentRoomId = null;
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                            }
                        }
                    } else {
                        showToast(response.error || 'Failed to ban room', 'error');
                    }
                })
                .fail(function() {
                    showToast('Failed to ban room', 'error');
                });
        }

        function closeAdminModal() {
            const modal = document.getElementById('adminModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                    }
                }, 300);
            }
        }

        // INITIALIZATION WITH COMPLETE FUNCTIONALITY
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing complete chat application...');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            if (!window.isLoggedIn) {
                console.log('User not logged in, handling auth forms');

                const signinTab = document.getElementById('signin-tab-btn');
                const registerTab = document.getElementById('register-tab-btn');
                const signinForm = document.getElementById('signin-form');
                const registerForm = document.getElementById('register-form');

                if (signinTab && registerTab && signinForm && registerForm) {
                    signinTab.addEventListener('click', function(e) {
                        e.preventDefault();
                        signinTab.classList.add('auth-tab--active');
                        registerTab.classList.remove('auth-tab--active');
                        signinForm.classList.remove('auth-form--hidden');
                        registerForm.classList.add('auth-form--hidden');
                    });

                    registerTab.addEventListener('click', function(e) {
                        e.preventDefault();
                        registerTab.classList.add('auth-tab--active');
                        signinTab.classList.remove('auth-tab--active');
                        registerForm.classList.remove('auth-form--hidden');
                        signinForm.classList.add('auth-form--hidden');
                    });

                    signinForm.addEventListener('submit', function() {
                        const btn = this.querySelector('.auth-btn--primary');
                        if (btn) {
                            btn.disabled = true;
                            btn.textContent = 'Signing In...';
                        }
                    });

                    registerForm.addEventListener('submit', function() {
                        const btn = this.querySelector('.auth-btn--primary');
                        if (btn) {
                            btn.disabled = true;
                            btn.textContent = 'Creating Account...';
                        }
                    });

                    console.log('Auth form handlers initialized');
                }
                return;
            }

            // Initialize theme
            initTheme();

            // Initialize mobile features
            initializeMobileFeatures();

            // Mobile header buttons (menu & back)
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileBackBtn = document.getElementById('mobile-back-btn');

            function updateMobileHeaderButtons() {
                if (window.innerWidth <= 768) {
                    if (mobileMenuBtn) mobileMenuBtn.style.display = 'inline-flex';
                } else {
                    if (mobileMenuBtn) mobileMenuBtn.style.display = 'none';
                    if (mobileBackBtn) mobileBackBtn.style.display = 'none';
                }
            }

            updateMobileHeaderButtons();
            window.addEventListener('resize', updateMobileHeaderButtons);

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleMobileSidebar();
                });
            }

            if (mobileBackBtn) {
                mobileBackBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('chat-interface').style.display = 'none';
                    document.getElementById('welcome-screen').style.display = 'flex';
                    currentRoomId = null;
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) sidebar.classList.add('show');
                    const overlay = document.querySelector('.sidebar-overlay');
                    if (overlay) overlay.classList.add('show');
                    mobileBackBtn.style.display = 'none';
                });
            }

            // Load joined rooms with persistence, then load rooms
            loadJoinedRooms();
            loadRooms();

            // Theme toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }

            // Mobile footer buttons inside sidebar
            const mobileThemeBtn = document.getElementById('mobile-theme-btn');
            if (mobileThemeBtn) {
                mobileThemeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleTheme();
                    closeMobileSidebar();
                });
            }

            const mobileAdminBtn = document.getElementById('mobile-admin-btn');
            if (mobileAdminBtn) {
                mobileAdminBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showAdminPanel();
                    closeMobileSidebar();
                });
            }

            // Admin panel button
            const adminBtn = document.getElementById('header-admin-btn');
            if (adminBtn) {
                adminBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showAdminPanel();
                });
            }

            // Room selection
            document.addEventListener('click', function(e) {
                if (e.target.closest('.sidebar-room') && !e.target.closest('.sidebar-room-btn') && !e.target.closest('.sidebar-room-badge--creator')) {
                    const roomElement = e.target.closest('.sidebar-room');
                    const roomId = roomElement.dataset.roomId;
                    const roomName = roomElement.querySelector('.sidebar-room-name').textContent;
                    selectRoom(roomId, roomName);
                }
            });

            // Create room modal
            const createRoomBtn = document.querySelector('.sidebar-create-btn');
            if (createRoomBtn) {
                createRoomBtn.addEventListener('click', function() {
                    const modal = document.getElementById('createRoomModal');
                    if (modal) {
                        modal.classList.add('show');
                    }
                });
            }

            // Modal close handlers
            document.querySelectorAll('[data-close]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const modal = this.closest('.modal-overlay');
                    if (modal) modal.classList.remove('show');
                });
            });

            // Create room form
            const createRoomForm = document.getElementById('createRoomForm');
            if (createRoomForm) {
                createRoomForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    createRoom();
                });
            }

            // File input
            const fileInput = document.getElementById('file-input');
            if (fileInput) {
                fileInput.addEventListener('change', handleFileSelect);
            }

            // File button
            const fileBtn = document.getElementById('file-btn');
            if (fileBtn) {
                fileBtn.addEventListener('click', function() {
                    if (fileInput) fileInput.click();
                });
            }

            // Message form
            const messageForm = document.getElementById('chat-message-form');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    sendMessage();
                });
            }

            // Message input auto-resize
            const messageInput = document.getElementById('chat-message-input');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Welcome create button
            const welcomeCreateBtn = document.getElementById('chat-welcome-create-btn');
            if (welcomeCreateBtn) {
                welcomeCreateBtn.addEventListener('click', function() {
                    const modal = document.getElementById('createRoomModal');
                    if (modal) {
                        modal.classList.add('show');
                    }
                });
            }

            // Close modals when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal-overlay')) {
                    e.target.classList.remove('show');
                }
            });

            // Handle window resize for mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMobileSidebar();
                } else {
                    initializeMobileFeatures();
                }
            });

            // Touch-friendly toggle for message actions on mobile devices
            (function enableTouchMessageActions() {
                if (!('ontouchstart' in window) && !navigator.maxTouchPoints) return;

                const messagesContainer = document.getElementById('chat-messages');
                if (!messagesContainer) return;

                messagesContainer.addEventListener('click', function(e) {
                    const messageEl = e.target.closest('.message');
                    if (!messageEl) return;

                    if (e.target.closest('.message-action-btn')) return;

                    const wasVisible = messageEl.classList.contains('actions-visible');
                    document.querySelectorAll('.message.actions-visible').forEach(el => el.classList.remove('actions-visible'));
                    if (!wasVisible) messageEl.classList.add('actions-visible');
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.message')) {
                        document.querySelectorAll('.message.actions-visible').forEach(el => el.classList.remove('actions-visible'));
                    }
                });
            })();

            // Start polling intervals - refresh rooms every 5 seconds
            setInterval(loadRooms, 5000);

            console.log('Complete chat application initialized');
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (pollingInterval) clearInterval(pollingInterval);
            if (adminPollingInterval) clearInterval(adminPollingInterval);
            if (roomsPollingInterval) clearInterval(roomsPollingInterval);
        });

        // Make functions globally available
        window.showToast = showToast;
        window.joinRoom = joinRoom;
        window.leaveRoom = leaveRoom;
        window.createRoom = createRoom;
        window.selectRoom = selectRoom;
        window.deleteMessage = deleteMessage;
        window.showImageModal = showImageModal;
        window.removeFilePreview = removeFilePreview;
        window.showReportModal = showReportModal;
        window.closeReportModal = closeReportModal;
        window.reportMessage = reportMessage;
        window.reportUser = reportUser;
        window.reportRoom = reportRoom;
        window.reportCurrentRoom = reportCurrentRoom;
        window.showRoomUsers = showRoomUsers;
        window.closeRoomUsersModal = closeRoomUsersModal;
        window.loadRoomMembers = loadRoomMembers;
        window.showAdminPanel = showAdminPanel;
        window.showAdminReports = showAdminReports;
        window.showAdminUsers = showAdminUsers;
        window.showAdminRooms = showAdminRooms;
        window.processReport = processReport;
        window.banUser = banUser;
        window.banRoom = banRoom;
        window.closeAdminModal = closeAdminModal;
        window.getReportIcon = getReportIcon;
        window.formatReportDate = formatReportDate;
        window.toggleMobileSidebar = toggleMobileSidebar;
        window.closeMobileSidebar = closeMobileSidebar;
        window.initializeMobileFeatures = initializeMobileFeatures;
        window.openInviteModal = openInviteModal;
        window.closeInviteModal = closeInviteModal;
        window.submitInvite = submitInvite;
    </script>

</body>

</html>