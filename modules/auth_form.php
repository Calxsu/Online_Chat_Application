<div class="auth-form-container p-4">
    
    <!-- Header -->
    <div class="text-center mb-4">
        <div class="auth-logo mb-3">
            <i data-lucide="message-square" style="width: 40px; height: 40px; color: white;"></i>
        </div>
        <h2 id="auth-title">Welcome Back</h2>
        <p class="text-muted">Sign in to continue to ChatApp</p>
    </div>
    
    <!-- Tab Navigation -->
    <div class="nav nav-pills nav-justified mb-4" role="tablist">
        <button class="nav-link active" 
                id="login-tab" 
                type="button"
                onclick="showLoginForm()">
            <i data-lucide="log-in" class="me-2" style="width: 16px; height: 16px;"></i>
            Sign In
        </button>
        <button class="nav-link" 
                id="register-tab" 
                type="button"
                onclick="showRegisterForm()">
            <i data-lucide="user-plus" class="me-2" style="width: 16px; height: 16px;"></i>
            Sign Up
        </button>
    </div>
    
    <!-- Authentication Form -->
    <form id="auth-form">
        
        <!-- Login Fields -->
        <div id="identifier-field" class="mb-3">
            <label for="identifier" class="form-label">
                <i data-lucide="user" class="me-1" style="width: 16px; height: 16px;"></i>
                Username or Email
            </label>
            <input type="text" 
                   class="form-control" 
                   id="identifier" 
                   placeholder="Enter your username or email"
                   autocomplete="username">
            <div class="form-text">You can sign in with either your username or email address</div>
        </div>
        
        <!-- Register Fields -->
        <div id="username-field" class="mb-3" style="display: none;">
            <label for="username" class="form-label">
                <i data-lucide="user" class="me-1" style="width: 16px; height: 16px;"></i>
                Username
            </label>
            <input type="text" 
                   class="form-control" 
                   id="username" 
                   placeholder="Choose a username"
                   minlength="3" 
                   maxlength="30"
                   autocomplete="username">
            <div class="form-text">3-30 characters, letters, numbers and underscores only</div>
        </div>
        
        <div id="email-field" class="mb-3" style="display: none;">
            <label for="email" class="form-label">
                <i data-lucide="mail" class="me-1" style="width: 16px; height: 16px;"></i>
                Email Address
            </label>
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   placeholder="Enter your email address"
                   autocomplete="email">
            <div class="form-text">We'll use this for account recovery and notifications</div>
        </div>
        
        <!-- Password Field -->
        <div class="mb-3">
            <label for="password" class="form-label">
                <i data-lucide="lock" class="me-1" style="width: 16px; height: 16px;"></i>
                Password
            </label>
            <div class="position-relative">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       placeholder="Enter your password"
                       minlength="6"
                       autocomplete="current-password">
                <button type="button" 
                        class="btn btn-sm position-absolute end-0 top-50 translate-middle-y me-2" 
                        id="toggle-password"
                        style="background: none; border: none; z-index: 5;">
                    <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
            <div class="form-text">
                <span class="login-help">Enter your password to sign in</span>
                <span class="register-help" style="display: none;">Minimum 6 characters recommended</span>
            </div>
        </div>
        
        <!-- Password Strength (Register Only) -->
        <div id="password-strength" class="mb-3" style="display: none;">
            <div class="password-strength-label mb-1">
                <small class="text-muted">Password Strength:</small>
            </div>
            <div class="progress mb-1" style="height: 4px;">
                <div class="progress-bar" 
                     id="password-strength-fill" 
                     style="width: 0%"></div>
            </div>
            <small class="text-muted" id="password-strength-text">Enter a password</small>
        </div>
        
        <!-- Remember Me (Login Only) -->
        <div id="remember-field" class="mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember-me">
                <label class="form-check-label" for="remember-me">
                    <i data-lucide="clock" class="me-1" style="width: 14px; height: 14px;"></i>
                    Keep me signed in
                </label>
            </div>
        </div>
        
        <!-- Terms Agreement (Register Only) -->
        <div id="terms-field" class="mb-3" style="display: none;">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="terms-agreement">
                <label class="form-check-label" for="terms-agreement">
                    <i data-lucide="shield-check" class="me-1" style="width: 14px; height: 14px;"></i>
                    I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                    <a href="#" class="text-decoration-none">Privacy Policy</a>
                </label>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="d-grid">
            <button type="submit" class="btn btn-primary" id="submit-btn">
                <i data-lucide="log-in" class="me-2" style="width: 16px; height: 16px;"></i>
                Sign In
            </button>
        </div>
        
    </form>
    
    <!-- Additional Links -->
    <div class="text-center mt-4" id="forgot-password-link">
        <a href="#" class="text-muted text-decoration-none">
            <i data-lucide="help-circle" class="me-1" style="width: 14px; height: 14px;"></i>
            Forgot your password?
        </a>
    </div>
    
    <!-- Security Notice -->
    <div class="mt-4 p-3" style="background: var(--bg-tertiary); border-radius: var(--border-radius);">
        <div class="d-flex align-items-start gap-2">
            <i data-lucide="shield" class="text-success" style="width: 16px; height: 16px; margin-top: 2px;"></i>
            <div>
                <div class="fw-medium text-success">Secure Connection</div>
                <small class="text-muted">Your data is protected with industry-standard encryption</small>
            </div>
        </div>
    </div>
    
</div>

<script>
// Initialize form on load and Lucide icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showLoginForm === 'function') {
        showLoginForm();
    }
    
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Password visibility toggle with Lucide icons
$('#toggle-password').on('click', function() {
    const passwordField = $('#password');
    const icon = $(this).find('[data-lucide]');
    
    if (passwordField.attr('type') === 'password') {
        passwordField.attr('type', 'text');
        icon.attr('data-lucide', 'eye-off');
    } else {
        passwordField.attr('type', 'password');
        icon.attr('data-lucide', 'eye');
    }
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Update icons when switching between forms
function updateAuthIcons() {
    const isLogin = $('#login-tab').hasClass('active');
    const submitBtn = $('#submit-btn');
    const btnIcon = submitBtn.find('[data-lucide]');
    
    if (isLogin) {
        btnIcon.attr('data-lucide', 'log-in');
        submitBtn.contents().last()[0].textContent = ' Sign In';
    } else {
        btnIcon.attr('data-lucide', 'user-plus');
        submitBtn.contents().last()[0].textContent = ' Create Account';
    }
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Hook into form switching to update icons
$('#login-tab').on('click', function() {
    setTimeout(updateAuthIcons, 10);
});

$('#register-tab').on('click', function() {
    setTimeout(updateAuthIcons, 10);
});
</script>

<style>
.auth-form-container {
    max-width: 400px;
    margin: 0 auto;
    color: var(--text-primary);
}

.auth-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary-color), #6f42c1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.nav-pills .nav-link {
    border-radius: 0.5rem;
    transition: var(--transition);
}

.nav-pills .nav-link:not(.active) {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}

.nav-pills .nav-link:not(.active):hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.nav-pills .nav-link.active {
    background: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-color);
}

.password-strength-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.progress-bar.weak {
    background-color: var(--danger-color);
}

.progress-bar.medium {
    background-color: var(--warning-color);
}

.progress-bar.strong {
    background-color: var(--success-color);
}

.form-check-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

@media (max-width: 576px) {
    .auth-form-container {
        padding: 1rem;
    }
    
    .auth-logo {
        width: 60px;
        height: 60px;
    }
    
    .nav-pills .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>