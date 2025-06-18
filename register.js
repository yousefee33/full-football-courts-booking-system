// Get utilities from global object
const { formUtils, notifications, storage, validators, STORAGE_KEYS } = window.footballCourtUtils;

// Constants
const USERS_STORAGE_KEY = 'footballCourtUsers';

// DOM Elements
const registerForm = document.querySelector('.register-form');
const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const phoneInput = document.getElementById('phone');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirmPassword');
const termsCheckbox = document.querySelector('input[name="terms"]');

// Validation rules
const validationRules = {
    name: ['required'],
    email: ['required', 'email'],
    phone: ['required', 'phone'],
    password: ['required', 'password'],
    confirmPassword: ['required']
};

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    registerForm.addEventListener('submit', handleRegister);
    setupValidation();
});

// Form Validation
function setupValidation() {
    // Name validation
    nameInput.addEventListener('input', () => {
        const errorElement = getErrorElement(nameInput);
        if (nameInput.value.length < 3) {
            showError(nameInput, 'Name must be at least 3 characters long');
        } else {
            hideError(nameInput);
        }
    });

    // Email validation
    emailInput.addEventListener('input', () => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value)) {
            showError(emailInput, 'Please enter a valid email address');
        } else {
            hideError(emailInput);
        }
    });

    // Phone validation
    phoneInput.addEventListener('input', () => {
        const phoneRegex = /^\+?[\d\s-]{10,}$/;
        if (!phoneRegex.test(phoneInput.value)) {
            showError(phoneInput, 'Please enter a valid phone number');
        } else {
            hideError(phoneInput);
        }
    });

    // Password validation
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);
}

function validatePassword() {
    const password = passwordInput.value;
    if (password.length < 8) {
        showError(passwordInput, 'Password must be at least 8 characters long');
    } else if (!/\d/.test(password)) {
        showError(passwordInput, 'Password must contain at least one number');
    } else if (!/[A-Z]/.test(password)) {
        showError(passwordInput, 'Password must contain at least one uppercase letter');
    } else if (!/[a-z]/.test(password)) {
        showError(passwordInput, 'Password must contain at least one lowercase letter');
    } else {
        hideError(passwordInput);
    }
    validateConfirmPassword();
}

function validateConfirmPassword() {
    if (confirmPasswordInput.value !== passwordInput.value) {
        showError(confirmPasswordInput, 'Passwords do not match');
    } else {
        hideError(confirmPasswordInput);
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

// Form Submission
async function handleRegister(event) {
    event.preventDefault();

    // Validate form
    const { isValid, formData } = formUtils.validateForm(registerForm, validationRules);
    if (!isValid) return;

    // Additional validation for password match
    if (formData.password !== formData.confirmPassword) {
        formUtils.showError(confirmPasswordInput, 'Passwords do not match');
        return;
    }

    // Check terms acceptance
    if (!registerForm.querySelector('#terms').checked) {
        notifications.show('Please accept the Terms & Conditions', 'error');
        return;
    }

    // Get existing users
    const users = storage.get(STORAGE_KEYS.USERS) || [];

    // Check if email already exists
    if (users.some(user => user.email === formData.email)) {
        notifications.show('Email already registered', 'error');
        return;
    }

    // Create new user object
    const newUser = {
        id: generateUserId(),
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        password: btoa(formData.password),
        role: 'user',
        createdAt: new Date().toISOString()
    };

    // Add user to storage
    users.push(newUser);
    storage.set(STORAGE_KEYS.USERS, users);

    // Show success message and redirect
    notifications.show('Registration successful! Redirecting to login...', 'success');
    setTimeout(() => {
        window.location.href = 'login.php
    }, 2000);
}

// Function to toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggleButton = input.parentElement.querySelector('.toggle-password');

    if (input.type === 'password') {
        input.type = 'text';
        toggleButton.textContent = '👁️‍🗨️';
    } else {
        input.type = 'password';
        toggleButton.textContent = '👁️';
    }
}

// Function to check password strength
function isPasswordStrong(password) {
    const minLength = 8;
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    return password.length >= minLength && hasLetter && hasNumber && hasSpecial;
}

// Function to update password strength indicator
function updatePasswordStrength(password) {
    const strengthIndicator = document.getElementById('passwordStrength');
    const minLength = 8;
    const hasLetter = /[a-zA-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    let strength = 0;
    if (password.length >= minLength) strength++;
    if (hasLetter) strength++;
    if (hasNumber) strength++;
    if (hasSpecial) strength++;

    strengthIndicator.className = 'password-strength';
    if (strength === 0) {
        strengthIndicator.classList.add('weak');
    } else if (strength <= 2) {
        strengthIndicator.classList.add('medium');
    } else {
        strengthIndicator.classList.add('strong');
    }
}

// Function to generate unique user ID
function generateUserId() {
    return 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

async function hashPassword(password) {
    // In a real application, you would use a proper hashing algorithm
    // For this demo, we'll use a simple encoding
    return btoa(password);
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