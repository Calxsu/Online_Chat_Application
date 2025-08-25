// Room Management Functions

function selectRoom(roomId, roomName) {
    console.log('Selecting room:', roomId, roomName);

    // Clear any existing polling before switching rooms
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    
    // Reset poll paused state
    pollPaused = false;

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

    // Close mobile sidebar if on mobile
    if (window.innerWidth <= 768) {
        closeMobileSidebar();
    }

    // If switching to a different room, reset message state
    if (currentRoomId !== roomId) {
        console.log('Switching from room', currentRoomId, 'to', roomId);
        
        // Reset message state before switching
        if (typeof resetMessageState === 'function') {
            resetMessageState();
        }
    }

    // Update current room info BEFORE loading messages - THIS IS CRITICAL!
    currentRoomId = roomId;
    currentRoomInfo = room;
    console.log('Set currentRoomId to:', currentRoomId);

    // Update UI to show active room
    document.querySelectorAll('.sidebar-room').forEach(room => {
        room.classList.remove('sidebar-room--active');
    });

    const selectedRoom = document.querySelector(`[data-room-id="${roomId}"]`);
    if (selectedRoom) {
        selectedRoom.classList.add('sidebar-room--active');
    }

    // Show chat interface
    document.getElementById('welcome-screen').style.display = 'none';
    document.getElementById('chat-interface').style.display = 'flex';
    
    const chatRoomNameEl = document.getElementById('chat-room-name');
    if (chatRoomNameEl) {
        chatRoomNameEl.textContent = roomName;
    }

    // Enable chat input
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

    // Handle mobile back button
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

    // Load messages for the selected room - currentRoomId is already set
    console.log('About to load messages for room:', currentRoomId);
    loadMessages(roomId);
    
    // Start polling for new messages immediately after loading
    startMessagePolling(roomId);
}

// Function to properly leave current room
function leaveCurrentRoom() {
    console.log('Leaving current room:', currentRoomId);
    
    // Clear polling
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    
    // Reset state
    currentRoomId = null;
    currentRoomInfo = null;
    lastMessageId = 0;
    displayedMessageIds.clear();
    pollPaused = false;
    
    // Show welcome screen
    document.getElementById('chat-interface').style.display = 'none';
    document.getElementById('welcome-screen').style.display = 'flex';
    
    // Clear active room selection
    document.querySelectorAll('.sidebar-room').forEach(room => {
        room.classList.remove('sidebar-room--active');
    });
}

// Export functions
window.selectRoom = selectRoom;
window.leaveCurrentRoom = leaveCurrentRoom;