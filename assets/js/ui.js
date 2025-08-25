// Enhanced Toast notification function
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    // Split message into title and content
    const messages = message.split('. ');
    const title = messages[0];
    const content = messages.slice(1).join('. ');

    toast.innerHTML = `
        <div class="toast-icon">
            <i data-lucide="${getToastIcon(type)}" style="width: 20px; height: 20px;"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            ${content ? `<div class="toast-message">${content}</div>` : ''}
        </div>
        <button class="toast-close" onclick="this.parentElement.classList.remove('show'); setTimeout(() => this.parentElement.remove(), 300);">
            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
        </button>
    `;

    container.appendChild(toast);

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 300);
    }, 5000);
}

// ENHANCED MOBILE FUNCTIONALITY
function initializeMobileFeatures() {
    console.log('Initializing mobile features...');
    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', closeMobileSidebar);
        document.body.appendChild(overlay);

        console.log('Sidebar overlay added');
    }
}

function toggleMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const isOpen = sidebar.classList.contains('show');

    console.log(isOpen ? 'Closing' : 'Opening', 'mobile sidebar');

    if (isOpen) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        const mobileFooter = document.querySelector('.sidebar-mobile-footer');
        if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
    } else {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        const mobileFooter = document.querySelector('.sidebar-mobile-footer');
        if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'false');
    }
}

function closeMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
    const mobileFooter = document.querySelector('.sidebar-mobile-footer');
    if (mobileFooter) mobileFooter.setAttribute('aria-hidden', 'true');
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