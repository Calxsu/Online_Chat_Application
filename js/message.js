// Message Management Functions (fixed message display and sizing)

// Load messages for a room
function loadMessages(roomId, limit = 20) {
    console.log('Loading messages for room:', roomId);
    
    const messagesContainer = $('#messages');
    if (messagesContainer.children().length === 0 || messagesContainer.text().includes('No messages')) {
        messagesContainer.html(`
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <small class="text-muted d-block mt-2">Loading messages...</small>
            </div>
        `);
    }

    $.get('handlers/message_handler.php', {
        action: 'get',
        room_id: roomId,
        limit: limit
    }, function(messages) {
        console.log('Loaded messages:', messages);
        
        if (Array.isArray(messages)) {
            renderMessages(messages);
            
            // Update state
            messages.forEach(msg => {
                if (msg.id > lastMessageId) {
                    lastMessageId = msg.id;
                }
                displayedMessageIds.add(msg.id);
            });
            
            // Auto-scroll to bottom
            setTimeout(() => scrollToBottom(false), 100);
            
        } else if (messages.error) {
            messagesContainer.html(`
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i data-lucide="alert-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                    <div>${escapeHtml(messages.error)}</div>
                </div>
            `);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
    }, 'json').fail(function(xhr, status, error) {
        console.error('Error loading messages:', error);
        const messagesContainer = $('#messages');
        messagesContainer.html(`
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i data-lucide="wifi-off" class="me-2" style="width: 16px; height: 16px;"></i>
                <div class="flex-grow-1">
                    Failed to load messages. 
                    <button class="btn btn-sm btn-primary ms-2" onclick="loadMessages(${roomId})">
                        <i data-lucide="refresh-cw" class="me-1" style="width: 12px; height: 12px;"></i>
                        Retry
                    </button>
                </div>
            </div>
        `);
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
}

// Render messages in the UI
function renderMessages(messages) {
    const messagesContainer = $('#messages');
    
    if (!Array.isArray(messages) || messages.length === 0) {
        messagesContainer.html(`
            <div class="text-center py-5">
                <i data-lucide="message-circle" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                <h5>No messages yet</h5>
                <p class="text-muted">Start the conversation by sending a message!</p>
            </div>
        `);
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        return;
    }

    const messagesHtml = messages.map(renderMessage).join('');
    messagesContainer.html(messagesHtml);
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Append new messages to existing ones
function appendMessages(messages) {
    if (!Array.isArray(messages) || messages.length === 0) return;
    
    const messagesContainer = $('#messages');
    
    // Remove empty state if present
    messagesContainer.find('.text-center').remove();
    
    messages.forEach(message => {
        if (!displayedMessageIds.has(message.id)) {
            const messageHtml = renderMessage(message);
            messagesContainer.append(messageHtml);
            displayedMessageIds.add(message.id);
            
            // Update last message ID
            if (message.id > lastMessageId) {
                lastMessageId = message.id;
            }
        }
    });
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Auto-scroll if near bottom
    const container = messagesContainer[0];
    if (container && container.scrollTop + container.clientHeight >= container.scrollHeight - 100) {
        setTimeout(() => scrollToBottom(), 100);
    }
}

// Render individual message (improved layout)
function renderMessage(message) {
    const isOwn = message.user_id === window.currentUserId;
    const isDeleted = message.is_deleted === 1;
    const isSystem = message.user_id === 0;
    
    let messageClasses = ['message'];
    if (isOwn) messageClasses.push('message-own');
    if (isSystem) messageClasses.push('message-system');
    if (isDeleted) messageClasses.push('message-deleted');
    
    const avatar = isSystem ? 
        '<i data-lucide="bot" style="width: 16px; height: 16px;"></i>' :
        (message.username ? message.username.charAt(0).toUpperCase() : '?');
    
    const content = isDeleted ? 
        '<em class="text-muted">This message was deleted</em>' :
        formatMessageContent(message.message);
    
    const attachment = message.attachment ? renderAttachment(message.attachment) : '';
    
    const actions = (!isSystem && !isDeleted) ? `
        <div class="message-actions">
            ${isOwn || window.isAdmin ? `
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="deleteMessage(${message.id})" 
                        title="Delete message">
                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                </button>
            ` : ''}
            <button class="btn btn-sm btn-outline-warning" 
                    onclick="reportEntity('message', ${message.id})" 
                    title="Report message">
                <i data-lucide="flag" style="width: 12px; height: 12px;"></i>
            </button>
        </div>
    ` : '';
    
    return `
        <div class="${messageClasses.join(' ')}" data-message-id="${message.id}">
            <div class="d-flex gap-2 ${isOwn ? 'justify-content-end' : ''}">
                ${!isOwn && !isSystem ? `
                    <div class="message-avatar">
                        ${avatar}
                    </div>
                ` : ''}
                
                <div class="message-wrapper">
                    <div class="message-content">
                        ${content}
                    </div>
                    ${attachment}
                    
                    <div class="message-meta d-flex ${isOwn ? 'justify-content-end' : ''} align-items-center gap-2">
                        <small class="text-muted">
                            ${!isSystem && !isOwn ? `<strong>${escapeHtml(message.username)}</strong> • ` : ''}
                            ${message.created_at}
                            ${isDeleted && message.deleted_at ? ` • Deleted ${message.deleted_at}` : ''}
                        </small>
                        ${actions}
                    </div>
                </div>
                
                ${isOwn && !isSystem ? `
                    <div class="message-avatar">
                        ${avatar}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

// Render attachment
function renderAttachment(filename) {
    if (!filename) return '';
    
    const isImage = /\.(jpg|jpeg|png|gif)$/i.test(filename);
    const url = `uploads/${filename}`;
    
    if (isImage) {
        return `
            <div class="message-attachment mt-2">
                <img src="${url}" 
                     alt="Attachment" 
                     class="img-thumbnail" 
                     style="max-width: 250px; max-height: 200px; cursor: pointer;"
                     onclick="showImageModal('${url}', '${filename}')">
            </div>
        `;
    } else {
        return `
            <div class="message-attachment mt-2">
                <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i data-lucide="paperclip" class="me-1" style="width: 14px; height: 14px;"></i>
                    ${filename}
                </a>
            </div>
        `;
    }
}

// Send message (improved with better feedback)
function sendMessage() {
    const messageInput = $('#message-input');
    const messageText = messageInput.val().trim();
    const fileInput = $('#file-input')[0];
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
    
    if ((!messageText && !hasFile) || !currentRoomId || !canSend) {
        return;
    }
    
    canSend = false;
    
    // Disable input temporarily
    messageInput.prop('disabled', true);
    $('#send-btn').prop('disabled', true);
    
    // Show sending indicator
    const sendBtn = $('#send-btn');
    const originalContent = sendBtn.html();
    sendBtn.html('<div class="spinner-border spinner-border-sm" role="status"></div>');
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('room_id', currentRoomId);
    formData.append('message', messageText);
    
    if (hasFile) {
        formData.append('attachment', fileInput.files[0]);
        showUploadProgress();
    }
    
    $.ajax({
        url: 'handlers/message_handler.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new XMLHttpRequest();
            if (hasFile) {
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $('#upload-progress-bar').css('width', percentComplete + '%');
                    }
                });
            }
            return xhr;
        },
        success: function(response) {
            if (response.success) {
                // Clear input
                messageInput.val('').trigger('input');
                if (fileInput) fileInput.value = '';
                
                // Hide upload progress
                hideUploadProgress();
                
                // Immediately poll for new messages
                setTimeout(() => {
                    pollMessages();
                }, 100);
                
                if (typeof window.showToast === 'function') {
                    window.showToast('Message sent', 'success');
                }
            } else {
                if (typeof window.showToast === 'function') {
                    window.showToast(response.error || 'Failed to send message', 'danger');
                } else {
                    alert(response.error || 'Failed to send message');
                }
            }
        },
        error: function() {
            if (typeof window.showToast === 'function') {
                window.showToast('Failed to send message', 'danger');
            }
        },
        complete: function() {
            hideUploadProgress();
            
            // Restore send button
            sendBtn.html(originalContent);
            
            // Re-enable input
            messageInput.prop('disabled', false);
            $('#send-btn').prop('disabled', false);
            
            // Re-enable sending after 1 second
            setTimeout(() => {
                canSend = true;
            }, 1000);
        }
    });
}

// Show upload progress
function showUploadProgress() {
    $('#upload-progress').show();
    $('#upload-progress-bar').css('width', '0%');
}

// Hide upload progress
function hideUploadProgress() {
    $('#upload-progress').hide();
    $('#upload-progress-bar').css('width', '0%');
}

// Delete message
function deleteMessage(messageId) {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }
    
    $.post('handlers/message_handler.php', {
        action: 'delete',
        message_id: messageId
    }, function(response) {
        if (response.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('Message deleted', 'success');
            }
            
            // Update message in UI
            const messageElement = $(`.message[data-message-id="${messageId}"]`);
            messageElement.addClass('message-deleted');
            messageElement.find('.message-content').html('<em class="text-muted">This message was deleted</em>');
            messageElement.find('.message-actions').remove();
            
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(response.error || 'Failed to delete message', 'danger');
            }
        }
    }, 'json');
}

// Poll for new messages (improved)
function pollMessages() {
    if (!currentRoomId) return;
    
    $.get('handlers/message_handler.php', {
        action: 'poll',
        room_id: currentRoomId,
        last_id: lastMessageId
    }, function(newMessages) {
        if (Array.isArray(newMessages) && newMessages.length > 0) {
            console.log('New messages received:', newMessages);
            appendMessages(newMessages);
        }
    }, 'json').catch(error => {
        console.error('Error polling messages:', error);
    });
}

// Start message polling
function startMessagePolling() {
    // Clear existing interval
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    if (currentRoomId) {
        pollingInterval = setInterval(function() {
            pollMessages();
        }, 2000); // Poll every 2 seconds
    }
}

// Stop message polling
function stopMessagePolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// Format message content with basic markdown-like formatting
function formatMessageContent(content) {
    if (!content) return '';
    
    return escapeHtml(content)
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code>$1</code>');
}

// Scroll to bottom of messages
function scrollToBottom(smooth = true) {
    const container = $('#messages')[0];
    if (container) {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto'
        });
    }
}

// Show image in modal
function showImageModal(src, filename) {
    const modalHtml = `
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i data-lucide="image" class="me-2"></i>
                            ${escapeHtml(filename)}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="${src}" alt="${escapeHtml(filename)}" class="img-fluid">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="${src}" download="${filename}" class="btn btn-primary">
                            <i data-lucide="download"></i>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    $('#imageModal').remove();
    
    // Add new modal
    $('body').append(modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Auto-resize message input
$(document).on('input', '#message-input', function() {
    this.style.height = 'auto';
    const maxHeight = 120;
    const newHeight = Math.min(this.scrollHeight, maxHeight);
    this.style.height = newHeight + 'px';
    
    // Update send button state
    const hasContent = this.value.trim().length > 0;
    const hasFile = $('#file-input')[0] && $('#file-input')[0].files.length > 0;
    $('#send-btn').prop('disabled', (!hasContent && !hasFile) || !canSend);
});

// File input change handler
$(document).on('change', '#file-input', function() {
    const hasContent = $('#message-input').val().trim().length > 0;
    const hasFile = this.files.length > 0;
    $('#send-btn').prop('disabled', (!hasContent && !hasFile) || !canSend);
    
    if (hasFile) {
        const file = this.files[0];
        const fileName = file.name;
        $('#file-preview').html(`
            <div class="d-flex align-items-center gap-2 p-2 bg-light border rounded">
                <i data-lucide="paperclip" class="text-muted" style="width: 16px; height: 16px;"></i>
                <span class="flex-grow-1">${fileName}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFileInput()">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                </button>
            </div>
        `).show();
        
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    } else {
        $('#file-preview').hide();
    }
});

// Clear file input
function clearFileInput() {
    $('#file-input').val('');
    $('#file-preview').hide();
    
    const hasContent = $('#message-input').val().trim().length > 0;
    $('#send-btn').prop('disabled', !hasContent || !canSend);
}

// Send message on form submit
$(document).on('submit', '#message-form', function(e) {
    e.preventDefault();
    sendMessage();
});

// Send button click handler
$(document).on('click', '#send-btn', function(e) {
    e.preventDefault();
    sendMessage();
});

// Make functions globally available
window.loadMessages = loadMessages;
window.renderMessages = renderMessages;
window.appendMessages = appendMessages;
window.renderMessage = renderMessage;
window.sendMessage = sendMessage;
window.deleteMessage = deleteMessage;
window.pollMessages = pollMessages;
window.startMessagePolling = startMessagePolling;
window.stopMessagePolling = stopMessagePolling;
window.scrollToBottom = scrollToBottom;
window.showImageModal = showImageModal;
window.clearFileInput = clearFileInput;