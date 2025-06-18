// Constants
const STORAGE_KEYS = {
    USERS: 'footballCourtUsers',
    CURRENT_USER: 'footballCourtCurrentUser',
    REMEMBERED_USER: 'rememberedUser',
    BOOKINGS: 'footballCourtBookings',
    COURTS: 'footballCourtCourts'
};

// Validation Utilities
const validators = {
    required: (value) => {
        return value && value.trim().length > 0 ? null : 'This field is required';
    },
    
    email: (value) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value) ? null : 'Please enter a valid email address';
    },
    
    phone: (value) => {
        const phoneRegex = /^\+?[\d\s-]{10,}$/;
        return phoneRegex.test(value) ? null : 'Please enter a valid phone number';
    },
    
    password: (value) => {
        if (value.length < 8) return 'Password must be at least 8 characters long';
        if (!/\d/.test(value)) return 'Password must contain at least one number';
        if (!/[A-Z]/.test(value)) return 'Password must contain at least one uppercase letter';
        if (!/[a-z]/.test(value)) return 'Password must contain at least one lowercase letter';
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(value)) return 'Password must contain at least one special character';
        return null;
    },
    
    passwordMatch: (value, compareValue) => {
        return value === compareValue ? null : 'Passwords do not match';
    }
};

// Form Handling Utilities
const formUtils = {
    showError: (input, message) => {
        const errorElement = formUtils.getErrorElement(input);
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        input.classList.add('error');
    },

    hideError: (input) => {
        const errorElement = formUtils.getErrorElement(input);
        errorElement.style.display = 'none';
        input.classList.remove('error');
    },

    getErrorElement: (input) => {
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        return errorElement;
    },

    validateForm: (form, validationRules) => {
        let isValid = true;
        const formData = {};

        for (const [fieldName, rules] of Object.entries(validationRules)) {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (!input) continue;

            formData[fieldName] = input.value;
            
            for (const rule of rules) {
                const error = validators[rule](input.value);
                if (error) {
                    formUtils.showError(input, error);
                    isValid = false;
                    break;
                } else {
                    formUtils.hideError(input);
                }
            }
        }

        return { isValid, formData };
    }
};

// Notification Utilities
const notifications = {
    show: (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        // Add notification styles
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '5px',
            color: '#fff',
            zIndex: '1000',
            animation: 'slideIn 0.5s ease'
        });

        // Set background color based on type
        const colors = {
            success: '#34a853',
            error: '#ea4335',
            info: '#1a73e8',
            warning: '#fbbc04'
        };
        notification.style.backgroundColor = colors[type] || colors.info;

        // Add animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Add to document and remove after delay
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.5s ease';
            setTimeout(() => {
                notification.remove();
                style.remove();
            }, 500);
        }, 3000);
    }
};

// Storage Utilities
const storage = {
    get: (key) => {
        try {
            return JSON.parse(localStorage.getItem(key));
        } catch (e) {
            console.error(`Error reading from localStorage: ${e}`);
            return null;
        }
    },

    set: (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.error(`Error writing to localStorage: ${e}`);
            return false;
        }
    },

    remove: (key) => {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error(`Error removing from localStorage: ${e}`);
            return false;
        }
    }
};

// Authentication Utilities
const auth = {
    getCurrentUser: () => {
        return storage.get(STORAGE_KEYS.CURRENT_USER);
    },

    login: (email, password) => {
        const users = storage.get(STORAGE_KEYS.USERS) || [];
        const user = users.find(u => u.email === email);
        
        if (!user) return { success: false, message: 'User not found' };
        if (user.password !== btoa(password)) return { success: false, message: 'Invalid password' };

        const currentUser = {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role || 'user',
            lastLogin: new Date().toISOString()
        };

        storage.set(STORAGE_KEYS.CURRENT_USER, currentUser);
        return { success: true, user: currentUser };
    },

    logout: () => {
        storage.remove(STORAGE_KEYS.CURRENT_USER);
    },

    isAuthenticated: () => {
        return !!auth.getCurrentUser();
    }
};

// Export utilities
window.footballCourtUtils = {
    validators,
    formUtils,
    notifications,
    storage,
    auth,
    STORAGE_KEYS
}; 