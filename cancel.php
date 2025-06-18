<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to view this page', 'error');
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? sanitize_input($_GET['id']) : '';

if (empty($booking_id)) {
    redirect('mybookings.php', 'Invalid booking selected', 'error');
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, c.name as court_name 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('mybookings.php', 'Booking not found or unauthorized', 'error');
}

$booking = $result->fetch_assoc();

// Check if booking is already cancelled
if ($booking['status'] === 'cancelled') {
    redirect('mybookings.php', 'This booking is already cancelled', 'error');
}

// Check if booking is in the future
$booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
if ($booking_datetime < time()) {
    redirect('mybookings.php', 'Cannot cancel past bookings', 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking - El 7arefaa</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/cancel.css">
    <style>
        .cancel-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-details {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .booking-details p {
            margin: 0.5rem 0;
        }
        .cancel-warning {
            color: #dc3545;
            margin: 1rem 0;
            padding: 1rem;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background: #fff8f8;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
        }
        .btn-cancel:hover {
            background: #c82333;
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        .btn-back:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">⚽ El 7arefaa</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="auth-buttons">
            <?php if (is_logged_in()): ?>
                <a href="profile.php" class="btn btn-outline">Profile</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="page-container">
        <div class="cancel-container">
            <h1>Cancel Booking</h1>
            
            <div class="booking-details">
                <h2>Booking Details</h2>
                <p><strong>Court:</strong> <?php echo htmlspecialchars($booking['court_name']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['booking_date']); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($booking['start_time']) . ' - ' . htmlspecialchars($booking['end_time']); ?></p>
                <p><strong>Total Price:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
            </div>

            <div class="cancel-warning">
                <h3>⚠️ Warning</h3>
                <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
            </div>

            <form action="process_cancel_booking.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
                
                <div class="button-group">
                    <button type="submit" class="btn btn-cancel">Cancel Booking</button>
                    <a href="mybookings.php" class="btn btn-back">Go Back</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>2025 El 7arefaa. Book your football pitch online anytime, anywhere.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 El 7arefaa. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
