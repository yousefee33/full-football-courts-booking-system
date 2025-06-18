<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Initialize variables
$booking_data = isset($_SESSION['temp_booking']) ? $_SESSION['temp_booking'] : null;

// If no booking data in session, redirect to booking page
if (!$booking_data) {
    header('Location: booking.php');
    exit();
}

// Get court details
$court_id = $booking_data['court_id'];
$stmt = $conn->prepare("SELECT * FROM courts WHERE id = ?");
$stmt->bind_param("i", $court_id);
$stmt->execute();
$court = $stmt->get_result()->fetch_assoc();

// Process confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Insert booking into database
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                user_id, court_id, start_time, end_time, 
                duration_hours, total_price, status, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        
        $stmt->bind_param("iissid", 
            $_SESSION['user_id'],
            $booking_data['court_id'],
            $booking_data['start_datetime'],
            $booking_data['end_datetime'],
            $booking_data['duration'],
            $booking_data['total_price']
        );

        if ($stmt->execute()) {
            $booking_id = $stmt->insert_id;
            // Clear temporary booking data
            unset($_SESSION['temp_booking']);
            
            // Redirect to success page
            $_SESSION['booking_success'] = [
                'booking_id' => $booking_id,
                'message' => 'Your booking has been confirmed successfully!'
            ];
            header('Location: booking_success.php');
            exit();
        } else {
            $error = "Error processing your booking. Please try again.";
        }
    } elseif (isset($_POST['cancel'])) {
        // Clear temporary booking data and redirect back to booking page
        unset($_SESSION['temp_booking']);
        header('Location: booking.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-details {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .total-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: #28a745;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #dee2e6;
        }
        .buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-confirm {
            flex: 1;
            padding: 1rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-cancel {
            flex: 1;
            padding: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .warning-text {
            margin-top: 1rem;
            color: #856404;
            background-color: #fff3cd;
            padding: 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
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
                <?php if (is_admin()): ?>
                    <li><a href="dashboard.php">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="confirmation-container">
        <h1>Confirm Your Booking</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="booking-details">
            <div class="detail-row">
                <span>Court:</span>
                <span><?php echo htmlspecialchars($court['name']); ?></span>
            </div>
            <div class="detail-row">
                <span>Date:</span>
                <span><?php echo date('F j, Y', strtotime($booking_data['start_datetime'])); ?></span>
            </div>
            <div class="detail-row">
                <span>Time:</span>
                <span><?php echo date('g:i A', strtotime($booking_data['start_datetime'])); ?> - 
                      <?php echo date('g:i A', strtotime($booking_data['end_datetime'])); ?></span>
            </div>
            <div class="detail-row">
                <span>Duration:</span>
                <span><?php echo $booking_data['duration']; ?> hour<?php echo $booking_data['duration'] > 1 ? 's' : ''; ?></span>
            </div>
            <div class="detail-row total-price">
                <span>Total Price:</span>
                <span>$<?php echo number_format($booking_data['total_price'], 2); ?></span>
            </div>
        </div>

        <div class="warning-text">
            <i class="fas fa-exclamation-triangle"></i>
            Please note that once confirmed, cancellations are subject to our cancellation policy.
        </div>

        <form method="POST" class="buttons">
            <button type="submit" name="cancel" class="btn-cancel">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" name="confirm" class="btn-confirm">
                <i class="fas fa-check"></i> Confirm Booking
            </button>
        </form>
    </main>

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
</body>
</html>
