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