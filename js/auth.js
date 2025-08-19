// Authentication Functions (restored original functionality)

// Login function
function login() {
    const identifier = $('#identifier').val();
    const password = $('#password').val();
    
    if (!identifier || !password) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please fill in all fields', 'warning');
        } else {
            alert('Please fill in all fields');
        }
        return;
    }

    // Disable form during submission
    const submitBtn = $('#submit-btn');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('Signing In...');

    $.post('handlers/auth_handler.php', {
        action: 'login',
        username: identifier,
        password: password
    }, function(response) {
        if (response.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('Sign in successful! Redirecting...', 'success');
            }
            
            // Reload page after short delay to show success message
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(response.error || 'Invalid credentials', 'danger');
            } else {
                alert(response.error || 'Invalid credentials');
            }
            
            // Re-enable form
            submitBtn.prop('disabled', false).html(originalText);
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Connection error. Please try again.', 'danger');
        }
        
        // Re-enable form
        submitBtn.prop('disabled', false).html(originalText);
    });
}

// Register function
function register() {
    const username = $('#username').val();
    const email = $('#email').val();
    const password = $('#password').val();
    
    if (!username || !email || !password) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please fill in all fields', 'warning');
        } else {
            alert('Please fill in all fields');
        }
        return;
    }
    
    // Basic validation
    if (username.length < 3) {
        if (typeof window.showToast === 'function') {
            window.showToast('Username must be at least 3 characters long', 'warning');
        }
        return;
    }
    
    if (password.length < 6) {
        if (typeof window.showToast === 'function') {
            window.showToast('Password must be at least 6 characters long', 'warning');
        }
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please enter a valid email address', 'warning');
        }
        return;
    }

    // Check terms agreement if checkbox exists
    const termsCheckbox = $('#terms-agreement');
    if (termsCheckbox.length && !termsCheckbox.is(':checked')) {
        if (typeof window.showToast === 'function') {
            window.showToast('Please agree to the terms and conditions', 'warning');
        }
        return;
    }

    // Disable form during submission
    const submitBtn = $('#submit-btn');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('Creating Account...');

    $.post('handlers/auth_handler.php', {
        action: 'register',
        username: username,
        email: email,
        password: password
    }, function(response) {
        if (response.success) {
            if (typeof window.showToast === 'function') {
                window.showToast('Account created successfully! Redirecting...', 'success');
            }
            
            // Reload page after short delay to show success message
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            if (typeof window.showToast === 'function') {
                window.showToast(response.error || 'Registration failed', 'danger');
            } else {
                alert(response.error || 'Registration failed');
            }
            
            // Re-enable form
            submitBtn.prop('disabled', false).html(originalText);
        }
    }, 'json').fail(function() {
        if (typeof window.showToast === 'function') {
            window.showToast('Connection error. Please try again.', 'danger');
        }
        
        // Re-enable form
        submitBtn.prop('disabled', false).html(originalText);
    });
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to sign out?')) {
        $.post('handlers/auth_handler.php', { action: 'logout' }, function() {
            if (typeof window.showToast === 'function') {
                window.showToast('Signed out successfully', 'info');
            }
            
            setTimeout(() => {
                location.reload();
            }, 500);
        }).fail(function() {
            // Force reload even if request fails
            location.reload();
        });
    }
}

// Show login form
function showLoginForm() {
    $('#username-field, #email-field, #terms-field').hide();
    $('#identifier-field, #remember-field, #forgot-password-link').show();
    
    const submitBtn = $('#submit-btn');
    if (submitBtn.length) {
        submitBtn.text('Sign In').off('click').on('click', function(e) {
            e.preventDefault();
            login();
        });
    }
    
    // Update tab states
    $('#login-tab').addClass('active');
    $('#register-tab').removeClass('active');
    
    // Update form title
    $('#auth-title').text('Welcome Back');
    
    // Update password help text
    $('.login-help').show();
    $('.register-help').hide();
    $('#password-strength').hide();
}

// Show register form
function showRegisterForm() {
    $('#identifier-field, #remember-field, #forgot-password-link').hide();
    $('#username-field, #email-field, #terms-field').show();
    
    const submitBtn = $('#submit-btn');
    if (submitBtn.length) {
        submitBtn.text('Create Account').off('click').on('click', function(e) {
            e.preventDefault();
            register();
        });
    }
    
    // Update tab states
    $('#login-tab').removeClass('active');
    $('#register-tab').addClass('active');
    
    // Update form title
    $('#auth-title').text('Create Account');
    
    // Update password help text
    $('.login-help').hide();
    $('.register-help').show();
    $('#password-strength').show();
}

// Form validation and UI enhancements
$(document).ready(function() {
    // Form submission handler
    $('#auth-form').on('submit', function(e) {
        e.preventDefault();
        
        const isLogin = $('#login-tab').hasClass('active');
        if (isLogin) {
            login();
        } else {
            register();
        }
    });
    
    // Tab click handlers
    $('#login-tab').on('click', function(e) {
        e.preventDefault();
        showLoginForm();
    });
    
    $('#register-tab').on('click', function(e) {
        e.preventDefault();
        showRegisterForm();
    });
    
    // Password strength indicator
    $('#password').on('input', function() {
        const password = this.value;
        const strengthIndicator = $('#password-strength');
        const strengthFill = $('#password-strength-fill');
        const strengthText = $('#password-strength-text');
        
        if (!strengthIndicator.length) return;
        
        if (!password) {
            strengthFill.css('width', '0%').removeClass('weak medium strong');
            strengthText.text('Enter a password');
            return;
        }
        
        let score = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) score += 25;
        else feedback.push('at least 8 characters');
        
        // Uppercase check
        if (/[A-Z]/.test(password)) score += 25;
        else feedback.push('uppercase letter');
        
        // Lowercase check
        if (/[a-z]/.test(password)) score += 25;
        else feedback.push('lowercase letter');
        
        // Number or symbol check
        if (/[\d\W]/.test(password)) score += 25;
        else feedback.push('number or symbol');
        
        // Update visual
        strengthFill.css('width', score + '%');
        
        let strength = 'weak';
        if (score >= 75) strength = 'strong';
        else if (score >= 50) strength = 'medium';
        
        strengthFill.removeClass('weak medium strong').addClass(strength);
        
        if (score === 100) {
            strengthText.text('Strong password!');
        } else {
            strengthText.text(`${strength.charAt(0).toUpperCase() + strength.slice(1)} - Add: ${feedback.join(', ')}`);
        }
    });
    
    // Password visibility toggle
    $('#toggle-password').on('click', function() {
        const passwordField = $('#password');
        const icon = $(this).find('[data-lucide]');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            $(this).text('👁️‍🗨️');
        } else {
            passwordField.attr('type', 'password');
            $(this).text('👁️');
        }
    });
    
    // Initialize form state
    if ($('#login-tab').length) {
        showLoginForm();
    }
});

// Make functions globally available
window.login = login;
window.register = register;
window.logout = logout;
window.showLoginForm = showLoginForm;
window.showRegisterForm = showRegisterForm;