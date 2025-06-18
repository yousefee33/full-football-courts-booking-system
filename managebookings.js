// Get DOM elements
const bookingsContainer = document.getElementById('bookings-container');
const modal = document.getElementById('booking-details-modal');
const searchInput = document.getElementById('search-bookings');
const statusFilter = document.getElementById('status-filter');
const courtFilter = document.getElementById('court-filter');
const dateFilter = document.getElementById('date-filter');
const exportButton = document.getElementById('export-bookings');

// Load bookings from localStorage
let bookings = JSON.parse(localStorage.getItem('bookings')) || [];
let courts = JSON.parse(localStorage.getItem('courts')) || [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadBookings();
    populateFilters();
    setupEventListeners();
});

// Load bookings
function loadBookings(filterOptions = {}) {
    bookingsContainer.innerHTML = '';
    
    let filteredBookings = bookings;
    
    // Apply filters
    if (filterOptions.search) {
        const searchTerm = filterOptions.search.toLowerCase();
        filteredBookings = filteredBookings.filter(booking => 
            booking.courtName.toLowerCase().includes(searchTerm) ||
            booking.customerName.toLowerCase().includes(searchTerm) ||
            booking.bookingId.toLowerCase().includes(searchTerm)
        );
    }
    
    if (filterOptions.status) {
        filteredBookings = filteredBookings.filter(booking => booking.status === filterOptions.status);
    }
    
    if (filterOptions.court) {
        filteredBookings = filteredBookings.filter(booking => booking.courtId === filterOptions.court);
    }
    
    if (filterOptions.date) {
        filteredBookings = filteredBookings.filter(booking => booking.date === filterOptions.date);
    }
    
    // Create booking rows
    filteredBookings.forEach(booking => {
        const row = createBookingRow(booking);
        bookingsContainer.appendChild(row);
    });
    
    if (filteredBookings.length === 0) {
        showNoResults();
    }
}

// Create booking row
function createBookingRow(booking) {
    const row = document.createElement('tr');
    
    row.innerHTML = `
        <td>${booking.bookingId}</td>
        <td>${booking.courtName}</td>
        <td>${booking.customerName}</td>
        <td>${formatDate(booking.date)}</td>
        <td>${booking.time}</td>
        <td>${booking.duration} hour(s)</td>
        <td><span class="status-badge ${booking.status.toLowerCase()}">${booking.status}</span></td>
        <td>
            <button class="btn btn-outline btn-small view-btn" data-id="${booking.bookingId}">View</button>
            ${booking.status === 'Pending' ? `
                <button class="btn btn-success btn-small approve-btn" data-id="${booking.bookingId}">Approve</button>
                <button class="btn btn-danger btn-small reject-btn" data-id="${booking.bookingId}">Reject</button>
            ` : ''}
        </td>
    `;
    
    // Add event listeners
    row.querySelector('.view-btn').addEventListener('click', () => showBookingDetails(booking));
    
    if (booking.status === 'Pending') {
        row.querySelector('.approve-btn').addEventListener('click', () => approveBooking(booking));
        row.querySelector('.reject-btn').addEventListener('click', () => rejectBooking(booking));
    }
    
    return row;
}

// Show booking details in modal
function showBookingDetails(booking) {
    const modalContent = modal.querySelector('.modal-content');
    
    // Set booking information
    modalContent.querySelector('.booking-id').textContent = `Booking ID: ${booking.bookingId}`;
    modalContent.querySelector('.booking-status').textContent = `Status: ${booking.status}`;
    modalContent.querySelector('.booking-date').textContent = `Date: ${formatDate(booking.date)}`;
    modalContent.querySelector('.booking-time').textContent = `Time: ${booking.time}`;
    modalContent.querySelector('.booking-duration').textContent = `Duration: ${booking.duration} hour(s)`;
    modalContent.querySelector('.booking-price').textContent = `Total Price: $${booking.totalPrice}`;
    
    // Set court information
    const court = courts.find(c => c.id === booking.courtId);
    if (court) {
        modalContent.querySelector('.court-name').textContent = `Court: ${court.name}`;
        modalContent.querySelector('.court-type').textContent = `Type: ${court.type}`;
        modalContent.querySelector('.court-location').textContent = `Location: ${court.city}, ${court.address}`;
    }
    
    // Set customer information
    modalContent.querySelector('.customer-name').textContent = `Name: ${booking.customerName}`;
    modalContent.querySelector('.customer-email').textContent = `Email: ${booking.customerEmail}`;
    modalContent.querySelector('.customer-phone').textContent = `Phone: ${booking.customerPhone}`;
    
    // Set notes
    modalContent.querySelector('.booking-notes').textContent = booking.notes || 'No additional notes';
    
    // Show/hide action buttons based on status
    const approveBtn = modalContent.querySelector('.approve-btn');
    const rejectBtn = modalContent.querySelector('.reject-btn');
    const cancelBtn = modalContent.querySelector('.cancel-btn');
    
    if (booking.status === 'Pending') {
        approveBtn.style.display = 'block';
        rejectBtn.style.display = 'block';
        cancelBtn.style.display = 'none';
    } else if (['Approved', 'Completed'].includes(booking.status)) {
        approveBtn.style.display = 'none';
        rejectBtn.style.display = 'none';
        cancelBtn.style.display = 'block';
    } else {
        approveBtn.style.display = 'none';
        rejectBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
    
    // Add event listeners for action buttons
    approveBtn.onclick = () => {
        approveBooking(booking);
        modal.classList.remove('active');
    };
    
    rejectBtn.onclick = () => {
        rejectBooking(booking);
        modal.classList.remove('active');
    };
    
    cancelBtn.onclick = () => {
        cancelBooking(booking);
        modal.classList.remove('active');
    };
    
    modal.classList.add('active');
}

// Approve booking
function approveBooking(booking) {
    if (confirm('Are you sure you want to approve this booking?')) {
        const index = bookings.findIndex(b => b.bookingId === booking.bookingId);
        if (index !== -1) {
            bookings[index].status = 'Approved';
            localStorage.setItem('bookings', JSON.stringify(bookings));
            loadBookings();
            showNotification('Booking approved successfully!', 'success');
        }
    }
}

// Reject booking
function rejectBooking(booking) {
    if (confirm('Are you sure you want to reject this booking?')) {
        const index = bookings.findIndex(b => b.bookingId === booking.bookingId);
        if (index !== -1) {
            bookings[index].status = 'Rejected';
            localStorage.setItem('bookings', JSON.stringify(bookings));
            loadBookings();
            showNotification('Booking rejected successfully!', 'success');
        }
    }
}

// Cancel booking
function cancelBooking(booking) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const index = bookings.findIndex(b => b.bookingId === booking.bookingId);
        if (index !== -1) {
            bookings[index].status = 'Cancelled';
            localStorage.setItem('bookings', JSON.stringify(bookings));
            loadBookings();
            showNotification('Booking cancelled successfully!', 'success');
        }
    }
}

// Populate filters
function populateFilters() {
    // Populate court filter
    courts.forEach(court => {
        const option = document.createElement('option');
        option.value = court.id;
        option.textContent = court.name;
        courtFilter.appendChild(option);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Search input
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadBookings({ search: this.value });
        }, 300);
    });
    
    // Filters
    statusFilter.addEventListener('change', applyFilters);
    courtFilter.addEventListener('change', applyFilters);
    dateFilter.addEventListener('change', applyFilters);
    
    // Modal close button
    modal.querySelector('.close-modal').addEventListener('click', () => {
        modal.classList.remove('active');
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
    
    // Export bookings
    exportButton.addEventListener('click', exportBookings);
}

// Apply filters
function applyFilters() {
    loadBookings({
        search: searchInput.value,
        status: statusFilter.value,
        court: courtFilter.value,
        date: dateFilter.value
    });
}

// Show no results message
function showNoResults() {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td colspan="8" class="no-results">
            <h3>No bookings found</h3>
            <p>Try adjusting your filters or search terms</p>
        </td>
    `;
    bookingsContainer.appendChild(row);
}

// Export bookings
function exportBookings() {
    let csvContent = 'Booking ID,Court,Customer,Date,Time,Duration,Status\n';
    
    bookings.forEach(booking => {
        csvContent += `${booking.bookingId},${booking.courtName},${booking.customerName},${formatDate(booking.date)},${booking.time},${booking.duration},${booking.status}\n`;
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `bookings_${formatDate(new Date())}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
} 