// Featured courts data
const featuredCourts = [
    {
        id: 1,
        name: "Olympic Court",
        type: "Indoor",
        city: "Cairo",
        facilities: ["Changing Rooms", "Parking", "Lighting"],
        price: 200,
        imageUrl: "https://images.unsplash.com/photo-1552667466-07770ae110d0?q=80&w=1000",
        rating: 4.5,
        availability: "Available"
    },
    {
        id: 2,
        name: "Champions Arena",
        type: "Outdoor",
        city: "Alexandria",
        facilities: ["Showers", "Cafeteria", "Parking"],
        price: 150,
        imageUrl: "https://images.unsplash.com/photo-1459865264687-595d652de67e?q=80&w=1000",
        rating: 4.2,
        availability: "Available"
    },
    {
        id: 3,
        name: "Sports Complex A",
        type: "Indoor",
        city: "Giza",
        facilities: ["Lighting", "Parking", "First Aid"],
        price: 180,
        imageUrl: "https://images.unsplash.com/photo-1624923686627-514dd5e57bae?q=80&w=1000",
        rating: 4.0,
        availability: "Booked"
    }
];

// Load featured courts
document.addEventListener('DOMContentLoaded', () => {
    loadFeaturedCourts();
    setupQuickBook();
    setupNewsletter();
});

// Render featured courts
function loadFeaturedCourts() {
    const courtsGrid = document.getElementById('featuredCourts');
    
    courtsGrid.innerHTML = featuredCourts.map(court => `
        <div class="court-card">
            <div class="court-image">
                <img src="${court.imageUrl}" alt="${court.name}">
                <span class="availability-badge ${court.availability.toLowerCase()}">${court.availability}</span>
            </div>
            <div class="court-content">
                <div class="court-header">
                    <h3>${court.name}</h3>
                    <div class="rating">
                        <span class="stars">${'★'.repeat(Math.floor(court.rating))}${'☆'.repeat(5-Math.floor(court.rating))}</span>
                        <span class="rating-value">${court.rating}</span>
                    </div>
                </div>
                <div class="court-info">
                    <p><i class="fas fa-map-marker-alt"></i> ${court.city}</p>
                    <p><i class="fas fa-futbol"></i> ${court.type}</p>
                    <p><i class="fas fa-dollar-sign"></i> ${court.price} EGP/hour</p>
                </div>
                <div class="facilities-list">
                    ${court.facilities.map(facility => `
                        <span class="facility-tag">${facility}</span>
                    `).join('')}
                </div>
                <div class="court-actions">
                    <button class="btn btn-primary" onclick="bookCourt(${court.id})">Book Now</button>
                    <button class="btn btn-secondary" onclick="viewDetails(${court.id})">View Details</button>
                </div>
            </div>
        </div>
    `).join('');
}

// Setup quick booking form
function setupQuickBook() {
    const quickBookForm = document.getElementById('quickBookForm');
    
    // Set minimum date to today
    const dateInput = document.getElementById('quickBookDate');
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    dateInput.value = today;
    
    quickBookForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const city = document.getElementById('quickBookCity').value;
        const type = document.getElementById('quickBookType').value;
        const date = document.getElementById('quickBookDate').value;
        
        // Redirect to view courts page with filters
        window.location.href = `viewcourts.phpity=${city}&type=${type}&date=${date}`;
    });
}

// Setup newsletter form
function setupNewsletter() {
    const newsletterForm = document.querySelector('.newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email = newsletterForm.querySelector('input[type="email"]').value;
            
            // Show success message
            showToast('Thanks for subscribing! 🎉', 'success');
            
            // Reset form
            newsletterForm.reset();
        });
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Court actions
function bookCourt(courtId) {
    window.location.href = `booking.phprtId=${courtId}`;
}

function viewDetails(courtId) {
    window.location.href = `courtdetails.phprtId=${courtId}`;
} 