// Global state variables (maintaining original structure)
let currentRoomId = null;
let currentRoomInfo = null;
let lastMessageId = 0;
let pollingInterval = null;
let adminPollingInterval = null;
let roomsPollingInterval = null;
let canSend = true;
let displayedMessageIds = new Set();
let lastReportCount = 0;
let joinedRooms = new Set();
let lastRoomsCount = 0;
let deletedMessageIds = new Set();

// Initialize localStorage for joined rooms
function initJoinedRooms() {
    if (!window.currentUserId) return;
    
    const stored = localStorage.getItem('joinedRooms_' + window.currentUserId);
    if (stored) {
        try {
            joinedRooms = new Set(JSON.parse(stored));
        } catch (e) {
            console.error('Error parsing joinedRooms from localStorage:', e);
            joinedRooms = new Set();
        }
    }
}

// Save joined rooms to localStorage
function saveJoinedRooms() {
    if (!window.currentUserId) return;
    
    try {
        localStorage.setItem('joinedRooms_' + window.currentUserId, JSON.stringify([...joinedRooms]));
    } catch (e) {
        console.error('Error saving joinedRooms to localStorage:', e);
    }
}

// Show notification for new rooms
function showNewRoomNotification(newCount) {
    if (typeof window.showToast === 'function') {
        window.showToast(`${newCount} new room${newCount > 1 ? 's' : ''} available!`, 'info');
    } else {
        console.log(`${newCount} new room${newCount > 1 ? 's' : ''} available!`);
    }
}

// Show room created notification
function showRoomCreatedNotification(roomName) {
    if (typeof window.showToast === 'function') {
        window.showToast(`Room "${roomName}" created successfully!`, 'success');
    } else {
        alert(`Room "${roomName}" created successfully!`);
    }
}

// Show room leave notification
function showLeaveRoomNotification(roomName) {
    if (typeof window.showToast === 'function') {
        window.showToast(`You have left "${roomName}"`, 'info');
    } else {
        alert(`You have left "${roomName}"`);
    }
}

// Initialize the application
function initializeApp() {
    console.log('🚀 ChatApp initializing...');
    
    if (window.isLoggedIn && window.currentUserId) {
        initJoinedRooms();
        loadRooms();
        startRoomsPolling();

        if (window.isAdmin) {
            loadReports();
            updateAdminDashboard();
            startAdminPolling();
        }
    } else if (!window.isLoggedIn) {
        if (typeof showLoginForm === 'function') {
            showLoginForm();
        }
    }
    
    console.log('✅ ChatApp initialized');
}

// Start rooms polling
function startRoomsPolling() {
    if (roomsPollingInterval) {
        clearInterval(roomsPollingInterval);
    }
    
    roomsPollingInterval = setInterval(function() {
        loadRooms(true); // silent = true
    }, 5000);
}

// Cleanup intervals
function cleanup() {
    if (pollingInterval) clearInterval(pollingInterval);
    if (adminPollingInterval) clearInterval(adminPollingInterval);
    if (roomsPollingInterval) clearInterval(roomsPollingInterval);
    
    console.log('🧹 ChatApp cleaned up');
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return 'Today';
    if (diffDays === 2) return 'Yesterday';
    if (diffDays <= 7) return `${diffDays} days ago`;
    
    return date.toLocaleDateString();
}

// Event handlers setup
function setupEventHandlers() {
    // Enter key support for message input
    $(document).on('keypress', '#message-input', function(e) {
        if (e.which === 13 && !e.shiftKey && canSend) {
            e.preventDefault();
            if (typeof sendMessage === 'function') {
                sendMessage();
            }
        }
    });

    // Event delegation for leave buttons
    $(document).on('click', '[data-action="leave-room"]', function(e) {
        e.preventDefault();
        const roomId = $(this).data('room-id');
        const roomName = $(this).data('room-name');
        if (typeof leaveRoom === 'function') {
            leaveRoom(roomId, roomName);
        }
    });

    // Auto-resize textareas
    $(document).on('input', 'textarea', function() {
        this.style.height = 'auto';
        const maxHeight = parseInt($(this).css('max-height')) || 120;
        const newHeight = Math.min(this.scrollHeight, maxHeight);
        this.style.height = newHeight + 'px';
    });
}

// Initialize when DOM is ready
$(document).ready(function() {
    setupEventHandlers();
    initializeApp();
});

// Cleanup on page unload
$(window).on('beforeunload', function() {
    cleanup();
});

// Make functions globally available
window.initializeApp = initializeApp;
window.initJoinedRooms = initJoinedRooms;
window.saveJoinedRooms = saveJoinedRooms;
window.showNewRoomNotification = showNewRoomNotification;
window.showRoomCreatedNotification = showRoomCreatedNotification;
window.showLeaveRoomNotification = showLeaveRoomNotification;
window.startRoomsPolling = startRoomsPolling;
window.cleanup = cleanup;
window.escapeHtml = escapeHtml;
window.formatDate = formatDate;