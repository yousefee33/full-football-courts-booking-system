<?php
require_once 'config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle booking cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    // Check if booking belongs to user and is in the future
    $stmt = $conn->prepare("
        SELECT booking_date, status 
        FROM bookings 
        WHERE id = ? AND user_id = ? 
        AND booking_date > NOW()
        AND status != 'cancelled'
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update booking status to cancelled
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'cancelled', 
                cancelled_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $booking_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Booking cancelled successfully.";
        } else {
            $error_message = "Error cancelling booking. Please try again.";
        }
    } else {
        $error_message = "Invalid booking or booking cannot be cancelled.";
    }
}

// Fetch user's bookings
$user_id = $_SESSION['user_id'];
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$bookings_per_page = 5;
$offset = ($current_page - 1) * $bookings_per_page;

// Get total bookings count for pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM bookings b 
    WHERE b.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $bookings_per_page);

// Fetch bookings with court details
$stmt = $conn->prepare("
    SELECT 
        b.id,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.created_at,
        b.total_price,
        c.name as court_name,
        c.image_url,
        c.price_per_hour
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $bookings_per_page, $offset);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .bookings-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .booking-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            display: flex;
            gap: 2rem;
        }
        .booking-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .booking-details {
            flex: 1;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .booking-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .booking-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-completed {
            background: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .booking-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-item i {
            color: #6c757d;
            width: 20px;
        }
        .booking-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.3s;
        }
        .btn-cancel:hover {
            background: #c82333;
        }
        .btn-cancel:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #007bff;
            text-decoration: none;
        }
        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .empty-bookings {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .empty-bookings i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .booking-card {
                flex-direction: column;
            }
            .booking-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <h1>⚽ El 7arefaa Court</h1>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="home.php">Home</a></li>
                <li><a href="viewcourts.php">View Courts</a></li>
                <li><a href="booking.php">Book Court</a></li>
                <li><a href="mybookings.php" class="active">My Bookings</a></li>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li><a href="dashboard.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </div>
    </header>

    <main class="bookings-container">
        <h1>My Bookings</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="empty-bookings">
                <i class="fas fa-calendar-times"></i>
                <h2>No Bookings Found</h2>
                <p>You haven't made any bookings yet.</p>
                <a href="booking.php" class="btn btn-primary">Book a Court Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($booking['court_name']); ?>" 
                         class="booking-image">
                    
                    <div class="booking-details">
                        <div class="booking-header">
                            <h2 class="booking-title"><?php echo htmlspecialchars($booking['court_name']); ?></h2>
                            <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                            </span>
                        </div>

                        <div class="booking-info">
                            <div class="info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>
                                    <?php 
                                    echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . 
                                         date('g:i A', strtotime($booking['end_time']));
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-history"></i>
                                <span>Booked on <?php echo date('M j, Y', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>

                        <?php if ($booking['status'] != 'cancelled' && strtotime($booking['booking_date']) > time()): ?>
                            <div class="booking-actions">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn-cancel">
                                        <i class="fas fa-times"></i> Cancel Booking
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p><i class="fas fa-phone"></i> +20 120 455 1879</p>
                <p><i class="fas fa-envelope"></i> info@el7arefaa.com</p>
                <div class="social-links">
                    <a href="https://wa.me/966567780260" target="_blank">
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
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> El 7arefaa Court. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 