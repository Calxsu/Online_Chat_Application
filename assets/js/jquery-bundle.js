// All jQuery-dependent code

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