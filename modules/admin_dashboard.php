<div class="admin-dashboard-compact">
    
    <!-- Dashboard Stats -->
    <div class="admin-stats mb-4">
        <div class="row g-3">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i data-lucide="users" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="total-users">0</div>
                        <div class="stat-label">Users</div>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i data-lucide="message-square" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="total-rooms">0</div>
                        <div class="stat-label">Rooms</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mt-1">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i data-lucide="flag" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number" id="pending-reports">0</div>
                        <div class="stat-label">Reports</div>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i data-lucide="activity" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">1</div>
                        <div class="stat-label">Online</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reports Section -->
    <div class="admin-section">
        <div class="admin-section-header">
            <h6 class="mb-0">
                <i data-lucide="flag" class="me-2" style="width: 16px; height: 16px;"></i>
                Pending Reports
                <span class="badge bg-warning ms-2" id="reports-count">0</span>
            </h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="loadReports()" title="Refresh">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
            </button>
        </div>
        
        <div class="admin-section-body">
            
            <!-- Reports Loading -->
            <div class="text-center py-3" id="reports-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <small class="text-muted d-block mt-1">Loading reports...</small>
            </div>
            
            <!-- Reports List -->
            <div id="reports-list" class="admin-list">
                <!-- Dynamic reports will be loaded here -->
            </div>
            
            <!-- Reports Empty State -->
            <div class="empty-state" id="reports-empty" style="display: none;">
                <div class="text-center py-3">
                    <i data-lucide="check-circle" style="width: 32px; height: 32px; color: var(--success-color);" class="mb-2"></i>
                    <h6>No pending reports</h6>
                    <small class="text-muted">All reports have been reviewed!</small>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="admin-section">
        <div class="admin-section-header">
            <h6 class="mb-0">
                <i data-lucide="zap" class="me-2" style="width: 16px; height: 16px;"></i>
                Quick Actions
            </h6>
        </div>
        
        <div class="admin-section-body">
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="updateAdminDashboard()">
                    <i data-lucide="refresh-cw" class="me-2" style="width: 14px; height: 14px;"></i>
                    Refresh Dashboard
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="exportData()">
                    <i data-lucide="download" class="me-2" style="width: 14px; height: 14px;"></i>
                    Export Data
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="showSystemSettings()">
                    <i data-lucide="settings" class="me-2" style="width: 14px; height: 14px;"></i>
                    System Settings
                </button>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="admin-section">
        <div class="admin-section-header">
            <h6 class="mb-0">
                <i data-lucide="clock" class="me-2" style="width: 16px; height: 16px;"></i>
                Recent Activity
            </h6>
        </div>
        
        <div class="admin-section-body">
            <div id="activity-list" class="admin-list">
                <div class="admin-list-item">
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-2">
                            <i data-lucide="shield" class="text-primary" style="width: 16px; height: 16px;"></i>
                            <strong>Admin panel loaded</strong>
                        </div>
                        <small class="text-muted">System initialized • <?php echo date('H:i'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- Export Data Modal -->
<div class="modal fade" id="exportDataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="download" class="me-2"></i>
                    Export Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="export-users" checked>
                    <label class="form-check-label" for="export-users">
                        <i data-lucide="users" class="me-2" style="width: 16px; height: 16px;"></i>
                        User Data
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="export-rooms" checked>
                    <label class="form-check-label" for="export-rooms">
                        <i data-lucide="message-square" class="me-2" style="width: 16px; height: 16px;"></i>
                        Room Data
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="export-reports">
                    <label class="form-check-label" for="export-reports">
                        <i data-lucide="flag" class="me-2" style="width: 16px; height: 16px;"></i>
                        Reports Data
                    </label>
                </div>
                
                <div class="mb-3">
                    <label for="export-format" class="form-label">Export Format</label>
                    <select class="form-control" id="export-format">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                        <option value="xlsx">Excel (XLSX)</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="processExport()">
                    <i data-lucide="download" class="me-1"></i>
                    Export Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- System Settings Modal -->
<div class="modal fade" id="systemSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-lucide="settings" class="me-2"></i>
                    System Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="alert alert-info">
                    <i data-lucide="info" class="me-2"></i>
                    System settings would be implemented in a full version
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="maintenance-mode">
                    <label class="form-check-label" for="maintenance-mode">
                        <i data-lucide="tool" class="me-2"></i>
                        Maintenance Mode
                    </label>
                    <div class="form-text">Prevent new user registrations</div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="public-rooms-only">
                    <label class="form-check-label" for="public-rooms-only">
                        <i data-lucide="globe" class="me-2"></i>
                        Public Rooms Only
                    </label>
                    <div class="form-text">Disable private room creation</div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">
                    <i data-lucide="save" class="me-1"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Export data functionality
function exportData() {
    const modal = new bootstrap.Modal(document.getElementById('exportDataModal'));
    modal.show();
}

function processExport() {
    const format = document.getElementById('export-format').value;
    const includeUsers = document.getElementById('export-users').checked;
    const includeRooms = document.getElementById('export-rooms').checked;
    const includeReports = document.getElementById('export-reports').checked;
    
    if (!includeUsers && !includeRooms && !includeReports) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please select at least one data type to export', 'warning');
        }
        return;
    }
    
    // In a real application, this would make an API call
    if (typeof window.showToast === 'function') {
        window.showToast(`Data export in ${format.toUpperCase()} format has been prepared`, 'success');
    } else {
        alert(`Data export in ${format.toUpperCase()} format has been prepared`);
    }
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('exportDataModal'));
    modal.hide();
}

// System settings functionality
function showSystemSettings() {
    const modal = new bootstrap.Modal(document.getElementById('systemSettingsModal'));
    modal.show();
}

// Initialize admin dashboard
$(document).ready(function() {
    if (window.isAdmin) {
        // Initialize dashboard
        if (typeof updateAdminDashboard === 'function') {
            updateAdminDashboard();
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
});

// Update modal icons when shown
$('.modal').on('shown.bs.modal', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<style>
.admin-dashboard-compact {
    font-size: 0.9rem;
}

.admin-stats .stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: var(--transition);
}

.admin-stats .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.admin-stats .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.admin-stats .stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text-primary);
    line-height: 1;
}

.admin-stats .stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.25rem;
}

.admin-section {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    overflow: hidden;
}

.admin-section-header {
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-color);
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-section-body {
    padding: 1rem;
    max-height: 300px;
    overflow-y: auto;
}

.admin-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.admin-list-item {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.admin-list-item:hover {
    border-color: var(--primary-color);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .admin-stats .stat-card {
        padding: 0.75rem;
        flex-direction: column;
        text-align: center;
    }
    
    .admin-stats .stat-icon {
        width: 32px;
        height: 32px;
    }
    
    .admin-stats .stat-number {
        font-size: 1.25rem;
    }
    
    .admin-section-body {
        padding: 0.75rem;
        max-height: 200px;
    }
}
</style>