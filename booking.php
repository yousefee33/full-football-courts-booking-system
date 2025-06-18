<?php

require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Initialize variables
$court_id = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;
$booking_date = isset($_POST['booking_date']) ? sanitize_input($_POST['booking_date']) : '';
$start_time = isset($_POST['start_time']) ? sanitize_input($_POST['start_time']) : '';
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 1;
$errors = [];
$success_message = '';

// Get court details if court_id is provided
$court = null;
if ($court_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM courts WHERE id = ? AND status = 'available'");
    $stmt->bind_param("i", $court_id);
    $stmt->execute();
    $court = $stmt->get_result()->fetch_assoc();
    
    if (!$court) {
        $errors[] = "Selected court is not available.";
    }
}

// Get all available courts for selection
$stmt = $conn->prepare("SELECT * FROM courts WHERE status = 'available' ORDER BY name");
$stmt->execute();
$available_courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Validate inputs
    if (empty($booking_date)) {
        $errors[] = "Booking date is required.";
    } elseif (strtotime($booking_date) < strtotime('today')) {
        $errors[] = "Booking date cannot be in the past.";
    }

    if (empty($start_time)) {
        $errors[] = "Start time is required.";
    }

    if ($duration < 1 || $duration > 4) {
        $errors[] = "Duration must be between 1 and 4 hours.";
    }

    $court_id = (int)$_POST['court_id'];
    if ($court_id <= 0) {
        $errors[] = "Please select a court.";
    }

    // Check if the selected time slot is available
    if (empty($errors)) {
        $booking_start = $booking_date . ' ' . $start_time;
        $booking_end = date('Y-m-d H:i:s', strtotime($booking_start . " +{$duration} hours"));

        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE court_id = ? 
            AND status = 'confirmed'
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND start_time < ?)
            )
        ");
        $stmt->bind_param("issssss", 
            $court_id, 
            $booking_end, $booking_start,
            $booking_end, $booking_start,
            $booking_start, $booking_end
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] > 0) {
            $errors[] = "Selected time slot is not available. Please choose another time.";
        }
    }

    // If no errors, store booking data in session and redirect to confirmation
    if (empty($errors)) {
        // Get court price
        $stmt = $conn->prepare("SELECT price_per_hour FROM courts WHERE id = ?");
        $stmt->bind_param("i", $court_id);
        $stmt->execute();
        $court = $stmt->get_result()->fetch_assoc();
        $total_price = $court['price_per_hour'] * $duration;

        // Store booking data in session
        $_SESSION['temp_booking'] = [
            'court_id' => $court_id,
            'start_datetime' => $booking_start,
            'end_datetime' => $booking_end,
            'duration' => $duration,
            'total_price' => $total_price
        ];

        // Redirect to confirmation page
        header('Location: confirmation.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Court - El 7arefaa</title>
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/booking.css">
    <style>
        /* Temporary fixes for basic styling */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
        }

        header {
            background-color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .logo h1 {
            margin: 0;
            color: #1a73e8;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .nav-links li {
            display: inline-block;
            margin-right: 1rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }

        .nav-links a.active {
            background-color: #1a73e8;
            color: white;
        }

        main {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .filters {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-group {
            margin-bottom: 1rem;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .courts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .court-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .court-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .court-info {
            padding: 1.5rem;
        }

        .btn-book {
            width: 100%;
            padding: 0.8rem;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 1rem;
        }

        .btn-book:hover {
            background-color: #1557b0;
        }

        footer {
            background-color: #333;
            color: white;
            padding: 2rem;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section a {
            color: white;
            text-decoration: none;
        }

        .social-links a {
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .search-button-container {
            display: flex;
            align-items: flex-end;
            margin-bottom: 0;
        }

        .btn-search {
            width: 100%;
            padding: 0.8rem 1.5rem;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .btn-search:hover {
            background-color: #1557b0;
        }

        .btn-search i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .search-button-container {
                margin-top: 1rem;
            }
        }

        .booking-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .success-message {
            color: #155724;
            background: #d4edda;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .price-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Header/Navbar -->
    <header>
        <div class="logo">
            <h1>⚽ El 7arefaa</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="home.php">Home</a></li>
                <li><a href="viewcourts.php">View Courts</a></li>
                <li><a href="booking.php" class="active">Book Court</a></li>
                <li><a href="mybookings.php">My Bookings</a></li>
                <li><a href="Dashboard.php">Admin Panel</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="booking-container">
        <h1>Book a Football Court</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="court_id">Select Court:</label>
                <select name="court_id" id="court_id" required>
                    <option value="">-- Select a Court --</option>
                    <?php foreach ($available_courts as $available_court): ?>
                        <option value="<?php echo $available_court['id']; ?>"
                                <?php echo ($court_id === $available_court['id']) ? 'selected' : ''; ?>
                                data-price="<?php echo $available_court['price_per_hour']; ?>">
                            <?php echo htmlspecialchars($available_court['name']); ?> 
                            ($<?php echo number_format($available_court['price_per_hour'], 2); ?>/hour)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="booking_date">Date:</label>
                <input type="date" id="booking_date" name="booking_date" 
                       min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo htmlspecialchars($booking_date); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="start_time">Start Time:</label>
                <input type="time" id="start_time" name="start_time" 
                       min="08:00" max="22:00"
                       value="<?php echo htmlspecialchars($start_time); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="duration">Duration (hours):</label>
                <select name="duration" id="duration" required>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>"
                                <?php echo ($duration === $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> hour<?php echo $i > 1 ? 's' : ''; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="price-preview" id="price-preview"></div>

            <button type="submit" name="submit_booking" class="btn btn-primary">
                Confirm Booking
            </button>
        </form>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-envelope"></i> info@el7arefaa.com</p>
                <p><i class="fas fa-phone"></i> +1 234 567 890</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="viewcourts.php">View Courts</a></li>
                    <li><a href="booking.php">Book Court</a></li>
                    <li><a href="mybookings.php">My Bookings</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/booking.js"></script>
    <script>
        function updatePricePreview() {
            const courtSelect = document.getElementById('court_id');
            const durationSelect = document.getElementById('duration');
            const pricePreview = document.getElementById('price-preview');

            if (courtSelect.value) {
                const pricePerHour = parseFloat(courtSelect.options[courtSelect.selectedIndex].dataset.price);
                const duration = parseInt(durationSelect.value);
                const totalPrice = pricePerHour * duration;

                pricePreview.innerHTML = `
                    <strong>Booking Summary:</strong><br>
                    Price per hour: $${pricePerHour.toFixed(2)}<br>
                    Duration: ${duration} hour${duration > 1 ? 's' : ''}<br>
                    <strong>Total Price: $${totalPrice.toFixed(2)}</strong>
                `;
            } else {
                pricePreview.innerHTML = '';
            }
        }

        // Add event listeners
        document.getElementById('court_id').addEventListener('change', updatePricePreview);
        document.getElementById('duration').addEventListener('change', updatePricePreview);

        // Initial price preview
        updatePricePreview();
    </script>
</body>
</html>
