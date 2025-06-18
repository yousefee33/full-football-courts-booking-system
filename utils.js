// Local Storage Keys
const STORAGE_KEYS = {
    COURTS: 'football_courts',
    BOOKINGS: 'court_bookings',
    USER_PROFILE: 'user_profile'
};

// Notification System
class NotificationSystem {
    static show(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Modal System
class Modal {
    constructor(content, options = {}) {
        this.content = content;
        this.options = {
            closeOnOverlayClick: true,
            ...options
        };
        this.modal = null;
        this.overlay = null;
    }

    create() {
        // Create overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'modal-overlay';

        // Create modal
        this.modal = document.createElement('div');
        this.modal.className = 'modal';

        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'modal-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => this.close();

        // Add content
        this.modal.appendChild(closeBtn);
        if (typeof this.content === 'string') {
            this.modal.innerHTML += this.content;
        } else {
            this.modal.appendChild(this.content);
        }

        // Add modal to overlay
        this.overlay.appendChild(this.modal);

        // Setup event listeners
        if (this.options.closeOnOverlayClick) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });
        }

        // Add to DOM
        document.body.appendChild(this.overlay);
    }

    close() {
        if (this.overlay) {
            this.overlay.remove();
        }
    }
}

// Form Validation
class FormValidator {
    static validateRequired(value) {
        return value !== null && value !== undefined && value.trim() !== '';
    }

    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePhone(phone) {
        const re = /^\+?[\d\s-]{10,}$/;
        return re.test(phone);
    }

    static validateNumber(value, min = null, max = null) {
        const num = Number(value);
        if (isNaN(num)) return false;
        if (min !== null && num < min) return false;
        if (max !== null && num > max) return false;
        return true;
    }
}

// Date and Time Utilities
class DateTimeUtils {
    static formatDate(date) {
        return new Date(date).toLocaleDateString();
    }

    static formatTime(date) {
        return new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    static formatDateTime(date) {
        return new Date(date).toLocaleString();
    }

    static isValidDate(date) {
        const d = new Date(date);
        return d instanceof Date && !isNaN(d);
    }

    static addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    }
}

// Image Handling
class ImageHandler {
    static async resizeImage(file, maxWidth = 800) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    if (width > maxWidth) {
                        height = (maxWidth * height) / width;
                        width = maxWidth;
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', 0.85);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    static createImagePreview(file, previewElement) {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewElement.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Local Storage Management
class StorageManager {
    static set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Error saving to localStorage:', error);
            return false;
        }
    }

    static get(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Error reading from localStorage:', error);
            return null;
        }
    }

    static remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Error removing from localStorage:', error);
            return false;
        }
    }

    static clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Error clearing localStorage:', error);
            return false;
        }
    }
}

// Export all utilities
export {
    STORAGE_KEYS,
    NotificationSystem,
    Modal,
    FormValidator,
    DateTimeUtils,
    ImageHandler,
    StorageManager
}; 