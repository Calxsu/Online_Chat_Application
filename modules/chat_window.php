<div class="chat-window h-100 d-flex flex-column">
    
    <!-- Chat Header -->
    <div class="chat-header p-3 border-bottom" style="background: var(--bg-secondary);">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="chat-room-avatar">
                    <i data-lucide="hash" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h5 class="mb-0" id="current-room-name">Select a room</h5>
                    <small class="text-muted" id="current-room-status">Choose a room to start chatting</small>
                </div>
            </div>
            
            <div class="chat-actions d-flex gap-2" id="chat-actions" style="display: none;">
                <button class="btn btn-outline-secondary btn-sm" title="Room info">
                    <i data-lucide="info" style="width: 16px; height: 16px;"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm" 
                            type="button" 
                            data-bs-toggle="dropdown">
                        <i data-lucide="more-vertical" style="width: 16px; height: 16px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="leaveCurrentRoom()">
                            <i data-lucide="log-out" class="me-2" style="width: 16px; height: 16px;"></i>
                            Leave Room
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-warning" href="#" onclick="reportCurrentRoom()">
                            <i data-lucide="flag" class="me-2" style="width: 16px; height: 16px;"></i>
                            Report Room
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Messages Container (Fixed sizing) -->
    <div class="messages-container flex-grow-1 overflow-auto p-3" 
         id="messages" 
         style="height: 0; min-height: 300px; background: var(--bg-primary);">
        
        <!-- Default state when no room selected -->
        <div class="text-center py-5" id="no-room-selected">
            <i data-lucide="message-square" style="width: 64px; height: 64px;" class="text-muted mb-3"></i>
            <h4>Welcome to ChatApp</h4>
            <p class="text-muted">Select a room from the sidebar to start chatting!</p>
        </div>
        
    </div>
    
    <!-- Message Input (Fixed height) -->
    <div class="message-input-container p-3 border-top" style="background: var(--bg-secondary); min-height: 80px;">
        
        <!-- File Preview -->
        <div id="file-preview" class="mb-2" style="display: none;">
            <!-- File preview will be shown here -->
        </div>
        
        <!-- Upload Progress -->
        <div class="file-upload-progress mb-2" id="upload-progress" style="display: none;">
            <div class="d-flex align-items-center gap-2">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="flex-grow-1">
                    <small class="text-muted">Uploading file...</small>
                    <div class="progress mt-1" style="height: 4px;">
                        <div class="progress-bar" style="width: 0%" id="upload-progress-bar"></div>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-danger" id="cancel-upload" title="Cancel upload">
                    <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                </button>
            </div>
        </div>
        
        <form id="message-form" class="d-flex gap-2 align-items-end">
            <div class="flex-grow-1">
                <textarea class="form-control" 
                          id="message-input" 
                          placeholder="Select a room to start messaging..." 
                          rows="1"
                          maxlength="2000"
                          disabled
                          style="resize: none; max-height: 120px;"></textarea>
            </div>
            
            <div class="d-flex gap-1">
                <!-- File Upload -->
                <input type="file" 
                       id="file-input" 
                       class="d-none" 
                       accept="image/*,.pdf,.doc,.docx,.txt"
                       title="Upload file (max 5MB)">
                
                <button type="button" 
                        class="btn btn-outline-secondary" 
                        id="attach-btn"
                        disabled
                        title="Attach file"
                        onclick="document.getElementById('file-input').click()">
                    <i data-lucide="paperclip" style="width: 16px; height: 16px;"></i>
                </button>
                
                <button type="submit" 
                        class="btn btn-primary" 
                        id="send-btn"
                        disabled>
                    <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </form>
    </div>
    
</div>

<script>
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

// Leave current room
function leaveCurrentRoom() {
    if (currentRoomId && currentRoomInfo) {
        if (typeof leaveRoom === 'function') {
            leaveRoom(currentRoomId, currentRoomInfo.name);
        }
    }
}

// Report current room
function reportCurrentRoom() {
    if (currentRoomId && window.reportEntity) {
        window.reportEntity('room', currentRoomId);
    }
}

// Reset chat to default state
function resetChat() {
    $('#current-room-name').text('Select a room');
    $('#current-room-status').text('Choose a room to start chatting');
    $('#chat-actions').hide();
    
    // Disable message input
    $('#message-input').prop('disabled', true).attr('placeholder', 'Select a room to start messaging...').val('');
    $('#send-btn, #attach-btn').prop('disabled', true);
    
    // Show no room selected state
    $('#no-room-selected').show();
    
    // Clear messages
    $('#messages').html($('#no-room-selected')[0].outerHTML);
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// File upload validation
$('#file-input').on('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 
        'application/pdf', 'text/plain',
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (file.size > maxSize) {
        if (typeof window.showToast === 'function') {
            window.showToast('File size must be less than 5MB', 'warning');
        } else {
            alert('File size must be less than 5MB');
        }
        this.value = '';
        return;
    }
    
    if (!allowedTypes.includes(file.type)) {
        if (typeof window.showToast === 'function') {
            window.showToast('File type not allowed. Only images, PDF, and text documents are supported.', 'warning');
        } else {
            alert('File type not allowed. Only images, PDF, and text documents are supported.');
        }
        this.value = '';
        return;
    }
});

// Cancel upload
$('#cancel-upload').on('click', function() {
    // In a real implementation, you'd cancel the XMLHttpRequest here
    $('#upload-progress').hide();
    $('#file-input').val('');
    $('#file-preview').hide();
});

// Auto-scroll when messages are updated
$(document).on('messagesUpdated', function() {
    const container = document.getElementById('messages');
    if (container) {
        const isScrolledToBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 50;
        if (isScrolledToBottom) {
            setTimeout(() => {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }, 100);
        }
    }
});

// Initialize Lucide icons when DOM is ready
$(document).ready(function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Make functions globally available
window.updateChatHeader = updateChatHeader;
window.resetChat = resetChat;
window.leaveCurrentRoom = leaveCurrentRoom;
window.reportCurrentRoom = reportCurrentRoom;
</script>

<style>
.chat-window {
    background: var(--bg-primary);
    height: calc(100vh - 120px);
}

.chat-header {
    flex-shrink: 0;
    min-height: 70px;
}

.chat-room-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.message {
    margin-bottom: 0.75rem;
    max-width: 75%;
    clear: both;
}

.message-own {
    margin-left: auto;
    text-align: right;
}

.message-avatar {
    width: 32px;
    height: 32px;
    background: var(--secondary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: bold;
    flex-shrink: 0;
}

.message-own .message-avatar {
    background: var(--primary-color);
}

.message-content {
    background: var(--bg-secondary);
    padding: 0.75rem 1rem;
    border-radius: 18px;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    position: relative;
    word-wrap: break-word;
    color: var(--text-primary);
    display: inline-block;
    max-width: 100%;
}

.message-own .message-content {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.message-meta {
    font-size: 0.7rem;
    margin-top: 0.25rem;
    opacity: 0.8;
    color: var(--text-muted);
}

.message-actions {
    opacity: 0;
    transition: opacity 0.2s;
    margin-top: 0.25rem;
}

.message:hover .message-actions {
    opacity: 1;
}

.message-deleted .message-content {
    background: var(--bg-tertiary);
    color: var(--text-muted);
    border-style: dashed;
    font-style: italic;
}

.message-input-container {
    flex-shrink: 0;
    border-top: 1px solid var(--border-color);
}

.file-upload-progress {
    background: rgba(0, 123, 255, 0.1);
    padding: 0.5rem;
    border-radius: 0.375rem;
    border: 1px solid rgba(0, 123, 255, 0.2);
}

.message-attachment img {
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    max-width: 100%;
    height: auto;
}

.message-attachment img:hover {
    transform: scale(1.02);
}

/* Fixed alert styling */
.alert {
    margin: 0.5rem 0;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.alert .btn {
    margin-left: 0.5rem;
}

.alert i[data-lucide] {
    vertical-align: middle;
}

/* Better scrollbar for messages */
.messages-container::-webkit-scrollbar {
    width: 6px;
}

.messages-container::-webkit-scrollbar-track {
    background: var(--bg-tertiary);
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted);
}

@media (max-width: 768px) {
    .chat-window {
        height: calc(100vh - 60px);
    }
    
    .message {
        max-width: 90%;
    }
    
    .chat-header {
        padding: 0.75rem !important;
        min-height: 60px;
    }
    
    .messages-container {
        padding: 0.75rem !important;
    }
    
    .message-input-container {
        padding: 0.75rem !important;
        min-height: 70px !important;
    }
    
    .message-content {
        padding: 0.5rem 0.75rem;
        border-radius: 16px;
    }
    
    .alert {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Ensure proper flex layout */
.d-flex.flex-column {
    height: 100%;
}

.flex-grow-1 {
    flex: 1 1 auto;
    min-height: 0;
}
</style>