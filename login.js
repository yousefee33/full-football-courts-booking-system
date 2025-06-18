// Get utilities from global object
const { formUtils, notifications, auth, storage, STORAGE_KEYS } = window.footballCourtUtils;

// Constants
const USERS_STORAGE_KEY = 'footballCourtUsers';
const CURRENT_USER_KEY = 'footballCourtCurrentUser';

// DOM Elements
const loginForm = document.querySelector('.login-form');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const rememberMeCheckbox = document.getElementById('remember');

// Validation rules
const validationRules = {
    email: ['required', 'email'],
    password: ['required']
};

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    loginForm.addEventListener('submit', handleLogin);
    checkRememberedUser();
});

// Function to handle login form submission
async function handleLogin(event) {
    event.preventDefault();

    // Validate form
    const { isValid, formData } = formUtils.validateForm(loginForm, validationRules);
    if (!isValid) return;

    // Attempt login
    const result = auth.login(formData.email, formData.password);
    
    if (!result.success) {
        notifications.show(result.message, 'error');
        return;
    }

    // Handle remember me
    if (rememberMeCheckbox.checked) {
        storage.set(STORAGE_KEYS.REMEMBERED_USER, { 
            email: formData.email, 
            password: formData.password 
        });
    } else {
        storage.remove(STORAGE_KEYS.REMEMBERED_USER);
    }

    // Show success message and redirect
    notifications.show('Login successful! Redirecting...', 'success');
    setTimeout(() => {
        window.location.href = result.user.role === 'admin' ? 'Dashboard.php: 'home.php
    }, 1500);
}

// Function to toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.querySelector('.toggle-password');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.textContent = '👁️‍🗨️';
    } else {
        passwordInput.type = 'password';
        toggleButton.textContent = '👁️';
    }
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    // Add notification styles
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '5px';
    notification.style.color = '#fff';
    notification.style.zIndex = '1000';
    notification.style.animation = 'slideIn 0.5s ease';

    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#34a853';
            break;
        case 'error':
            notification.style.backgroundColor = '#ea4335';
            break;
        default:
            notification.style.backgroundColor = '#1a73e8';
    }

    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);

    // Add to document
    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.5s ease';
        setTimeout(() => {
            notification.remove();
            style.remove();
        }, 500);
    }, 3000);
}

// Check for remembered user on page load
function checkRememberedUser() {
    const rememberedUser = storage.get(STORAGE_KEYS.REMEMBERED_USER);
    if (rememberedUser) {
        emailInput.value = rememberedUser.email;
        passwordInput.value = rememberedUser.password;
        rememberMeCheckbox.checked = true;
    }
}

// Error Handling
function showError(input, message) {
    const errorElement = getErrorElement(input);
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    input.classList.add('error');
}

function hideError(input) {
    const errorElement = getErrorElement(input);
    errorElement.style.display = 'none';
    input.classList.remove('error');
}

function getErrorElement(input) {
    let errorElement = input.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('error-message')) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        input.parentNode.insertBefore(errorElement, input.nextSibling);
    }
    return errorElement;
}

// Utility Functions
async function hashPassword(password) {
    // In a real application, you would use a proper hashing algorithm
    // For this demo, we'll use a simple encoding
    return btoa(password);
} 