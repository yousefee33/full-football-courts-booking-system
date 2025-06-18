// Constants
const STORAGE_KEY = 'footballCourtBookings';
const COURTS_STORAGE_KEY = 'footballCourts';

// DOM Elements
const bookingsList = document.getElementById('bookingsGrid');
const noBookingsMessage = document.getElementById('noBookings');
const searchInput = document.getElementById('searchBookings');
const statusFilter = document.getElementById('statusFilter');
const dateFilter = document.getElementById('dateFilter');
const sortBySelect = document.getElementById('sortBy');

// Modals
const bookingModal = document.getElementById('bookingModal');
const rescheduleModal = document.getElementById('rescheduleModal');
const rescheduleForm = document.getElementById('rescheduleForm');

// State
let currentBookings = [];
let selectedBookingId = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadBookings();
    setupEventListeners();
    // Add sample data if none exists
    if (currentBookings.length === 0) {
        addSampleBookings();
    }
});

// Add sample bookings for testing
function addSampleBookings() {
    const sampleBookings = [
        {
            id: '1',
            courtName: 'Court A1 - Indoor',
            status: 'confirmed',
            date: '2024-05-20',
            time: '14:00',
            duration: 1,
            totalPrice: 50,
            reference: 'BOOK001',
            customerName: 'John Doe',
            phone: '+201234567890',
            email: 'john@example.com',
            location: 'Main Branch',
            courtPrice: 40,
            servicesTotal: 10,
            services: [{ name: 'Equipment Rental', price: 10 }],
            notes: 'Need 2 footballs'
        },
        {
            id: '2',
            courtName: 'Court B2 - Outdoor',
            status: 'pending',
            date: '2024-05-21',
            time: '16:00',
            duration: 1,
            totalPrice: 45,
            reference: 'BOOK002',
            customerName: 'Jane Smith',
            phone: '+201234567891',
            email: 'jane@example.com',
            location: 'Branch 2',
            courtPrice: 35,
            servicesTotal: 10,
            services: [{ name: 'Water Bottles', price: 10 }],
            notes: ''
        }
    ];
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(sampleBookings));
    currentBookings = sampleBookings;
}

// Event Listeners Setup
function setupEventListeners() {
    // Search and Filter Events
    searchInput.addEventListener('input', filterAndSortBookings);
    statusFilter.addEventListener('change', filterAndSortBookings);
    dateFilter.addEventListener('change', filterAndSortBookings);
    sortBySelect.addEventListener('change', filterAndSortBookings);

    // Modal Close Buttons
    document.querySelectorAll('.modal-close').forEach(button => {
        button.addEventListener('click', () => {
            bookingModal?.classList.add('hidden');
            rescheduleModal?.classList.add('hidden');
        });
    });

    // Reschedule Form
    rescheduleForm.addEventListener('submit', handleReschedule);
    document.getElementById('cancelReschedule').addEventListener('click', () => {
        rescheduleModal.classList.add('hidden');
    });
}

// Load Bookings
function loadBookings() {
    const storedBookings = localStorage.getItem(STORAGE_KEY);
    currentBookings = storedBookings ? JSON.parse(storedBookings) : [];
    filterAndSortBookings();
}

// Filter and Sort Bookings
function filterAndSortBookings() {
    let filteredBookings = [...currentBookings];

    // Search Filter
    const searchTerm = searchInput.value.toLowerCase().trim();
    if (searchTerm) {
        filteredBookings = filteredBookings.filter(booking => 
            booking.courtName.toLowerCase().includes(searchTerm) ||
            booking.time.toLowerCase().includes(searchTerm) ||
            booking.reference.toLowerCase().includes(searchTerm)
        );
    }

    // Status Filter
    const selectedStatus = statusFilter.value.toLowerCase();
    if (selectedStatus && selectedStatus !== 'all status') {
        filteredBookings = filteredBookings.filter(booking => 
            booking.status.toLowerCase() === selectedStatus
        );
    }

    // Date Filter
    const selectedDateFilter = dateFilter.value.toLowerCase();
    if (selectedDateFilter && selectedDateFilter !== 'all time') {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        filteredBookings = filteredBookings.filter(booking => {
            const bookingDate = new Date(booking.date);
            bookingDate.setHours(0, 0, 0, 0);
            
            switch (selectedDateFilter) {
                case 'today':
                    return bookingDate.getTime() === today.getTime();
                case 'week': {
                    const weekEnd = new Date(today);
                    weekEnd.setDate(weekEnd.getDate() + 7);
                    return bookingDate >= today && bookingDate <= weekEnd;
                }
                case 'month':
                    return bookingDate.getMonth() === today.getMonth() &&
                           bookingDate.getFullYear() === today.getFullYear();
                default:
                    return true;
            }
        });
    }

    // Sort
    const sortBy = sortBySelect.value;
    filteredBookings.sort((a, b) => {
        switch (sortBy) {
            case 'date-desc':
                return new Date(b.date) - new Date(a.date);
            case 'date-asc':
                return new Date(a.date) - new Date(b.date);
            case 'price-desc':
                return b.totalPrice - a.totalPrice;
            case 'price-asc':
                return a.totalPrice - b.totalPrice;
            default:
                return 0;
        }
    });

    displayBookings(filteredBookings);
}

// Display Bookings
function displayBookings(bookings) {
    if (bookings.length === 0) {
        bookingsList.innerHTML = '';
        noBookingsMessage.classList.remove('hidden');
        return;
    }

    noBookingsMessage.classList.add('hidden');
    bookingsList.innerHTML = bookings.map(booking => `
        <div class="booking-card" data-id="${booking.id}">
            <div class="booking-header">
                <h3>${booking.courtName}</h3>
                <span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span>
            </div>
            
            <div class="booking-info">
                <div class="info-row">
                    <span class="label">Date:</span>
                    <span>${new Date(booking.date).toLocaleDateString()}</span>
                </div>
                <div class="info-row">
                    <span class="label">Time:</span>
                    <span>${booking.time}</span>
                </div>
                <div class="info-row">
                    <span class="label">Reference:</span>
                    <span>${booking.reference}</span>
                </div>
                <div class="info-row">
                    <span class="label">Total Price:</span>
                    <span>$${booking.totalPrice.toFixed(2)}</span>
                </div>
            </div>
            
            <div class="booking-actions">
                <button class="btn btn-primary view-details-btn" onclick="viewBookingDetails('${booking.id}')">
                    View Details
                </button>
                ${booking.status === 'Confirmed' ? `
                    <button class="btn btn-secondary reschedule-btn" onclick="openRescheduleModal('${booking.id}')">
                        Reschedule
                    </button>
                    <button class="btn btn-danger cancel-btn" onclick="cancelBooking('${booking.id}')">
                        Cancel
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// View Booking Details
function viewBookingDetails(bookingId) {
    const booking = currentBookings.find(b => b.id === bookingId);
    if (!booking) return;

    // Populate modal with booking details
    const modal = document.getElementById('bookingModal');
    modal.querySelector('.court-name').textContent = booking.courtName;
    modal.querySelector('.court-location').textContent = booking.location;
    modal.querySelector('.booking-reference').textContent = booking.reference;
    modal.querySelector('.booking-status').textContent = booking.status;
    modal.querySelector('.booking-date').textContent = new Date(booking.date).toLocaleDateString();
    modal.querySelector('.booking-time').textContent = booking.time;
    modal.querySelector('.booking-duration').textContent = `${booking.duration} hours`;
    modal.querySelector('.customer-name').textContent = booking.customerName;
    modal.querySelector('.customer-phone').textContent = booking.phone;
    modal.querySelector('.customer-email').textContent = booking.email;
    
    // Services
    const servicesList = modal.querySelector('.services-list');
    servicesList.innerHTML = booking.services.map(service => `
        <div class="service-item">
            <span class="service-name">${service.name}</span>
            <span class="service-price">$${service.price.toFixed(2)}</span>
        </div>
    `).join('');

    // Price Summary
    modal.querySelector('.rental-price').textContent = `$${booking.courtPrice.toFixed(2)}`;
    modal.querySelector('.services-price').textContent = `$${booking.servicesTotal.toFixed(2)}`;
    modal.querySelector('.total-price').textContent = `$${booking.totalPrice.toFixed(2)}`;
    
    // Notes
    modal.querySelector('.booking-notes').textContent = booking.notes || 'No special requests';

    // Show/Hide action buttons based on status
    const rescheduleBtn = modal.querySelector('.reschedule-btn');
    const cancelBtn = modal.querySelector('.cancel-btn');
    if (booking.status === 'Confirmed') {
        rescheduleBtn.style.display = 'block';
        cancelBtn.style.display = 'block';
    } else {
        rescheduleBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }

    modal.classList.remove('hidden');
}

// Reschedule Booking
function openRescheduleModal(bookingId) {
    selectedBookingId = bookingId;
    const booking = currentBookings.find(b => b.id === bookingId);
    if (!booking) return;

    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('newDate').min = tomorrow.toISOString().split('T')[0];

    // Populate time slots
    const timeSelect = document.getElementById('newTime');
    timeSelect.innerHTML = generateTimeSlots();

    rescheduleModal.classList.remove('hidden');
}

function generateTimeSlots() {
    const slots = [];
    for (let hour = 8; hour < 22; hour++) {
        slots.push(`<option value="${hour}:00">${hour}:00</option>`);
        slots.push(`<option value="${hour}:30">${hour}:30</option>`);
    }
    return slots.join('');
}

function handleReschedule(event) {
    event.preventDefault();
    
    const newDate = document.getElementById('newDate').value;
    const newTime = document.getElementById('newTime').value;
    
    if (!selectedBookingId || !newDate || !newTime) return;

    const bookingIndex = currentBookings.findIndex(b => b.id === selectedBookingId);
    if (bookingIndex === -1) return;

    // Update booking
    currentBookings[bookingIndex].date = newDate;
    currentBookings[bookingIndex].time = newTime;

    // Save to localStorage
    localStorage.setItem(STORAGE_KEY, JSON.stringify(currentBookings));

    // Close modal and refresh display
    rescheduleModal.classList.add('hidden');
    filterAndSortBookings();

    // Show success message
    showNotification('Booking rescheduled successfully!');
}

// Cancel Booking
function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;

    const bookingIndex = currentBookings.findIndex(b => b.id === bookingId);
    if (bookingIndex === -1) return;

    // Update status
    currentBookings[bookingIndex].status = 'Cancelled';

    // Save to localStorage
    localStorage.setItem(STORAGE_KEY, JSON.stringify(currentBookings));

    // Refresh display
    filterAndSortBookings();

    // Show success message
    showNotification('Booking cancelled successfully!');
}

// Utility Functions
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
} 