import { STORAGE_KEYS, NotificationSystem, FormValidator, ImageHandler, StorageManager } from './utils.js';

class AddCourtForm {
    constructor() {
        this.form = document.getElementById('addCourtForm');
        this.imageInput = document.getElementById('courtImages');
        this.imagePreviewContainer = document.getElementById('imagePreviewContainer');
        this.selectedImages = [];
        this.maxImages = 5;
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.imageInput.addEventListener('change', (e) => this.handleImageSelection(e));
        
        // Add real-time validation for required fields
        this.form.querySelectorAll('input[required], select[required], textarea[required]')
            .forEach(field => {
                field.addEventListener('blur', () => this.validateField(field));
            });
    }

    validateField(field) {
        const validationMessage = field.nextElementSibling?.classList.contains('validation-message') 
            ? field.nextElementSibling 
            : document.createElement('div');
        
        if (!validationMessage.classList.contains('validation-message')) {
            validationMessage.className = 'validation-message';
            field.parentNode.insertBefore(validationMessage, field.nextSibling);
        }

        let isValid = true;
        let message = '';

        switch (field.id) {
            case 'courtName':
                isValid = FormValidator.validateRequired(field.value);
                message = isValid ? '' : 'Court name is required';
                break;
            
            case 'courtType':
                isValid = field.value !== '';
                message = isValid ? '' : 'Please select a court type';
                break;
            
            case 'capacity':
                isValid = FormValidator.validateNumber(field.value, 2);
                message = isValid ? '' : 'Capacity must be at least 2 players';
                break;
            
            case 'pricePerHour':
                isValid = FormValidator.validateNumber(field.value, 0);
                message = isValid ? '' : 'Price must be a positive number';
                break;
            
            case 'address':
            case 'city':
            case 'postalCode':
                isValid = FormValidator.validateRequired(field.value);
                message = isValid ? '' : `${field.id.charAt(0).toUpperCase() + field.id.slice(1)} is required`;
                break;
            
            case 'courtImages':
                isValid = this.selectedImages.length > 0;
                message = isValid ? '' : 'At least one image is required';
                break;
        }

        validationMessage.textContent = message;
        field.classList.toggle('invalid', !isValid);
        
        return isValid;
    }

    async handleImageSelection(e) {
        const files = Array.from(e.target.files);
        
        if (this.selectedImages.length + files.length > this.maxImages) {
            NotificationSystem.show(`Maximum ${this.maxImages} images allowed`, 'error');
            return;
        }

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                NotificationSystem.show('Please select only image files', 'error');
                continue;
            }

            try {
                const resizedImage = await ImageHandler.resizeImage(file);
                this.selectedImages.push(resizedImage);
                this.addImagePreview(resizedImage);
            } catch (error) {
                console.error('Error processing image:', error);
                NotificationSystem.show('Error processing image', 'error');
            }
        }

        this.validateField(this.imageInput);
    }

    addImagePreview(imageBlob) {
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        
        const img = document.createElement('img');
        img.src = URL.createObjectURL(imageBlob);
        
        const removeButton = document.createElement('button');
        removeButton.className = 'remove-image';
        removeButton.innerHTML = '×';
        removeButton.addEventListener('click', () => this.removeImage(preview, imageBlob));
        
        preview.appendChild(img);
        preview.appendChild(removeButton);
        this.imagePreviewContainer.appendChild(preview);
    }

    removeImage(previewElement, imageBlob) {
        const index = this.selectedImages.indexOf(imageBlob);
        if (index > -1) {
            this.selectedImages.splice(index, 1);
        }
        previewElement.remove();
        this.validateField(this.imageInput);
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Validate all fields
        const fields = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        if (!isValid) {
            NotificationSystem.show('Please fill in all required fields correctly', 'error');
            return;
        }

        // Disable submit button and show loading state
        const submitButton = this.form.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="loading-spinner"></span>Saving...';

        try {
            // Convert images to base64 for storage
            const imagePromises = this.selectedImages.map(blob => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result);
                    reader.readAsDataURL(blob);
                });
            });

            const imageBase64Array = await Promise.all(imagePromises);

            // Gather form data
            const formData = {
                id: Date.now().toString(),
                name: this.form.courtName.value,
                type: this.form.courtType.value,
                capacity: parseInt(this.form.capacity.value),
                pricePerHour: parseFloat(this.form.pricePerHour.value),
                location: {
                    address: this.form.address.value,
                    city: this.form.city.value,
                    postalCode: this.form.postalCode.value
                },
                facilities: Array.from(this.form.facilities)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value),
                images: imageBase64Array,
                description: this.form.description.value,
                rules: this.form.rules.value,
                createdAt: new Date().toISOString(),
                status: 'available'
            };

            // Save to localStorage
            const courts = StorageManager.get(STORAGE_KEYS.COURTS) || [];
            courts.push(formData);
            StorageManager.set(STORAGE_KEYS.COURTS, courts);

            NotificationSystem.show('Court added successfully!', 'success');
            
            // Redirect to view courts page after a short delay
            setTimeout(() => {
                window.location.href = 'viewcourts.php';
            }, 1500);

        } catch (error) {
            console.error('Error saving court:', error);
            NotificationSystem.show('Error saving court', 'error');
            
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }
}

// Initialize the form handler when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AddCourtForm();
}); 