import { STORAGE_KEYS, NotificationSystem, Modal, StorageManager } from './utils.js';

class ViewCourtsPage {
    constructor() {
        this.courts = [];
        this.filteredCourts = [];
        this.currentCourt = null;
        
        // DOM Elements
        this.courtsGrid = document.getElementById('courtsGrid');
        this.searchInput = document.getElementById('searchInput');
        this.typeFilter = document.getElementById('typeFilter');
        this.cityFilter = document.getElementById('cityFilter');
        this.facilityFilter = document.getElementById('facilityFilter');
        this.priceSort = document.getElementById('priceSort');
        this.noResults = document.getElementById('noResults');
        
        // Modals
        this.courtModal = document.getElementById('courtModal');
        this.deleteModal = document.getElementById('deleteModal');
        
        this.initialize();
    }

    async initialize() {
        this.loadCourts();
        this.setupEventListeners();
        this.populateCityFilter();
        this.renderCourts();
    }

    loadCourts() {
        this.courts = StorageManager.get(STORAGE_KEYS.COURTS) || [];
        this.filteredCourts = [...this.courts];
    }

    setupEventListeners() {
        // Search and Filter Events
        this.searchInput.addEventListener('input', () => this.filterCourts());
        this.typeFilter.addEventListener('change', () => this.filterCourts());
        this.cityFilter.addEventListener('change', () => this.filterCourts());
        this.facilityFilter.addEventListener('change', () => this.filterCourts());
        this.priceSort.addEventListener('change', () => this.filterCourts());

        // Modal Close Events
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', () => {
                this.courtModal.classList.add('hidden');
                this.deleteModal.classList.add('hidden');
            });
        });

        // Delete Court Events
        document.getElementById('cancelDelete').addEventListener('click', () => {
            this.deleteModal.classList.add('hidden');
        });

        document.getElementById('confirmDelete').addEventListener('click', () => {
            this.deleteCourt();
        });
    }

    populateCityFilter() {
        const cities = [...new Set(this.courts.map(court => court.location.city))];
        cities.sort();
        
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            this.cityFilter.appendChild(option);
        });
    }

    filterCourts() {
        const searchTerm = this.searchInput.value.toLowerCase();
        const typeFilter = this.typeFilter.value;
        const cityFilter = this.cityFilter.value;
        const facilityFilter = this.facilityFilter.value;
        const priceSort = this.priceSort.value;

        this.filteredCourts = this.courts.filter(court => {
            const matchesSearch = 
                court.name.toLowerCase().includes(searchTerm) ||
                court.location.city.toLowerCase().includes(searchTerm) ||
                court.type.toLowerCase().includes(searchTerm);

            const matchesType = !typeFilter || court.type === typeFilter;
            const matchesCity = !cityFilter || court.location.city === cityFilter;
            const matchesFacility = !facilityFilter || court.facilities.includes(facilityFilter);

            return matchesSearch && matchesType && matchesCity && matchesFacility;
        });

        if (priceSort) {
            this.filteredCourts.sort((a, b) => {
                return priceSort === 'asc' 
                    ? a.pricePerHour - b.pricePerHour
                    : b.pricePerHour - a.pricePerHour;
            });
        }

        this.renderCourts();
    }

    renderCourts() {
        this.courtsGrid.innerHTML = '';
        
        if (this.filteredCourts.length === 0) {
            this.noResults.classList.remove('hidden');
            return;
        }

        this.noResults.classList.add('hidden');
        
        this.filteredCourts.forEach(court => {
            const card = this.createCourtCard(court);
            this.courtsGrid.appendChild(card);
        });
    }

    createCourtCard(court) {
        const card = document.createElement('div');
        card.className = 'court-card';
        
        card.innerHTML = `
            <div class="court-image">
                <img src="${court.images[0]}" alt="${court.name}">
                <div class="court-type-badge">${court.type}</div>
            </div>
            <div class="court-info">
                <h3 class="court-name">${court.name}</h3>
                <p class="court-location">📍 ${court.location.city}</p>
                <div class="court-details">
                    <span class="detail-item">
                        <i>👥</i> ${court.capacity} players
                    </span>
                    <span class="detail-item">
                        <i>💰</i> $${court.pricePerHour}/hour
                    </span>
                </div>
                <div class="facilities-list">
                    ${court.facilities.map(facility => `
                        <span class="facility-tag">${facility}</span>
                    `).join('')}
                </div>
            </div>
            <div class="court-actions">
                <button class="btn btn-primary view-details">View Details</button>
            </div>
        `;

        card.querySelector('.view-details').addEventListener('click', () => {
            this.showCourtDetails(court);
        });

        return card;
    }

    showCourtDetails(court) {
        this.currentCourt = court;
        
        // Update modal content
        const modal = this.courtModal;
        modal.querySelector('.court-name').textContent = court.name;
        modal.querySelector('.court-type-badge').textContent = court.type;
        modal.querySelector('.court-address').textContent = court.location.address;
        modal.querySelector('.court-city').textContent = `${court.location.city}, ${court.location.postalCode}`;
        modal.querySelector('.court-capacity').textContent = `Capacity: ${court.capacity} players`;
        modal.querySelector('.court-price').textContent = `Price: $${court.pricePerHour}/hour`;
        modal.querySelector('.court-description').textContent = court.description || 'No description available';
        modal.querySelector('.court-rules').textContent = court.rules || 'No specific rules provided';

        // Update facilities
        const facilitiesList = modal.querySelector('.facilities-list');
        facilitiesList.innerHTML = court.facilities.map(facility => `
            <span class="facility-tag">${facility}</span>
        `).join('');

        // Update gallery
        const mainImage = modal.querySelector('.main-image img');
        mainImage.src = court.images[0];
        mainImage.alt = court.name;

        const thumbnailContainer = modal.querySelector('.thumbnail-container');
        thumbnailContainer.innerHTML = court.images.map((image, index) => `
            <div class="thumbnail ${index === 0 ? 'active' : ''}" data-index="${index}">
                <img src="${image}" alt="${court.name} - Image ${index + 1}">
            </div>
        `).join('');

        // Setup thumbnail clicks
        thumbnailContainer.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', () => {
                const index = thumb.dataset.index;
                mainImage.src = court.images[index];
                thumbnailContainer.querySelector('.active').classList.remove('active');
                thumb.classList.add('active');
            });
        });

        // Setup action buttons
        modal.querySelector('.book-btn').addEventListener('click', () => {
            window.location.href = `booking.phpcourtId=${court.id}`;
        });

        modal.querySelector('.edit-btn').addEventListener('click', () => {
            window.location.href = `addcourt.php?courtId=${court.id}`;
        });

        modal.querySelector('.delete-btn').addEventListener('click', () => {
            this.showDeleteConfirmation();
        });

        // Show modal
        modal.classList.remove('hidden');
    }

    showDeleteConfirmation() {
        this.deleteModal.classList.remove('hidden');
    }

    deleteCourt() {
        if (!this.currentCourt) return;

        const index = this.courts.findIndex(c => c.id === this.currentCourt.id);
        if (index > -1) {
            this.courts.splice(index, 1);
            StorageManager.set(STORAGE_KEYS.COURTS, this.courts);
            
            this.deleteModal.classList.add('hidden');
            this.courtModal.classList.add('hidden');
            
            NotificationSystem.show('Court deleted successfully', 'success');
            
            this.filterCourts();
        }
    }
}

// Initialize the page when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ViewCourtsPage();
}); 