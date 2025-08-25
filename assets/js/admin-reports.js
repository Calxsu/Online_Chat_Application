// JS MODULE: admin
// Report Functions
function showReportModal(type, entityId, entityName) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay show';
    modal.id = 'reportModal';
    modal.innerHTML = `
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i data-lucide="flag" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                    Report ${type.charAt(0).toUpperCase() + type.slice(1)}
                </h3>
                <button class="modal-close" onclick="closeReportModal()">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>
            <form id="reportForm">
                <div style="margin-bottom: 1rem;">
                    <p style="color: var(--gray-600); margin-bottom: 1rem;">
                        You are reporting: <strong>${escapeHtml(entityName)}</strong>
                    </p>
                    
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Report</label>
                    <select id="report-reason" style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; margin-bottom: 1rem;" required>
                        <option value="">Select a reason...</option>
                        <option value="spam">Spam</option>
                        <option value="harassment">Harassment</option>
                        <option value="inappropriate_content">Inappropriate Content</option>
                        <option value="hate_speech">Hate Speech</option>
                        <option value="impersonation">Impersonation</option>
                        <option value="violence">Violence or Threats</option>
                        <option value="misinformation">Misinformation</option>
                        <option value="copyright">Copyright Violation</option>
                        <option value="other">Other</option>
                    </select>
                    
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Additional Details (Optional)</label>
                    <textarea id="report-details" placeholder="Provide additional context..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 8px; min-height: 100px; resize: vertical;"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="header-btn" onclick="closeReportModal()">Cancel</button>
                    <button type="submit" class="header-btn header-btn--danger">
                        <i data-lucide="flag" style="width: 14px; height: 14px;"></i>
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const reason = document.getElementById('report-reason').value;
        const details = document.getElementById('report-details').value.trim();

        if (!reason) {
            showToast('Please select a reason for the report', 'warning');
            return;
        }

        const fullReason = details ? `${reason}: ${details}` : reason;
        submitReport(type, entityId, fullReason);
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            if (document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        }, 300);
    }
}

function submitReport(type, entityId, reason) {
    const submitBtn = document.querySelector('#reportForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Submitting...';

    $.post('handlers/report_handler.php', {
            action: 'create',
            type: type,
            entity_id: entityId,
            reason: reason
        })
        .done(function(response) {
            if (response.success) {
                showToast('Report submitted successfully. Thank you for helping keep our community safe!', 'success');
                closeReportModal();
            } else {
                showToast(response.error || 'Failed to submit report', 'error');
            }
        })
        .fail(function() {
            showToast('Failed to submit report. Please try again.', 'error');
        })
        .always(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
}

function reportMessage(messageId, username) {
    showReportModal('message', messageId, `Message by ${username}`);
}

function reportUser(userId, username) {
    showReportModal('user', userId, `User: ${username}`);
}

function reportRoom(roomId, roomName) {
    showReportModal('room', roomId, `Room: ${roomName}`);
}

// Admin Panel Implementation
function showAdminPanel() {
    if (!window.isAdmin) {
        showToast('Access denied - Admin only', 'error');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal-overlay show';
    modal.id = 'adminModal';
    modal.innerHTML = `
        <div class="modal" style="max-width: 900px; max-height: 80vh;">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i data-lucide="shield" style="width: 20px; height: 20px; margin-right: 0.5rem;"></i>
                    Admin Panel
                </h3>
                <button class="modal-close" onclick="closeAdminModal()">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
            </div>
            <div style="max-height: 70vh; overflow-y: auto;">
                <div style="border-bottom: 1px solid var(--gray-200); margin-bottom: 1.5rem;">
                    <div style="display: flex; gap: 1rem; padding-bottom: 1rem;">
                        <button id="admin-tab-reports" class="header-btn header-btn--primary admin-tab-btn" onclick="showAdminReports()">
                            <i data-lucide="flag" style="width: 16px; height: 16px;"></i>
                            Reports
                        </button>
                        <button id="admin-tab-users" class="header-btn admin-tab-btn" onclick="showAdminUsers()">
                            <i data-lucide="users" style="width: 16px; height: 16px;"></i>
                            Users
                        </button>
                        <button id="admin-tab-rooms" class="header-btn admin-tab-btn" onclick="showAdminRooms()">
                            <i data-lucide="message-square" style="width: 16px; height: 16px;"></i>
                            Rooms
                        </button>
                    </div>
                </div>
                <div id="admin-content">
                    <div id="admin-loading" style="text-align: center; padding: 3rem;">
                        <i data-lucide="loader-2" style="width: 32px; height: 32px; animation: spin 1s linear infinite; margin-bottom: 1rem;"></i>
                        <p>Loading admin data...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    showAdminReports();

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function showAdminReports() {
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('header-btn--primary');
    });
    document.getElementById('admin-tab-reports').classList.add('header-btn--primary');

    document.getElementById('admin-content').innerHTML = `
        <div id="admin-loading" style="text-align: center; padding: 2rem;">
            <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
            <p>Loading reports...</p>
        </div>
    `;

    $.get('handlers/admin_handler.php?action=get_reports')
        .done(function(reports) {
            const content = document.getElementById('admin-content');
            if (reports.length === 0) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <i data-lucide="check-circle" style="width: 48px; height: 48px; margin-bottom: 1rem; color: var(--success);"></i>
                        <p><strong>No pending reports</strong><br>All reports have been processed.</p>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px; border-left: 4px solid var(--warning);">
                        <p style="color: var(--gray-700); font-size: 0.875rem; margin: 0;">
                            <i data-lucide="alert-triangle" style="width: 16px; height: 16px; margin-right: 0.5rem; color: var(--warning);"></i>
                            <strong style="color: var(--warning);">${reports.length}</strong> pending report${reports.length !== 1 ? 's' : ''} require your attention
                        </p>
                    </div>
                    ${reports.map(report => `
                        <div style="border: 1px solid var(--gray-200); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--white); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: var(--error); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i data-lucide="${getReportIcon(report.report_type)}" style="width: 20px; height: 20px;"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; color: var(--gray-900); font-size: 1.125rem;">
                                            ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)} Report
                                        </h4>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
                                            Reported ${formatReportDate(report.created_at)} by ${escapeHtml(report.reporter_name || 'Unknown')}
                                        </p>
                                    </div>
                                </div>
                                <span style="background: var(--error); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                    PENDING
                                </span>
                            </div>

                            <div style="background: var(--gray-50); border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                        <i data-lucide="target" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                        Reported Entity
                                    </label>
                                    <p style="margin: 0; color: var(--gray-900); font-weight: 500;">${escapeHtml(report.entity_name || 'Unknown')}</p>
                                </div>
                                
                                <div style="margin-bottom: ${report.message_content ? '1rem' : '0'};">
                                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                        <i data-lucide="message-circle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                        Reason for Report
                                    </label>
                                    <div style="background: var(--white); border: 1px solid var(--gray-200); border-radius: 6px; padding: 0.75rem;">
                                        <p style="margin: 0; color: var(--gray-900); font-weight: 500; line-height: 1.5;">
                                            "${escapeHtml(report.reason)}"
                                        </p>
                                    </div>
                                </div>

                                ${report.message_content ? `
                                <div>
                                    <label style="display: block; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; margin-bottom: 0.5rem;">
                                        <i data-lucide="message-square" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                        Reported Message ${report.room_name ? `(in "${escapeHtml(report.room_name)}")` : ''}
                                    </label>
                                    <div style="background: var(--white); border: 1px solid var(--gray-300); border-left: 4px solid var(--warning); border-radius: 6px; padding: 0.75rem;">
                                        <p style="margin: 0; color: var(--gray-800); font-style: italic; line-height: 1.5;">
                                            "${escapeHtml(report.message_content)}"
                                        </p>
                                        ${report.message_date ? `
                                        <p style="margin: 0.5rem 0 0 0; color: var(--gray-500); font-size: 0.8125rem;">
                                            <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                            Sent: ${new Date(report.message_date).toLocaleString()}
                                        </p>
                                        ` : ''}
                                    </div>
                                </div>
                                ` : ''}
                            </div>

                            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                                <button class="header-btn header-btn--danger" onclick="processReport(${report.id}, 'ban')" style="background: var(--error); color: white; border-color: var(--error);">
                                    <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                    Ban ${report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)}
                                </button>
                                ${report.report_type === 'user' ? `
                                <button class="header-btn" onclick="processReport(${report.id}, 'warning')" style="background: var(--warning); color: white; border-color: var(--warning);">
                                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                                    Warn User
                                </button>
                                ` : ''}
                                <button class="header-btn" onclick="processReport(${report.id}, 'reject')" style="background: var(--gray-500); color: white; border-color: var(--gray-500);">
                                    <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                    Reject
                                </button>
                            </div>
                        </div>
                    `).join('')}
                `;
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .fail(function() {
            document.getElementById('admin-content').innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--error);">
                    <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                    <p><strong>Failed to load reports</strong><br>Please try again later.</p>
                </div>
            `;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
}

function showAdminUsers() {
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('header-btn--primary');
    });
    document.getElementById('admin-tab-users').classList.add('header-btn--primary');

    document.getElementById('admin-content').innerHTML = `
        <div id="admin-loading" style="text-align: center; padding: 2rem;">
            <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
            <p>Loading users...</p>
        </div>
    `;

    $.get('handlers/admin_handler.php?action=get_users')
        .done(function(users) {
            const content = document.getElementById('admin-content');
            content.innerHTML = `
                <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                    <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                        <i data-lucide="users" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                        <strong>${users.length}</strong> registered user${users.length !== 1 ? 's' : ''}
                    </p>
                </div>
                ${users.map(user => `
                    <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 32px; height: 32px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                        ${escapeHtml(user.username).charAt(0).toUpperCase()}
                                    </div>
                                    ${escapeHtml(user.username)}
                                    ${user.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                </h4>
                                <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                    <i data-lucide="mail" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                    ${escapeHtml(user.email)}
                                </p>
                                <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                    <i data-lucide="alert-triangle" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                    ${user.warnings} warning${user.warnings !== 1 ? 's' : ''} • 
                                    <i data-lucide="calendar" style="width: 12px; height: 12px; margin-left: 0.5rem; margin-right: 0.25rem;"></i>
                                    Joined: ${new Date(user.created_at).toLocaleDateString()}
                                </p>
                            </div>
                            ${!user.is_banned && user.id != window.currentUserId ? `
                                <button class="header-btn header-btn--danger" onclick="banUser(${user.id}, '${escapeHtml(user.username).replace(/'/g, '\\\'')}')" title="Ban User">
                                    <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                    Ban User
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `).join('')}
            `;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .fail(function() {
            document.getElementById('admin-content').innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--error);">
                    <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                    <p>Failed to load users</p>
                </div>
            `;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
}

function showAdminRooms() {
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('header-btn--primary');
    });
    document.getElementById('admin-tab-rooms').classList.add('header-btn--primary');

    document.getElementById('admin-content').innerHTML = `
        <div id="admin-loading" style="text-align: center; padding: 2rem;">
            <i data-lucide="loader-2" style="width: 24px; height: 24px; animation: spin 1s linear infinite; margin-bottom: 0.5rem;"></i>
            <p>Loading rooms...</p>
        </div>
    `;

    $.get('handlers/admin_handler.php?action=get_rooms')
        .done(function(rooms) {
            const content = document.getElementById('admin-content');
            content.innerHTML = `
                <div style="margin-bottom: 1rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                    <p style="color: var(--gray-600); font-size: 0.875rem; margin: 0;">
                        <i data-lucide="message-square" style="width: 16px; height: 16px; margin-right: 0.5rem;"></i>
                        <strong>${rooms.length}</strong> room${rooms.length !== 1 ? 's' : ''} created
                    </p>
                </div>
                ${rooms.map(room => `
                    <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--gray-900); display: flex; align-items: center; gap: 0.5rem;">
                                    <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 16px; height: 16px;"></i>
                                    ${escapeHtml(room.name)}
                                    ${room.is_banned ? '<span style="color: var(--error); font-size: 0.75rem; font-weight: normal; background: var(--error); color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-left: 0.5rem;">BANNED</span>' : ''}
                                </h4>
                                <p style="margin: 0 0 0.25rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                    <i data-lucide="user" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                    Created by ${escapeHtml(room.creator_name)}
                                </p>
                                <p style="margin: 0; color: var(--gray-500); font-size: 0.8125rem;">
                                    <i data-lucide="calendar" style="width: 12px; height: 12px; margin-right: 0.25rem;"></i>
                                    Created: ${new Date(room.created_at).toLocaleDateString()} • 
                                    <span style="text-transform: capitalize;">${room.visibility}</span> room
                                </p>
                            </div>
                            ${!room.is_banned ? `
                                <button class="header-btn header-btn--danger" onclick="banRoom(${room.id}, '${escapeHtml(room.name).replace(/'/g, '\\\'')}')" title="Ban Room">
                                    <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                    Ban Room
                                </button>
                            ` : ''}
                        </div>
                    </div>
                `).join('')}
            `;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        })
        .fail(function() {
            document.getElementById('admin-content').innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--error);">
                    <i data-lucide="alert-triangle" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                    <p>Failed to load rooms</p>
                </div>
            `;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
}

function processReport(reportId, decision) {
    const actionNames = {
        'ban': 'ban this entity',
        'warning': 'issue a warning',
        'reject': 'reject this report'
    };

    const actionName = actionNames[decision] || decision;

    if (!confirm(`Are you sure you want to ${actionName}?`)) {
        return;
    }

    const notes = prompt('Add admin notes (optional):') || '';
    const submitBtn = event.target;
    const originalText = submitBtn.innerHTML;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i> Processing...';

    $.post('handlers/admin_handler.php', {
            action: 'process_report',
            report_id: reportId,
            decision: decision,
            notes: notes
        })
        .done(function(response) {
            if (response.success) {
                showToast(response.message || 'Report processed successfully', 'success');
                showAdminReports();
                // Refresh rooms to show ban status
                loadRooms();
            } else {
                showToast(response.error || 'Failed to process report', 'error');
            }
        })
        .fail(function() {
            showToast('Failed to process report', 'error');
        })
        .always(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
}

function banUser(userId, username) {
    if (!confirm(`Are you sure you want to permanently ban user "${username}"?\n\nThis will:\n• Ban the user permanently (cannot login again)\n• Ban all rooms they created\n• Delete all their messages\n\nThis action cannot be undone.`)) return;

    $.post('handlers/admin_handler.php', {
            action: 'ban_user',
            user_id: userId
        })
        .done(function(response) {
            if (response.success) {
                showToast(response.message || `User "${username}" has been permanently banned`, 'success');
                // Refresh the admin panel
                showAdminUsers();
                // Refresh rooms to show ban status
                loadRooms();
            } else {
                showToast(response.error || 'Failed to ban user', 'error');
            }
        })
        .fail(function() {
            showToast('Failed to ban user', 'error');
        });
}

function banRoom(roomId, roomName) {
    if (!confirm(`Are you sure you want to permanently ban room "${roomName}"?\n\nThis will prevent anyone from sending messages in this room.\n\nThis action cannot be undone.`)) return;

    $.post('handlers/admin_handler.php', {
            action: 'ban_room',
            room_id: roomId
        })
        .done(function(response) {
            if (response.success) {
                showToast(response.message || `Room "${roomName}" has been permanently banned`, 'success');
                // Refresh the admin panel and rooms list
                showAdminRooms();
                loadRooms();

                // If we're currently in the banned room, exit it
                if (currentRoomId == roomId) {
                    document.getElementById('chat-interface').style.display = 'none';
                    document.getElementById('welcome-screen').style.display = 'flex';
                    currentRoomId = null;
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                }
            } else {
                showToast(response.error || 'Failed to ban room', 'error');
            }
        })
        .fail(function() {
            showToast('Failed to ban room', 'error');
        });
}

function closeAdminModal() {
    const modal = document.getElementById('adminModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            if (document.body.contains(modal)) {
                document.body.removeChild(modal);
            }
        }, 300);
    }
}