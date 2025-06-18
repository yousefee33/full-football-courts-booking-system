<?php
require_once 'config.php';


// Handle newsletter subscription
if (isset($_POST['newsletter_email'])) {
    $email = filter_var($_POST['newsletter_email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("INSERT INTO newsletters (email, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $newsletter_success = "Thank you for subscribing!";
        } else {
            $newsletter_error = "Error subscribing. Please try again.";
        }
    } else {
        $newsletter_error = "Please enter a valid email address.";
    }
}

// Handle quick booking form
if (isset($_POST['quick_book'])) {
    $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);
    $court_type = filter_var($_POST['court_type'], FILTER_SANITIZE_STRING);
    $book_date = filter_var($_POST['book_date'], FILTER_SANITIZE_STRING);
    
    // Validate date
    if (strtotime($book_date) < strtotime('today')) {
        $booking_error = "Please select a future date.";
    } else {
        // Redirect to booking page with parameters
        header("Location: booking.php?city=" . urlencode($city) . 
               "&type=" . urlencode($court_type) . 
               "&date=" . urlencode($book_date));
        exit();
    }
}

// Fetch featured courts
$featured_courts = [];
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(b.id) as booking_count,
           AVG(r.rating) as average_rating
    FROM courts c
    LEFT JOIN bookings b ON c.id = b.court_id
    LEFT JOIN reviews r ON c.id = r.court_id
    WHERE c.status = 'available'
    GROUP BY c.id
    ORDER BY booking_count DESC
    LIMIT 6
");
$stmt->execute();
$result = $stmt->get_result();
while ($court = $result->fetch_assoc()) {
    $featured_courts[] = $court;
}

// Get statistics
$stats = [
    'courts' => 0,
    'players' => 0,
    'rating' => 0
];

$result = $conn->query("SELECT COUNT(*) as court_count FROM courts");
$stats['courts'] = $result->fetch_assoc()['court_count'];

$result = $conn->query("SELECT COUNT(DISTINCT user_id) as player_count FROM bookings");
$stats['players'] = $result->fetch_assoc()['player_count'];

$result = $conn->query("SELECT AVG(rating) as avg_rating FROM reviews");
$stats['rating'] = number_format($result->fetch_assoc()['avg_rating'], 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El 7arefaa Court - Book Your Perfect Pitch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/home.css">
</head>
<body>
    <header>
        <div class="logo">
            <h1>⚽ El 7arefaa Court</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="home.php" class="active">Home</a></li>
                <li><a href="viewcourts.php">View Courts</a></li>
                <li><a href="booking.php">Book Court</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="mybookings.php">My Bookings</a></li>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li><a href="dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Find and Book Your Perfect El 7arefaa Court</h1>
                <p>Choose from hundreds of indoor and outdoor courts across Egypt</p>
                <div class="hero-search">
                    <?php if (isset($booking_error)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($booking_error); ?></div>
                    <?php endif; ?>
                    <form id="quickBookForm" class="quick-search-form" method="POST">
                        <div class="search-inputs">
                            <div class="search-group">
                                <i class="fas fa-map-marker-alt"></i>
                                <select id="quickBookCity" name="city" required>
                                    <option value="">Select City</option>
                                    <option value="cairo">Cairo</option>
                                    <option value="alexandria">Alexandria</option>
                                    <option value="giza">Giza</option>
                                </select>
                            </div>
                            <div class="search-group">
                                <i class="fas fa-futbol"></i>
                                <select id="quickBookType" name="court_type" required>
                                    <option value="">Court Type</option>
                                    <option value="indoor">Indoor</option>
                                    <option value="outdoor">Outdoor</option>
                                </select>
                            </div>
                            <div class="search-group">
                                <i class="fas fa-calendar"></i>
                                <input type="date" id="quickBookDate" name="book_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <button type="submit" name="quick_book" class="btn btn-primary">
                            <i class="fas fa-search"></i> Find Courts
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <h2>Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-futbol"></i>
                    <h3>Quality Courts</h3>
                    <p>Premium football courts with top-notch facilities</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Real-time Booking</h3>
                    <p>Book your court instantly, 24/7</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Multiple Locations</h3>
                    <p>Courts available across all major cities</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-star"></i>
                    <h3>Best Prices</h3>
                    <p>Competitive prices and special offers</p>
                </div>
            </div>
        </section>

        <!-- Popular Courts Section -->
        <section class="popular-courts">
            <h2>Featured Courts</h2>
            <div class="courts-grid">
                <?php foreach ($featured_courts as $court): ?>
                    <div class="court-card">
                        <img src="<?php echo htmlspecialchars($court['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($court['name']); ?>">
                        <div class="court-info">
                            <h3><?php echo htmlspecialchars($court['name']); ?></h3>
                            <p><?php echo htmlspecialchars($court['description']); ?></p>
                            <div class="court-meta">
                                <span class="price">$<?php echo number_format($court['price_per_hour'], 2); ?>/hour</span>
                                <span class="rating">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($court['average_rating'], 1); ?>
                                </span>
                            </div>
                            <a href="booking.php?court_id=<?php echo $court['id']; ?>" 
                               class="btn btn-primary">Book Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="viewcourts.php" class="btn btn-outline">View All Courts</a>
            </div>
        </section>

        <!-- Quick Book Section -->
        <section class="cta-section">
            <div class="cta-content">
                <h2>Ready to Play?</h2>
                <p>Book your court now and enjoy the game with your friends!</p>
                <div class="cta-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['courts']; ?>+</span>
                        <span class="stat-label">Courts</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['players']; ?>+</span>
                        <span class="stat-label">Happy Players</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['rating']; ?></span>
                        <span class="stat-label">Average Rating</span>
                    </div>
                </div>
                <a href="booking.php" class="btn btn-primary">Book a Court Now</a>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-phone"></i> +20 120 455 1879</p>
                <p><i class="fas fa-envelope"></i> info@el7arefaa.com</p>
                <div class="social-links">
                    <a href="https://wa.me/966567780260" target="_blank" title="Chat on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="https://www.instagram.com/p.sweepy12" target="_blank">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.facebook.com/Pest%20sweepy" target="_blank">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://twitter.com/pestsweepy" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to get special offers and updates</p>
                <?php if (isset($newsletter_success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($newsletter_success); ?></div>
                <?php endif; ?>
                <?php if (isset($newsletter_error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($newsletter_error); ?></div>
                <?php endif; ?>
                <form class="newsletter-form" method="POST">
                    <input type="email" name="newsletter_email" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> El 7arefaa Court. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Add minimum date to date input
        document.getElementById('quickBookDate').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>