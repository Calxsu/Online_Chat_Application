<div class="rooms-section">
    
    <!-- Loading State -->
    <div class="text-center py-3" id="rooms-loading" style="display: none;">
        <div class="spinner-border spinner-border-sm" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <small class="text-muted d-block mt-2">Loading rooms...</small>
    </div>
    
    <!-- Rooms List -->
    <div id="rooms-list">
        <!-- Dynamic rooms will be loaded here -->
        <div class="text-center py-4">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <p class="text-muted mt-2">Loading rooms...</p>
        </div>
    </div>
    
</div>

<!-- Create Room Modal -->
<div class="modal fade" id="createRoomModal" tabindex="-1" aria-labelledby="createRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createRoomModalLabel">
                    <i data-lucide="plus-circle" class="me-2"></i>
                    Create New Room
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="createRoomForm">
                <div class="modal-body">
                    
                    <!-- Room Name -->
                    <div class="mb-3">
                        <label for="new-room-name" class="form-label">
                            <i data-lucide="hash" class="me-1" style="width: 16px; height: 16px;"></i>
                            Room Name
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="new-room-name" 
                               placeholder="Enter room name..." 
                               required 
                               minlength="3" 
                               maxlength="50">
                        <div class="form-text">3-50 characters, descriptive and friendly</div>
                    </div>
                    
                    <!-- Room Visibility -->
                    <div class="mb-3">
                        <label for="room-visibility" class="form-label">
                            <i data-lucide="globe" class="me-1" style="width: 16px; height: 16px;"></i>
                            Visibility
                        </label>
                        <select class="form-control" id="room-visibility">
                            <option value="public">
                                <i data-lucide="globe" class="me-2"></i>
                                Public - Anyone can join
                            </option>
                            <option value="private">
                                <i data-lucide="lock" class="me-2"></i>
                                Private - Invitation only
                            </option>
                        </select>
                        <div class="form-text">
                            Public rooms are discoverable by all users. Private rooms require invitations.
                        </div>
                    </div>
                    
                    <!-- Room Guidelines -->
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i data-lucide="info" class="me-2"></i>
                                Room Guidelines
                            </h6>
                            <ul class="mb-0">
                                <li>Keep conversations respectful and on-topic</li>
                                <li>Use clear, descriptive room names</li>
                                <li>Private rooms are perfect for team discussions</li>
                                <li>Public rooms help build community connections</li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-lucide="x"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="plus-circle"></i>
                        Create Room
                    </button>
                </div>
            </form>
            
        </div>
    </div>
</div>

<script>
// Initialize Lucide icons when modal is shown
$('#createRoomModal').on('shown.bs.modal', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Form validation
$('#createRoomForm').on('submit', function(e) {
    e.preventDefault();
    
    const roomName = $('#new-room-name').val().trim();
    if (roomName.length < 3) {
        if (typeof window.showToast === 'function') {
            window.showToast('Room name must be at least 3 characters long', 'warning');
        }
        return;
    }
    
    // Call the createRoom function from room.js
    if (typeof createRoom === 'function') {
        createRoom();
    }
});

// Real-time character counter for room name
$('#new-room-name').on('input', function() {
    const length = this.value.length;
    const formText = $(this).siblings('.form-text');
    
    if (length < 3) {
        formText.text(`${3 - length} more characters needed (3-50 characters)`).removeClass('text-success').addClass('text-warning');
    } else if (length > 50) {
        formText.text(`${length - 50} characters over limit (3-50 characters)`).removeClass('text-success').addClass('text-danger');
    } else {
        formText.text(`✓ ${length}/50 characters`).removeClass('text-warning text-danger').addClass('text-success');
    }
});
</script>

<style>
.rooms-section {
    max-height: 400px;
    overflow-y: auto;
}

.room-item {
    padding: 0.75rem;
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
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.room-item.joined {
    border-color: var(--success-color);
    background: rgba(40, 167, 69, 0.1);
}

.room-name {
    color: var(--text-primary);
    font-weight: 600;
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

.message-content {
    background: var(--bg-secondary);
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    position: relative;
    word-wrap: break-word;
    color: var(--text-primary);
}

.message-own .message-content {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.message-meta {
    font-size: 0.75rem;
    margin-top: 0.25rem;
    opacity: 0.7;
    color: var(--text-muted);
}

.message-actions {
    opacity: 0;
    transition: opacity 0.2s;
}

.message:hover .message-actions {
    opacity: 1;
}

.message-deleted .message-content {
    background: var(--bg-tertiary);
    color: var(--text-muted);
    border-style: dashed;
}
</style>