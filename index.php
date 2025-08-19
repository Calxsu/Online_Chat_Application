<?php
session_start();

require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/User.php';
require_once 'classes/Room.php';
require_once 'classes/Message.php';
require_once 'classes/Report.php';
require_once 'classes/Admin.php';

$auth = new Auth();
$user = new User();
$room = new Room();
$message = new Message();
$report = new Report();
$admin = new Admin();

$isLoggedIn = $auth->check();
$isAdmin = $isLoggedIn && $user->isAdmin($_SESSION['user_id']);

if ($isLoggedIn) {
    $user->updateLastSeen($_SESSION['user_id']);
    
    if ($isAdmin) {
        $allUsers = $user->getAll();
        $allRooms = $room->getAllForAdmin();
        $allReports = $admin->getReportsWithDetails();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ChatApp - Modern, secure chat application for teams and communities">
    <meta name="keywords" content="chat, messaging, communication, teams, rooms">
    <meta name="author" content="ChatApp">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <title><?php echo $isLoggedIn ? 'ChatApp - Dashboard' : 'ChatApp - Sign In'; ?></title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="classes/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2'><path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/></svg>">
    
    <!-- Theme color for mobile browsers -->
    <meta name="theme-color" content="#1e293b">
    <meta name="msapplication-navbutton-color" content="#1e293b">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <style>
    /* Enhanced Dark Mode Styles */
    :root {
        --primary-color: #007bff;
        --secondary-color: #6c757d;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
        --info-color: #17a2b8;
        --dark-color: #343a40;
        --light-color: #f8f9fa;
        
        --bg-primary: #ffffff;
        --bg-secondary: #f8f9fa;
        --bg-tertiary: #e9ecef;
        --text-primary: #212529;
        --text-secondary: #6c757d;
        --text-muted: #adb5bd;
        --border-color: #dee2e6;
        
        --border-radius: 0.375rem;
        --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        --transition: all 0.15s ease-in-out;
    }
    
    [data-theme="dark"] {
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --bg-tertiary: #404040;
        --text-primary: #ffffff;
        --text-secondary: #e0e0e0;
        --text-muted: #b0b0b0;
        --border-color: #404040;
        
        --primary-color: #0d6efd;
        --success-color: #198754;
        --danger-color: #dc3545;
        --warning-color: #fd7e14;
        --info-color: #0dcaf0;
    }
    
    body {
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        transition: var(--transition);
    }
    
    .navbar {
        background: var(--bg-secondary) !important;
        border-bottom: 1px solid var(--border-color);
        box-shadow: var(--box-shadow);
    }
    
    .navbar-brand, .navbar-nav .nav-link {
        color: var(--text-primary) !important;
    }
    
    .card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        transition: var(--transition);
        color: var(--text-primary);
    }
    
    .card-header {
        background: var(--bg-tertiary);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }
    
    .btn {
        border-radius: var(--border-radius);
        transition: var(--transition);
    }
    
    .form-control {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        border-radius: var(--border-radius);
    }
    
    .form-control:focus {
        background: var(--bg-primary);
        border-color: var(--primary-color);
        color: var(--text-primary);
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .form-control::placeholder {
        color: var(--text-muted);
    }
    
    .modal-content {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .modal-header {
        border-bottom: 1px solid var(--border-color);
    }
    
    .modal-footer {
        border-top: 1px solid var(--border-color);
    }
    
    .dropdown-menu {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .dropdown-item {
        color: var(--text-primary);
    }
    
    .dropdown-item:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }
    
    .theme-toggle {
        background: none;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: var(--border-radius);
        transition: var(--transition);
    }
    
    .theme-toggle:hover {
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }
    
    .loading-spinner {
        width: 1.5rem;
        height: 1.5rem;
        border: 2px solid var(--border-color);
        border-top: 2px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .message {
        margin-bottom: 1rem;
        padding: 0.75rem;
        border-radius: var(--border-radius);
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .message-own {
        background: rgba(0, 123, 255, 0.1);
        border-color: var(--primary-color);
        margin-left: auto;
        max-width: 70%;
    }
    
    .message-deleted {
        opacity: 0.6;
        font-style: italic;
        color: var(--text-muted);
    }
    
    .room-item {
        padding: 1rem;
        margin-bottom: 0.5rem;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        transition: var(--transition);
        cursor: pointer;
        color: var(--text-primary);
    }
    
    .room-item:hover {
        background: var(--bg-tertiary);
        border-color: var(--primary-color);
        transform: translateY(-1px);
    }
    
    .room-item.joined {
        border-color: var(--success-color);
        background: rgba(40, 167, 69, 0.1);
    }
    
    .toast {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .auth-container {
        min-height: 100vh;
        background: linear-gradient(135deg, var(--primary-color), #6f42c1);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .auth-card {
        background: var(--bg-primary);
        border-radius: calc(var(--border-radius) * 2);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        max-width: 400px;
        width: 100%;
        color: var(--text-primary);
    }
    
    /* Text color fixes for dark mode */
    .text-muted {
        color: var(--text-muted) !important;
    }
    
    .text-primary {
        color: var(--text-primary) !important;
    }
    
    .text-secondary {
        color: var(--text-secondary) !important;
    }
    
    .alert {
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    
    .alert-danger {
        background-color: rgba(220, 53, 69, 0.1);
        border-color: var(--danger-color);
        color: var(--danger-color);
    }
    
    .alert-success {
        background-color: rgba(40, 167, 69, 0.1);
        border-color: var(--success-color);
        color: var(--success-color);
    }
    
    .alert-warning {
        background-color: rgba(255, 193, 7, 0.1);
        border-color: var(--warning-color);
        color: #856404;
    }
    
    [data-theme="dark"] .alert-warning {
        color: var(--warning-color);
    }
    
    /* Badge fixes */
    .badge {
        color: white;
    }
    
    /* List group fixes */
    .list-group-item {
        background: var(--bg-secondary);
        border-color: var(--border-color);
        color: var(--text-primary);
    }
    
    .list-group-item:hover {
        background: var(--bg-tertiary);
    }
    </style>
</head>
<body>
    
    <?php if ($isLoggedIn): ?>
        <!-- Main Application -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i data-lucide="message-square" class="me-2"></i>
                    ChatApp
                </a>
                
                <div class="d-flex align-items-center gap-3">
                    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">
                        <i data-lucide="moon" style="width: 20px; height: 20px;"></i>
                    </button>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3">
                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Rooms <span class="badge bg-primary" id="rooms-count">0</span></h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <?php include 'modules/rooms_section.php'; ?>
                        </div>
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Admin Panel</h5>
                        </div>
                        <div class="card-body">
                            <?php include 'modules/admin_dashboard.php'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Chat Area -->
                <div class="col-md-9">
                    <div class="card mt-3" style="height: calc(100vh - 120px);">
                        <?php include 'modules/chat_window.php'; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Authentication -->
        <div class="auth-container">
            <div class="auth-card">
                <?php include 'modules/auth_form.php'; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js" crossorigin="anonymous"></script>
    
    <!-- Application Scripts in correct order -->
    <script src="js/utils.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/room.js"></script>
    <script src="js/message.js"></script>
    <?php if ($isAdmin): ?>
    <script src="js/admin.js"></script>
    <?php endif; ?>
    
    <!-- Global Variables -->
    <script>
        // Application state
        window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        window.isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        
        <?php if ($isLoggedIn): ?>
        window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
        window.currentUsername = <?php echo json_encode($_SESSION['username']); ?>;
        
        <?php if ($isAdmin): ?>
        window.allUsers = <?php echo json_encode($allUsers ?? []); ?>;
        window.allRooms = <?php echo json_encode($allRooms ?? []); ?>;
        window.allReports = <?php echo json_encode($allReports ?? []); ?>;
        <?php endif; ?>
        <?php endif; ?>
    </script>
    
    <!-- Theme toggle -->
    <script>
        document.getElementById('theme-toggle')?.addEventListener('click', function() {
            const icon = this.querySelector('[data-lucide]');
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (icon) {
                icon.setAttribute('data-lucide', newTheme === 'dark' ? 'sun' : 'moon');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        });
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            const themeIcon = document.querySelector('#theme-toggle [data-lucide]');
            if (themeIcon) {
                themeIcon.setAttribute('data-lucide', savedTheme === 'dark' ? 'sun' : 'moon');
            }
            
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
        
        // Toast notification system
        function showToast(message, type = 'info', duration = 5000) {
            const container = document.querySelector('.toast-container');
            if (!container) return;
            
            const toastId = 'toast-' + Date.now();
            const typeClass = {
                'success': 'text-bg-success',
                'danger': 'text-bg-danger', 
                'warning': 'text-bg-warning',
                'info': 'text-bg-info'
            }[type] || 'text-bg-info';
            
            const toastHtml = `
                <div class="toast ${typeClass}" id="${toastId}" role="alert">
                    <div class="toast-body">
                        ${message}
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { 
                autohide: true, 
                delay: duration 
            });
            
            toast.show();
            
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
        
        // Make showToast globally available
        window.showToast = showToast;
        
        // Report function
        function reportEntity(type, entityId, reason) {
            if (!reason) {
                reason = prompt('Please provide a reason for reporting this content:');
                if (!reason || reason.trim().length < 5) {
                    showToast('Please provide a detailed reason (at least 5 characters)', 'warning');
                    return;
                }
            }
            
            fetch('handlers/report_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=create&type=${type}&entity_id=${entityId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Report submitted successfully', 'success');
                } else {
                    showToast(data.error || 'Failed to submit report', 'danger');
                }
            })
            .catch(error => {
                console.error('Error submitting report:', error);
                showToast('Failed to submit report', 'danger');
            });
        }
        
        window.reportEntity = reportEntity;
    </script>
    
</body>
</html>