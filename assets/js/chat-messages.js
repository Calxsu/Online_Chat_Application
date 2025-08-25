// Message Functions

// Function to reset message state when switching rooms
function resetMessageState() {
    console.log('Resetting message state for room switch');
    
    // Clear any existing polling interval
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    
    // Reset all message-related state variables
    lastMessageId = 0;
    displayedMessageIds.clear();
    pollPaused = false;
    isSending = false;
    
    // Clear the messages container
    const container = document.getElementById('chat-messages');
    if (container) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem 1rem; color: var(--gray-500);">
                <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite;"></i>
                <p>Loading messages...</p>
            </div>
        `;
    }
}

function loadMessages(roomId) {
    console.log('Loading messages for room:', roomId);
    
    // Ensure we're not in a paused state
    pollPaused = false;
    
    $.get('handlers/message_handler.php', {
            action: 'get',
            room_id: roomId,
            limit: 50
        })
        .done(function(messages) {
            console.log('Messages loaded successfully:', messages.length, 'messages');
            displayMessages(messages);
            if (messages.length > 0) {
                lastMessageId = Math.max(...messages.map(m => m.id));
                console.log('Set lastMessageId to:', lastMessageId);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Failed to load messages:', { status, error });
            showToast('Failed to load messages', 'error');
            
            // Show error in chat container
            const container = document.getElementById('chat-messages');
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 3rem 1rem; color: var(--error);">
                        <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                        <p>Failed to load messages. Please try refreshing.</p>
                    </div>
                `;
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        });
}

function displayMessages(messages) {
    const container = document.getElementById('chat-messages');
    if (!container) {
        console.error('Chat messages container not found');
        return;
    }

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
        // Clear the displayed message IDs set before repopulating
        displayedMessageIds.clear();
        
        // Reverse messages for correct chronological order (oldest first)
        const reversedMessages = [...messages].reverse();
        container.innerHTML = reversedMessages.map(message => createMessageHTML(message)).join('');
        
        // Track displayed message IDs
        messages.forEach(m => {
            if (m && m.id !== undefined && m.id !== null) {
                displayedMessageIds.add(m.id);
            }
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

        // Pause polling during message send
        pollPaused = true;
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

            // Resume polling after a short delay
            setTimeout(function() {
                pollPaused = false;
                console.log('Resumed polling after message send');
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
    console.log('Starting message polling for room:', roomId);
    
    // Clear any existing polling interval first
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    
    // Ensure polling is not paused
    pollPaused = false;

    pollingInterval = setInterval(() => {
        // Skip if polling is paused or room has changed
        if (pollPaused || currentRoomId != roomId) {
            console.log('Skipping poll - paused:', pollPaused, 'room mismatch:', currentRoomId != roomId);
            return;
        }
        
        // Check if room is banned (stop polling if banned)
        if (currentRoomInfo && (currentRoomInfo.is_banned == 1 || currentRoomInfo.is_banned === true)) {
            console.log('Room is banned, stopping polling');
            clearInterval(pollingInterval);
            pollingInterval = null;
            return;
        }

        $.get('handlers/message_handler.php', {
                action: 'poll',
                room_id: roomId,
                last_id: lastMessageId
            })
            .done(function(newMessages) {
                // Double-check we're still in the same room using == for type coercion
                if (currentRoomId != roomId) {
                    console.log('Room changed during poll, ignoring results');
                    return;
                }
                
                if (newMessages && newMessages.length > 0) {
                    console.log('New messages received:', newMessages.length);
                    appendNewMessages(newMessages);
                    const numericIds = newMessages.map(m => m.id).filter(i => i !== undefined && i !== null && !isNaN(i));
                    if (numericIds.length) {
                        lastMessageId = Math.max(lastMessageId, ...numericIds);
                    }
                }
            })
            .fail(function(xhr, status, error) {
                // Only log critical errors, not routine polling failures
                if (status !== 'abort' && xhr.status !== 0) {
                    console.error('Polling error:', { status, error, roomId });
                }
            });
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

// Export the reset function so it can be called from other modules
window.resetMessageState = resetMessageState;