// Room Management Functions (fixed Open vs Join logic)

// Create Room with immediate update
function createRoom() {
    const name = $('#new-room-name').val();
    const visibility = $('#room-visibility').val();
    
    if (!name || !name.trim()) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please enter a room name', 'warning');
        } else {
            alert('Please enter a room name');
        }
        return;
    }

    $.post('handlers/room_handler.php', {
        action: 'create',
        name: name.trim(),
        visibility: visibility
    }, function(response) {
        if (response.success) {
            $('#new-room-name').val('');
            
            // Close modal if exists
            const modal = bootstrap.Modal.getInstance(document.getElementById('createRoomModal'));
            if (modal) modal.hide();
            
            showRoomCreatedNotification(name);
            loadRooms();
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(response.error || 'Failed to create room', 'danger');
            } else {
                alert(response.error || 'Failed to create room');
            }
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to create room', 'danger');
        } else {
            alert('Failed to create room');
        }
    });
}

// Load and display rooms dynamically (fixed Open vs Join logic)
function loadRooms(silent = false) {
    if (!silent) {
        $('#rooms-loading').show();
        $('#rooms-list').hide();
    }

    $.get('handlers/room_handler.php', { action: 'get_rooms' }, function(rooms) {
        if (Array.isArray(rooms)) {
            // Check for new rooms
            if (rooms.length > lastRoomsCount && lastRoomsCount > 0 && !silent) {
                showNewRoomNotification(rooms.length - lastRoomsCount);
            }
            lastRoomsCount = rooms.length;
            
            // Update rooms count
            $('#rooms-count').text(rooms.length);
            
            // Sort rooms: joined first, then by creation date
            rooms.sort((a, b) => {
                const aJoined = joinedRooms.has(a.id.toString());
                const bJoined = joinedRooms.has(b.id.toString());
                if (aJoined && !bJoined) return -1;
                if (!aJoined && bJoined) return 1;
                return new Date(b.created_at) - new Date(a.created_at);
            });

            const roomsList = rooms.map(function(room) {
                const isJoined = joinedRooms.has(room.id.toString());
                const joinedBadge = isJoined ? '<span class="badge bg-success ms-2">Joined</span>' : '';
                const visibilityIcon = room.visibility === 'private' ? 'lock' : 'globe';

                return `
                    <div class="room-item ${isJoined ? 'joined' : ''}" data-room-id="${room.id}">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i data-lucide="${visibilityIcon}" style="width: 16px; height: 16px;"></i>
                                    <strong class="room-name">${escapeHtml(room.name)}</strong>
                                    ${joinedBadge}
                                </div>
                                <small class="text-muted">
                                    Created by ${escapeHtml(room.creator_name)} • ${formatDate(room.created_at)}
                                </small>
                            </div>
                            
                            <div class="d-flex gap-1">
                                ${isJoined ? `
                                    <button class="btn btn-success btn-sm" onclick="openRoom(${room.id}, '${escapeHtml(room.name)}')">
                                        <i data-lucide="message-circle" style="width: 14px; height: 14px;"></i>
                                        Open
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="leaveRoom(${room.id}, '${escapeHtml(room.name)}')">
                                        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                                    </button>
                                ` : `
                                    <button class="btn btn-primary btn-sm" onclick="joinRoom(${room.id}, '${escapeHtml(room.name)}')">
                                        <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
                                        Join
                                    </button>
                                `}
                                
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i data-lucide="more-horizontal" style="width: 14px; height: 14px;"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="showRoomInfo(${room.id})">
                                            <i data-lucide="info" class="me-2" style="width: 14px; height: 14px;"></i>
                                            Room Info
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-warning" href="#" onclick="reportEntity('room', ${room.id})">
                                            <i data-lucide="flag" class="me-2" style="width: 14px; height: 14px;"></i>
                                            Report Room
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            $('#rooms-list').html(roomsList);
            
            // Show empty state if no rooms
            if (rooms.length === 0) {
                $('#rooms-list').html(`
                    <div class="text-center py-4">
                        <i data-lucide="message-square" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                        <h5>No rooms available</h5>
                        <p class="text-muted">Be the first to create a room!</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                            <i data-lucide="plus"></i>
                            Create Room
                        </button>
                    </div>
                `);
            }
            
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
        } else {
            console.error('Invalid rooms response:', rooms);
            $('#rooms-list').html(`
                <div class="alert alert-danger">
                    <i data-lucide="alert-circle" class="me-2"></i>
                    Failed to load rooms
                </div>
            `);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        $('#rooms-loading').hide();
        $('#rooms-list').show();
        
    }, 'json').fail(function(xhr, status, error) {
        console.error('Error loading rooms:', error);
        $('#rooms-loading').hide();
        $('#rooms-list').html(`
            <div class="alert alert-danger">
                <i data-lucide="wifi-off" class="me-2"></i>
                Failed to load rooms. Please try again.
                <button class="btn btn-sm btn-primary ms-2" onclick="loadRooms()">
                    <i data-lucide="refresh-cw" class="me-1"></i>
                    Retry
                </button>
            </div>
        `).show();
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
}

// Open room (for already joined rooms) - NO permission check needed
function openRoom(roomId, roomName) {
    console.log(`Opening already joined room: ${roomName} (${roomId})`);
    
    // Directly load room since user is already joined
    loadRoomInfo(roomId, roomName);
    
    // Show feedback
    if (typeof window.showToast === 'function') {
        window.showToast(`Opening "${roomName}"`, 'info');
    }
}

// Join room (for new rooms) - permission check required
function joinRoom(roomId, roomName) {
    console.log(`Attempting to join room: ${roomName} (${roomId})`);
    
    // Check if user can join this room
    $.post('handlers/room_handler.php', {
        action: 'can_join',
        room_id: roomId
    }, function(response) {
        if (response.can_join) {
            // Add to joined rooms
            joinedRooms.add(roomId.toString());
            saveJoinedRooms();
            
            // Update room buttons
            updateRoomButtons(roomId, true);
            
            // Load room and messages
            loadRoomInfo(roomId, roomName);
            
            // Show success message
            if (typeof window.showToast === 'function') {
                window.showToast(`Joined "${roomName}"`, 'success');
            }
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('You cannot join this room', 'warning');
            } else {
                alert('You cannot join this room');
            }
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to join room', 'danger');
        }
    });
}

// Leave room
function leaveRoom(roomId, roomName) {
    if (confirm(`Are you sure you want to leave "${roomName}"?`)) {
        // Remove from joined rooms
        joinedRooms.delete(roomId.toString());
        saveJoinedRooms();
        
        // Update UI
        updateRoomButtons(roomId, false);
        
        // If currently in this room, leave chat
        if (currentRoomId === roomId) {
            if (typeof resetChat === 'function') {
                resetChat();
            }
            currentRoomId = null;
            currentRoomInfo = null;
        }
        
        showLeaveRoomNotification(roomName);
        
        // Refresh rooms list
        setTimeout(() => loadRooms(true), 500);
    }
}

// Load room info and switch to chat
function loadRoomInfo(roomId, roomName) {
    $.get('handlers/room_handler.php', {
        action: 'get_room_info',
        room_id: roomId
    }, function(roomInfo) {
        if (roomInfo && !roomInfo.error) {
            currentRoomId = roomId;
            currentRoomInfo = roomInfo;
            lastMessageId = 0;
            displayedMessageIds.clear();
            
            // Update chat header
            if (typeof updateChatHeader === 'function') {
                updateChatHeader(roomInfo);
            }
            
            // Load messages
            if (typeof loadMessages === 'function') {
                loadMessages(roomId);
            }
            
            // Start polling
            if (typeof startMessagePolling === 'function') {
                startMessagePolling();
            }
            
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast('Room not found', 'danger');
            }
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to load room', 'danger');
        }
    });
}

// Update room buttons dynamically
function updateRoomButtons(roomId, isJoined) {
    const roomElement = $(`.room-item[data-room-id="${roomId}"]`);
    if (roomElement.length) {
        if (isJoined) {
            roomElement.addClass('joined');
            
            // Add joined badge if not present
            if (!roomElement.find('.badge').length) {
                roomElement.find('.room-name').after('<span class="badge bg-success ms-2">Joined</span>');
            }
        } else {
            roomElement.removeClass('joined');
            roomElement.find('.badge').remove();
        }
        
        // Refresh the entire room list to update buttons
        setTimeout(() => loadRooms(true), 100);
    }
}

// Update chat header when room is selected
function updateChatHeader(roomInfo) {
    $('#current-room-name').text(roomInfo.name);
    $('#current-room-status').text(`${roomInfo.visibility} room • Created by ${roomInfo.creator_name}`);
    $('#chat-actions').show();
    
    // Enable message input
    $('#message-input').prop('disabled', false).attr('placeholder', 'Type a message...');
    $('#send-btn, #attach-btn').prop('disabled', false);
    
    // Hide no room selected state
    $('#no-room-selected').hide();
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Show room info modal
function showRoomInfo(roomId) {
    const roomElement = $(`.room-item[data-room-id="${roomId}"]`);
    if (!roomElement.length) return;
    
    const roomName = roomElement.find('.room-name').text();
    const isJoined = roomElement.hasClass('joined');
    const isPrivate = roomElement.find('[data-lucide="lock"]').length > 0;
    
    const modalContent = `
        <div class="modal fade" id="roomInfoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-lucide="${isPrivate ? 'lock' : 'globe'}" class="me-2"></i>
                            Room Information
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <h4>${escapeHtml(roomName)}</h4>
                        <p class="text-muted">${isPrivate ? 'Private' : 'Public'} Room</p>
                        
                        <div class="row">
                            <div class="col-6">
                                <strong>Status:</strong><br>
                                <span class="badge ${isJoined ? 'bg-success' : 'bg-secondary'}">
                                    ${isJoined ? 'Joined' : 'Not Joined'}
                                </span>
                            </div>
                            <div class="col-6">
                                <strong>Type:</strong><br>
                                ${isPrivate ? 'Private' : 'Public'}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        ${!isJoined ? `
                            <button type="button" class="btn btn-primary" onclick="joinRoom(${roomId}, '${escapeHtml(roomName)}'); bootstrap.Modal.getInstance(document.getElementById('roomInfoModal')).hide();">
                                Join Room
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    $('#roomInfoModal').remove();
    
    // Add new modal
    $('body').append(modalContent);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('roomInfoModal'));
    modal.show();
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Form submission handler for create room modal
$(document).on('submit', '#createRoomForm', function(e) {
    e.preventDefault();
    createRoom();
});

// Make functions globally available
window.createRoom = createRoom;
window.loadRooms = loadRooms;
window.openRoom = openRoom; // NEW: separate function for opening joined rooms
window.joinRoom = joinRoom; // UPDATED: only for joining new rooms
window.leaveRoom = leaveRoom;
window.loadRoomInfo = loadRoomInfo;
window.updateRoomButtons = updateRoomButtons;
window.updateChatHeader = updateChatHeader;
window.showRoomInfo = showRoomInfo;