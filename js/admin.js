// Admin Functions (with Lucide icons and professional styling)

// Start admin polling
function startAdminPolling() {
    if (adminPollingInterval) {
        clearInterval(adminPollingInterval);
    }
    
    adminPollingInterval = setInterval(function() {
        loadReports(true);
        updateAdminDashboard();
    }, 5000); // Poll every 5 seconds
}

// Load reports with details
function loadReports(silent = false) {
    $.get('handlers/admin_handler.php', { action: 'get_reports' }, function(reports) {
        if (Array.isArray(reports)) {
            // Check for new reports
            if (!silent && reports.length > lastReportCount && lastReportCount > 0) {
                if (typeof window.showToast === 'function') {
                    window.showToast(`${reports.length - lastReportCount} new report${reports.length - lastReportCount > 1 ? 's' : ''} received!`, 'info');
                } else {
                    console.log(`${reports.length - lastReportCount} new report${reports.length - lastReportCount > 1 ? 's' : ''} received!`);
                }
            }
            
            lastReportCount = reports.length;
            
            // Update reports count in header
            $('#pending-reports').text(reports.length);
            
            // Professional rendering for reports
            const reportsList = reports.map(function(report) {
                return `
                    <div class="admin-list-item" data-report-id="${report.id}">
                        <div class="p-3">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i data-lucide="flag" class="text-warning" style="width: 16px; height: 16px;"></i>
                                        <strong>${escapeHtml(report.entity_name || 'Unknown Entity')}</strong>
                                        <span class="badge bg-secondary">${report.report_type}</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i data-lucide="user" class="me-1" style="width: 12px; height: 12px;"></i>
                                            <strong>Reported by:</strong> ${escapeHtml(report.reporter_name)}
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i data-lucide="message-square" class="me-1" style="width: 12px; height: 12px;"></i>
                                            <strong>Reason:</strong> ${escapeHtml(report.reason)}
                                        </small>
                                    </div>
                                    <div>
                                        <small class="text-muted">
                                            <i data-lucide="clock" class="me-1" style="width: 12px; height: 12px;"></i>
                                            ${formatDate(report.created_at)}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-sm btn-danger" onclick="processReportDecision(${report.id}, 'ban')" title="Ban/Remove">
                                    <i data-lucide="ban" style="width: 14px; height: 14px;"></i>
                                    Ban
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="processReportDecision(${report.id}, 'warning')" title="Issue Warning">
                                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                                    Warn
                                </button>
                                <button class="btn btn-sm btn-success" onclick="processReportDecision(${report.id}, 'reject')" title="Reject Report">
                                    <i data-lucide="x-circle" style="width: 14px; height: 14px;"></i>
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            const reportsContainer = $('#reports-list');
            if (reports.length > 0) {
                reportsContainer.html(reportsList);
                $('#reports-empty').hide();
            } else {
                reportsContainer.html('');
                $('#reports-empty').show();
            }
            
            $('#reports-loading').hide();
            reportsContainer.show();
            
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }, 'json').fail(function() {
        console.error('Failed to load reports');
        $('#reports-loading').hide();
        $('#reports-list').html(`
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" class="me-2"></i>
                Failed to load reports
            </div>
        `);
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
}

// Load users for admin management
function loadUsers() {
    if (window.allUsers && Array.isArray(window.allUsers)) {
        const usersList = window.allUsers.map(function(user) {
            return `
                <div class="admin-list-item" data-user-id="${user.id}">
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                            <strong>${escapeHtml(user.username)}</strong>
                            ${user.is_banned ? '<span class="badge bg-danger ms-2">Banned</span>' : ''}
                            ${user.warnings > 0 ? `<span class="badge bg-warning ms-2">${user.warnings} warnings</span>` : ''}
                        </div>
                        <small class="text-muted">
                            <i data-lucide="mail" class="me-1" style="width: 12px; height: 12px;"></i>
                            ${escapeHtml(user.email)} • 
                            <i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i>
                            Joined ${formatDate(user.created_at)}
                        </small>
                    </div>
                </div>
            `;
        }).join('');
        
        const usersContainer = $('#users-list');
        if (window.allUsers.length > 0) {
            usersContainer.html(usersList);
            $('#users-count').text(window.allUsers.length);
            $('#total-users').text(window.allUsers.length);
        } else {
            usersContainer.html(`
                <div class="text-center text-muted py-4">
                    <i data-lucide="users" style="width: 24px; height: 24px;" class="mb-2"></i>
                    <div>No users found</div>
                </div>
            `);
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Load rooms for admin management  
function loadRoomsAdmin() {
    if (window.allRooms && Array.isArray(window.allRooms)) {
        const roomsList = window.allRooms.map(function(room) {
            return `
                <div class="admin-list-item" data-room-id="${room.id}">
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i data-lucide="${room.visibility === 'private' ? 'lock' : 'globe'}" style="width: 16px; height: 16px;"></i>
                            <strong>${escapeHtml(room.name)}</strong>
                            ${room.is_banned ? '<span class="badge bg-danger ms-2">Banned</span>' : ''}
                            <span class="badge bg-secondary ms-2">${room.visibility}</span>
                        </div>
                        <small class="text-muted">
                            <i data-lucide="user" class="me-1" style="width: 12px; height: 12px;"></i>
                            Created by ${escapeHtml(room.creator_name)} • 
                            <i data-lucide="calendar" class="me-1" style="width: 12px; height: 12px;"></i>
                            ${formatDate(room.created_at)}
                        </small>
                    </div>
                </div>
            `;
        }).join('');
        
        const roomsContainer = $('#rooms-admin-list');
        if (window.allRooms.length > 0) {
            roomsContainer.html(roomsList);
            $('#rooms-admin-count').text(window.allRooms.length);
            $('#total-rooms').text(window.allRooms.length);
        } else {
            roomsContainer.html(`
                <div class="text-center text-muted py-4">
                    <i data-lucide="message-square" style="width: 24px; height: 24px;" class="mb-2"></i>
                    <div>No rooms found</div>
                </div>
            `);
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Process report decision
function processReportDecision(reportId, decision, notes = '') {
    if (!notes && decision !== 'reject') {
        notes = prompt('Enter admin notes (optional):') || '';
    }
    
    if (decision === 'ban' && !confirm('Are you sure you want to ban/remove this content? This action cannot be undone.')) {
        return;
    }
    
    $.post('handlers/admin_handler.php', {
        action: 'process_report',
        report_id: reportId,
        decision: decision,
        notes: notes
    }, function(response) {
        if (response.success) {
            if (typeof window.showToast === 'function') {
                window.showToast(response.message || 'Report processed successfully', 'success');
            } else {
                alert(response.message || 'Report processed successfully');
            }
            
            // Remove the report from the list
            $(`.admin-list-item[data-report-id="${reportId}"]`).fadeOut(function() {
                $(this).remove();
                
                // Update counts
                lastReportCount--;
                $('#pending-reports').text(lastReportCount);
                
                // Show empty state if no more reports
                if (lastReportCount === 0) {
                    $('#reports-list').html('');
                    $('#reports-empty').show();
                }
            });
            
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(response.error || 'Failed to process report', 'danger');
            } else {
                alert(response.error || 'Failed to process report');
            }
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Failed to process report', 'danger');
        } else {
            alert('Failed to process report');
        }
    });
}

// Update admin dashboard with fresh data
function updateAdminDashboard() {
    loadReports(true);
    loadUsers();
    loadRoomsAdmin();
    
    // Update activity list
    const activityList = $('#activity-list');
    if (activityList.length) {
        const currentTime = new Date().toLocaleTimeString();
        
        // Add a recent activity item
        const newActivity = `
            <div class="admin-list-item">
                <div class="p-3">
                    <div class="d-flex align-items-center gap-2">
                        <i data-lucide="activity" class="text-info" style="width: 16px; height: 16px;"></i>
                        <strong>Dashboard refreshed</strong>
                    </div>
                    <small class="text-muted">System updated at ${currentTime}</small>
                </div>
            </div>
        `;
        
        // Add to top and remove old items to keep list manageable
        activityList.prepend(newActivity);
        const activityItems = activityList.find('.admin-list-item');
        if (activityItems.length > 5) {
            activityItems.slice(5).remove();
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Initialize admin panel
function initAdminPanel() {
    if (window.isAdmin) {
        loadReports();
        updateAdminDashboard();
        startAdminPolling();
    }
}

// Initialize when DOM is ready
$(document).ready(function() {
    if (window.isAdmin) {
        initAdminPanel();
    }
});

// Make functions globally available
window.startAdminPolling = startAdminPolling;
window.loadReports = loadReports;
window.loadUsers = loadUsers;
window.loadRoomsAdmin = loadRoomsAdmin;
window.processReportDecision = processReportDecision;
window.updateAdminDashboard = updateAdminDashboard;
window.initAdminPanel = initAdminPanel;