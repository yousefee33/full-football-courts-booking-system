<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to view your bookings.', 'error');
}

$message = '';
$message_type = '';

// Handle booking deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = sanitize_input($_POST['booking_id']);
    
    // Get booking details to check ownership and status
    $stmt = $conn->prepare("
        SELECT b.*, c.name as court_name 
        FROM bookings b 
        JOIN courts c ON b.court_id = c.id 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if ($booking) {
        // Check if booking can be deleted (not too close to booking time)
        $booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
        $current_time = time();
        $hours_until_booking = ($booking_datetime - $current_time) / 3600;

        // Allow deletion if booking is more than 24 hours away or is pending
        if ($hours_until_booking >= 24 || $booking['status'] === 'pending') {
            // Delete the booking
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $message = "Booking for " . htmlspecialchars($booking['court_name']) . " has been cancelled successfully.";
                $message_type = "success";
            } else {
                $message = "Failed to cancel booking. Please try again.";
                $message_type = "error";
            }
        } else {
            $message = "Bookings can only be cancelled at least 24 hours before the scheduled time.";
            $message_type = "error";
        }
    } else {
        $message = "Invalid booking or you don't have permission to cancel this booking.";
        $message_type = "error";
    }
}

// Get user's bookings
$stmt = $conn->prepare("
    SELECT 
        b.*,
        c.name as court_name,
        c.location as court_location,
        c.price_per_hour
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.start_time ASC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get message from session if exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message'], $_SESSION['message_type']);
}

if (empty($_POST['booking_date']) || !strtotime($_POST['booking_date'])) {
    // Show error: Invalid date
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings</title>
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .bookings-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .bookings-table th, .bookings-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .bookings-table th { background-color: #f5f5f5; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.9em; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #383d41; }
        .btn { padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-primary { background-color: #007bff; color: white; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        .no-bookings { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>
    <header>
        <div class="logo">⚽ Football Admin Panel</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="mybooking.php" class="active">My Bookings</a>
            <a href="viewRating.php">Reviews</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <?php if (is_admin()): ?>
                <a href="Dashboard.php">Admin</a>
            <?php endif; ?>
        </nav>
        <div class="auth-buttons">
            <a href="profile.php" class="btn btn-outline">Profile</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>My Bookings</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="no-bookings">
                <p>You haven't made any bookings yet.</p>
                <a href="booking.php" class="btn btn-primary">Book a Court</a>
            </div>
        <?php else: ?>
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Court</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($booking['court_name']) ?><br>
                                <small><?= htmlspecialchars($booking['court_location']) ?></small>
                            </td>
                            <td><?= date('Y-m-d', strtotime($booking['booking_date'])) ?></td>
                            <td><?= htmlspecialchars($booking['start_time']) ?> - <?= htmlspecialchars($booking['end_time']) ?></td>
                            <td><?= htmlspecialchars($booking['duration_hours']) ?> hours</td>
                            <td>$<?= number_format($booking['total_price'], 2) ?></td>
                            <td>
                                <span class="status-badge status-<?= $booking['status'] ?>">
                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'approved'): ?>
                                    <?php
                                    $booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
                                    $current_time = time();
                                    $hours_until_booking = ($booking_datetime - $current_time) / 3600;
                                    ?>
                                    <?php if ($hours_until_booking >= 24 || $booking['status'] === 'pending'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" name="delete_booking" class="btn btn-danger">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-danger" disabled title="Bookings can only be cancelled at least 24 hours before the scheduled time">
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <!-- Edit Button -->
                                <a href="edit_booking.php?id=<?= $booking['id'] ?>" class="btn btn-warning" title="Edit">✏️</a>
                                <!-- Delete Button -->
                                <form method="POST" action="delete_booking.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" name="delete_booking" class="btn btn-danger" title="Delete">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>2025 Football Court. Book your football pitch online anytime, anywhere. Easy scheduling, affordable prices, and guaranteed fun</p>
                <div class="social-icons">
                    <a href="https://wa.me/966567780260" target="_blank" title="Chat on WhatsApp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
                    </a>
                    <a href="https://www.instagram.com/p.sweepy12" target="_blank" title="Follow us on Instagram">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram">
                    </a>
                    <a href="https://www.facebook.com/Pest%20sweepy" target="_blank" title="Follow us on Facebook">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                    </a>
                    <a href="https://twitter.com/pestsweepy" target="_blank" title="Follow us on Twitter">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6f/Logo_of_Twitter.svg" alt="Twitter">
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>If You Want To Get An Appointment</h3>
                <div class="contact-info">
                    <i class="phone-icon">📞</i>
                    <p>01204551879</p>
                </div>
                <div class="social-icons">
                    <a href="https://wa.me/966567780260" target="_blank" title="Chat on WhatsApp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
                    </a>
                    <a href="https://www.instagram.com/p.sweepy12" target="_blank" title="Follow us on Instagram">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram">
                    </a>
                    <a href="https://www.facebook.com/Pest%20sweepy" target="_blank" title="Follow us on Facebook">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                    </a>
                    <a href="https://twitter.com/pestsweepy" target="_blank" title="Follow us on Twitter">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6f/Logo_of_Twitter.svg" alt="Twitter">
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Court. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
