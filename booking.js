import { STORAGE_KEYS, NotificationSystem, StorageManager } from './utils.js';

// Initialize sample courts data if not exists
function initializeSampleCourts() {
    const sampleCourts = [
        {
            id: '1',
            name: 'Premium Indoor Court',
            type: 'indoor',
            location: {
                city: 'cairo',
                address: 'Nasr City'
            },
            pricePerHour: 250,
            capacity: 10,
            facilities: ['Showers', 'Parking', 'Lockers'],
            images: ['images/court1.jpg']
        },
        {
            id: '2',
            name: 'Outdoor Turf Field',
            type: 'outdoor',
            location: {
                city: 'giza',
                address: '6th October'
            },
            pricePerHour: 300,
            capacity: 14,
            facilities: ['Lighting', 'Café', 'Parking'],
            images: ['images/court2.jpg']
        }
    ];

    if (!localStorage.getItem('courts')) {
        localStorage.setItem('courts', JSON.stringify(sampleCourts));
    }
}

class BookingPage {
    constructor() {
        this.courts = [];
        this.filteredCourts = [];
        this.selectedCourt = null;
        this.bookingData = {
            additionalServices: {
                equipment: 10,
                referee: 30,
                lighting: 15
            }
        };

        // DOM Elements - Court Selection
        this.courtsGrid = document.getElementById('courtsGrid');
        this.courtType = document.getElementById('courtType');
        this.courtCity = document.getElementById('courtCity');
        this.priceRange = document.getElementById('priceRange');
        this.searchBtn = document.getElementById('searchBtn');
        
        // DOM Elements - Booking Form
        this.bookingForm = document.getElementById('courtBookingForm');
        this.bookingSection = document.getElementById('bookingForm');
        this.bookingDate = document.getElementById('bookingDate');
        this.bookingTime = document.getElementById('bookingTime');
        this.duration = document.getElementById('duration');
        this.players = document.getElementById('players');
        this.fullName = document.getElementById('fullName');
        this.phone = document.getElementById('phone');
        this.email = document.getElementById('email');
        this.notes = document.getElementById('notes');
        
        // Additional Services
        this.equipmentCheckbox = document.getElementById('equipment');
        this.refereeCheckbox = document.getElementById('referee');
        this.lightingCheckbox = document.getElementById('lighting');
        
        // Price Elements
        this.courtRentalPrice = document.querySelector('.court-rental-price');
        this.servicesPrice = document.querySelector('.services-price');
        this.totalPrice = document.querySelector('.total-price');
        
        // Modal Elements
        this.confirmationModal = document.getElementById('confirmationModal');
        
        // Initialize sample data
        initializeSampleCourts();
        
        this.initialize();
    }

    initialize() {
        console.log('Initializing booking page...');
        this.loadCourts();
        this.setupEventListeners();
        this.populateCityFilter();
        this.populateTimeSlots();
        this.setMinDate();
        this.renderCourts();
        
        // Check if a specific court was selected from the view courts page
        const urlParams = new URLSearchParams(window.location.search);
        const courtId = urlParams.get('courtId');
        if (courtId) {
            const court = this.courts.find(c => c.id === courtId);
            if (court) {
                this.selectCourt(court);
            }
        }
    }

    loadCourts() {
        try {
            const courtsData = localStorage.getItem('courts');
            this.courts = courtsData ? JSON.parse(courtsData) : [];
            this.filteredCourts = [...this.courts];
            console.log('Courts loaded:', this.courts);
        } catch (error) {
            console.error('Error loading courts:', error);
            this.courts = [];
            this.filteredCourts = [];
        }
    }

    setupEventListeners() {
        console.log('Setting up event listeners...');
        
        // Search button click event
        if (this.searchBtn) {
            console.log('Search button found, adding click listener');
            this.searchBtn.addEventListener('click', () => {
                console.log('Search button clicked');
                this.filterCourts();
            });
        } else {
            console.error('Search button not found in DOM');
        }

        // Filter change events
        [this.courtType, this.courtCity, this.priceRange].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => {
                    console.log('Filter changed:', filter.id);
                    this.updateSearchButtonState();
                });
            }
        });

        // Booking form submit
        if (this.bookingForm) {
            this.bookingForm.addEventListener('submit', (e) => this.handleBookingSubmit(e));
        }

        // Cancel booking
        const cancelButton = document.querySelector('.btn-cancel');
        if (cancelButton) {
            cancelButton.addEventListener('click', () => {
                this.bookingSection.style.display = 'none';
                this.courtsGrid.parentElement.style.display = 'block';
            });
        }

        // Duration change
        if (this.duration) {
            this.duration.addEventListener('change', () => this.updatePriceSummary());
        }

        // Players input validation
        if (this.players) {
            this.players.addEventListener('input', () => {
                const value = parseInt(this.players.value);
                if (this.selectedCourt) {
                    if (value > this.selectedCourt.capacity) {
                        this.players.value = this.selectedCourt.capacity;
                    } else if (value < 2) {
                        this.players.value = 2;
                    }
                }
            });
        }

        // Additional Services Events
        this.equipmentCheckbox.addEventListener('change', () => this.updatePriceSummary());
        this.refereeCheckbox.addEventListener('change', () => this.updatePriceSummary());
        this.lightingCheckbox.addEventListener('change', () => this.updatePriceSummary());
        
        // Modal Events
        document.querySelectorAll('.modal-close').forEach(button => {
            button.addEventListener('click', () => {
                this.confirmationModal.classList.add('hidden');
            });
        });
        
        document.getElementById('viewBookings').addEventListener('click', () => {
            window.location.href = 'mybookings.php;
        });
        
        document.getElementById('bookAnother').addEventListener('click', () => {
            this.confirmationModal.classList.add('hidden');
            this.showCourtSelection();
        });

        // Enter key on filters triggers search
        const filterInputs = [this.courtType, this.courtCity, this.priceRange];
        filterInputs.forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.filterCourts();
                }
            });
        });
    }

    populateCityFilter() {
        const cities = [...new Set(this.courts.map(court => court.location.city))];
        cities.sort();
        
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            this.courtCity.appendChild(option);
        });
    }

    populateTimeSlots() {
        const startHour = 8; // 8 AM
        const endHour = 22; // 10 PM
        
        for (let hour = startHour; hour <= endHour; hour++) {
            const timeString = `${hour.toString().padStart(2, '0')}:00`;
            const option = document.createElement('option');
            option.value = timeString;
            option.textContent = timeString;
            this.bookingTime.appendChild(option);
        }
    }

    setMinDate() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const minDate = tomorrow.toISOString().split('T')[0];
        this.bookingDate.min = minDate;
        this.bookingDate.value = minDate;
    }

    filterCourts() {
        console.log('Filtering courts...');
        console.log('Current filters:', {
            type: this.courtType.value,
            city: this.courtCity.value,
            price: this.priceRange.value
        });

        const typeFilter = this.courtType.value.toLowerCase();
        const cityFilter = this.courtCity.value.toLowerCase();
        const priceFilter = this.priceRange.value;

        this.filteredCourts = this.courts.filter(court => {
            // Type filter
            const matchesType = !typeFilter || court.type === typeFilter;
            
            // City filter
            const matchesCity = !cityFilter || court.location.city === cityFilter;
            
            // Price filter
            let matchesPrice = true;
            if (priceFilter) {
                const price = court.pricePerHour;
                if (priceFilter.includes('-')) {
                    const [min, max] = priceFilter.split('-').map(Number);
                    matchesPrice = price >= min && price <= max;
                } else if (priceFilter.includes('+')) {
                    const min = parseInt(priceFilter);
                    matchesPrice = price >= min;
                }
            }

            return matchesType && matchesCity && matchesPrice;
        });

        console.log('Filtered courts:', this.filteredCourts);

        // Show loading state
        this.courtsGrid.innerHTML = '<div class="loading">Searching courts...</div>';
        
        // Render after a small delay to show loading state
        setTimeout(() => {
            this.renderCourts();
            
            // Show result message
            if (this.filteredCourts.length === 0) {
                this.courtsGrid.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>No courts found matching your criteria.</p>
                        <button class="btn-reset" onclick="window.location.reload()">
                            Reset Filters
                        </button>
                    </div>
                `;
            }
        }, 500);
    }

    renderCourts() {
        console.log('Rendering courts:', this.filteredCourts);
        this.courtsGrid.innerHTML = '';
        
        if (this.filteredCourts.length === 0) {
            this.courtsGrid.innerHTML = '<p class="no-results">No courts found matching your criteria.</p>';
            return;
        }

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
                <img src="${court.images[0]}" alt="${court.name}" onerror="this.src='images/default-court.jpg'">
                <div class="court-type-badge">${court.type}</div>
            </div>
            <div class="court-info">
                <h3 class="court-name">${court.name}</h3>
                <p class="court-location">📍 ${court.location.city}</p>
                <div class="court-details">
                    <span class="detail-item">
                        <i class="fas fa-users"></i> ${court.capacity} players
                    </span>
                    <span class="detail-item">
                        <i class="fas fa-dollar-sign"></i> $${court.pricePerHour}/hour
                    </span>
                </div>
                <div class="facilities-list">
                    ${court.facilities.map(facility => `
                        <span class="facility-tag">${facility}</span>
                    `).join('')}
                </div>
                <button class="btn-book">Book Now</button>
            </div>
        `;

        // Add click event to the Book Now button
        const bookButton = card.querySelector('.btn-book');
        bookButton.addEventListener('click', () => this.selectCourt(court));

        return card;
    }

    selectCourt(court) {
        this.selectedCourt = court;
        
        // Hide courts grid and show booking form
        this.courtsGrid.parentElement.style.display = 'none';
        this.bookingSection.style.display = 'block';
        
        // Update booking form with court details
        this.updateBookingForm(court);
    }

    updateBookingForm(court) {
        // Update court details in the form
        const courtName = this.bookingSection.querySelector('.court-name');
        const courtLocation = this.bookingSection.querySelector('.court-location');
        const courtPrice = this.bookingSection.querySelector('.court-rental-price');
        
        if (courtName) courtName.textContent = court.name;
        if (courtLocation) courtLocation.textContent = `${court.location.city}, ${court.location.address}`;
        if (courtPrice) courtPrice.textContent = `$${court.pricePerHour}`;
        
        // Set max players
        this.players.max = court.capacity;
        this.players.min = 2;
        
        // Update price summary
        this.updatePriceSummary();
    }

    updatePriceSummary() {
        if (!this.selectedCourt) return;
        
        const hours = parseInt(this.duration.value) || 1;
        const basePrice = this.selectedCourt.pricePerHour * hours;
        
        let servicesPrice = 0;
        if (this.equipmentCheckbox.checked) servicesPrice += this.bookingData.additionalServices.equipment;
        if (this.refereeCheckbox.checked) servicesPrice += this.bookingData.additionalServices.referee;
        if (this.lightingCheckbox.checked) servicesPrice += this.bookingData.additionalServices.lighting;
        
        const totalPrice = basePrice + servicesPrice;
        
        // Update summary items
        const summaryItems = this.bookingSection.querySelectorAll('.summary-item');
        summaryItems[0].querySelector('span:last-child').textContent = `$${basePrice}`;
        summaryItems[1].querySelector('span:last-child').textContent = `${hours} Hour${hours > 1 ? 's' : ''}`;
        summaryItems[2].querySelector('span:last-child').textContent = `$${totalPrice}`;
    }

    showCourtSelection() {
        this.bookingSection.style.display = 'none';
        document.querySelector('.booking-section').style.display = 'block';
        this.selectedCourt = null;
        this.renderCourts();
    }

    async handleBookingSubmit(e) {
        e.preventDefault();
        
        if (!this.selectedCourt) {
            alert('Please select a court first');
            return;
        }
        
        // Get form data
        const bookingData = {
            id: Date.now().toString(),
            courtId: this.selectedCourt.id,
            courtName: this.selectedCourt.name,
            date: this.bookingDate.value,
            time: this.bookingTime.value,
            duration: parseInt(this.duration.value),
            players: parseInt(this.players.value),
            totalPrice: parseFloat(this.totalPrice.textContent.replace('$', '')),
            status: 'Pending'
        };
        
        // Save booking to localStorage
        const bookings = JSON.parse(localStorage.getItem('bookings')) || [];
        bookings.push(bookingData);
        localStorage.setItem('bookings', JSON.stringify(bookings));
        
        // Show success message
        alert('Booking submitted successfully! You will receive a confirmation shortly.');
        
        // Reset form and show courts grid
        this.bookingForm.reset();
        this.bookingSection.style.display = 'none';
        this.courtsGrid.parentElement.style.display = 'block';
        this.selectedCourt = null;
    }

    showBookingConfirmation(booking) {
        const modal = this.confirmationModal;
        
        modal.querySelector('.booking-reference').textContent = booking.id;
        modal.querySelector('.court-name').textContent = this.selectedCourt.name;
        modal.querySelector('.booking-date').textContent = new Date(booking.date).toLocaleDateString();
        modal.querySelector('.booking-time').textContent = booking.time;
        modal.querySelector('.booking-duration').textContent = `${booking.duration} hour(s)`;
        modal.querySelector('.booking-price').textContent = `$${booking.totalPrice.toFixed(2)}`;
        
        modal.classList.remove('hidden');
    }

    updateSearchButtonState() {
        if (this.searchBtn) {
            this.searchBtn.style.backgroundColor = '#34a853';
            this.searchBtn.innerHTML = '<i class="fas fa-search"></i> Search Updated Courts';
            
            setTimeout(() => {
                this.searchBtn.style.backgroundColor = '#1a73e8';
                this.searchBtn.innerHTML = '<i class="fas fa-search"></i> Search Courts';
            }, 2000);
        }
    }
}

// Initialize the page when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing BookingPage');
    window.bookingPage = new BookingPage();
});

document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const bookButtons = document.querySelectorAll('.btn-book');
    const bookingForm = document.getElementById('bookingForm');
    const courtBookingForm = document.getElementById('courtBookingForm');
    const cancelButton = document.querySelector('.btn-cancel');
    const durationSelect = document.getElementById('duration');
    const summaryItems = document.querySelectorAll('.summary-item');

    // Add event listeners to book buttons
    bookButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courtCard = this.closest('.court-card');
            const courtName = courtCard.querySelector('h3').textContent;
            const courtPrice = courtCard.querySelector('.price').textContent;
            
            // Show booking form
            bookingForm.style.display = 'block';
            
            // Scroll to booking form
            bookingForm.scrollIntoView({ behavior: 'smooth' });
            
            // Update booking form with court details
            updateBookingSummary(courtPrice);
        });
    });

    // Cancel booking
    cancelButton.addEventListener('click', function() {
        bookingForm.style.display = 'none';
    });

    // Update booking summary when duration changes
    durationSelect.addEventListener('change', function() {
        const basePrice = document.querySelector('.court-card .price').textContent;
        updateBookingSummary(basePrice);
    });

    // Handle form submission
    courtBookingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = {
            date: document.getElementById('bookingDate').value,
            time: document.getElementById('bookingTime').value,
            duration: document.getElementById('duration').value,
            players: document.getElementById('players').value
        };

        // Here you would typically send this data to your server
        console.log('Booking submitted:', formData);
        
        // Show success message
        alert('Booking successful! You will receive a confirmation email shortly.');
        
        // Reset form and hide it
        courtBookingForm.reset();
        bookingForm.style.display = 'none';
    });

    // Helper function to update booking summary
    function updateBookingSummary(basePrice) {
        const duration = parseInt(durationSelect.value);
        const priceValue = parseInt(basePrice.replace(/[^0-9]/g, ''));
        const totalPrice = priceValue * duration;

        // Update duration in summary
        summaryItems[1].querySelector('span:last-child').textContent = `${duration} Hour${duration > 1 ? 's' : ''}`;
        
        // Update total price
        summaryItems[2].querySelector('span:last-child').textContent = `${totalPrice} EGP`;
    }

    // Set minimum date to today for booking date input
    const bookingDateInput = document.getElementById('bookingDate');
    const today = new Date().toISOString().split('T')[0];
    bookingDateInput.setAttribute('min', today);
}); 