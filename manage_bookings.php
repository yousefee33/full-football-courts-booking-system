<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('login.php', 'Access denied. Admin privileges required.', 'error');
}

$message = '';
$message_type = '';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $booking_id = sanitize_input($_POST['booking_id']);
        $action = sanitize_input($_POST['action']);
        
        // Validate action
        $valid_actions = ['approve', 'reject', 'cancel'];
        if (!in_array($action, $valid_actions)) {
            $message = "Invalid action.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $action, $booking_id);
            
            if ($stmt->execute()) {
                $message = "Booking " . ucfirst($action) . "d successfully.";
                $message_type = "success";
            } else {
                $message = "Error updating booking status.";
                $message_type = "error";
            }
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$court_filter = isset($_GET['court']) ? sanitize_input($_GET['court']) : '';
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build the query
$query = "
    SELECT 
        b.*,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        c.name as court_name,
        c.price_per_hour
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    WHERE 1=1
";
$params = [];
$types = "";

if ($status_filter) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($court_filter) {
    $query .= " AND b.court_id = ?";
    $params[] = $court_filter;
    $types .= "i";
}

if ($date_filter) {
    $query .= " AND DATE(b.booking_date) = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($search_query) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY b.booking_date DESC, b.start_time ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all courts for the filter dropdown
$courts = $conn->query("SELECT id, name FROM courts ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle export functionality
if (isset($_POST['export_bookings'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings_report.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Booking ID', 'Court', 'Customer', 'Date', 'Time', 'Duration', 'Status', 'Total Price']);
    
    foreach ($bookings as $booking) {
        fputcsv($output, [
            $booking['id'],
            $booking['court_name'],
            $booking['customer_name'],
            $booking['booking_date'],
            $booking['start_time'] . ' - ' . $booking['end_time'],
            $booking['duration_hours'],
            $booking['status'],
            $booking['total_price']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/managebookings.css">
    <script src="../js/managebookings.js" defer></script>
</head>
<body>
    <header>
        <div class="logo">⚽ Football Admin Panel</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="mybooking.php">My Bookings</a>
            <a href="viewRating.php">Reviews</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="Dashboard.php" class="active">Admin</a>
        </nav>
        <div class="auth-buttons">
            <a href="profile.php" class="btn btn-outline">Profile</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </header>

    <main class="bookings-page">
        <div class="page-header">
            <div class="header-content">
                <h1>Manage Bookings</h1>
                <p>View and manage all court bookings</p>
            </div>
            <div class="header-actions">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="export_bookings" class="btn btn-primary">
                        <span class="icon">📊</span> Export Report
                    </button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="filters-section">
            <form method="GET" class="search-form">
                <div class="search-bar">
                    <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search bookings...">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
                <div class="filters">
                    <select name="status" id="status-filter">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <select name="court" id="court-filter">
                        <option value="">All Courts</option>
                        <?php foreach ($courts as $court): ?>
                            <option value="<?= $court['id'] ?>" <?= $court_filter == $court['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($court['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="date" id="date-filter" value="<?= htmlspecialchars($date_filter) ?>" class="date-input">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="bookings-table-container">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Court</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['id']) ?></td>
                            <td><?= htmlspecialchars($booking['court_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($booking['customer_name']) ?><br>
                                <small><?= htmlspecialchars($booking['customer_email']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                            <td><?= htmlspecialchars($booking['start_time']) ?> - <?= htmlspecialchars($booking['end_time']) ?></td>
                            <td><?= htmlspecialchars($booking['duration_hours']) ?> hours</td>
                            <td>
                                <span class="status-badge status-<?= $booking['status'] ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] !== 'cancelled'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-warning btn-sm">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                    <button class="btn btn-info btn-sm view-details" data-booking-id="<?= $booking['id'] ?>">Details</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Booking Details Modal -->
        <div class="modal" id="booking-details-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Booking Details</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="booking-info">
                        <div class="info-section">
                            <h3>Booking Information</h3>
                            <p class="booking-id"></p>
                            <p class="booking-status"></p>
                            <p class="booking-date"></p>
                            <p class="booking-time"></p>
                            <p class="booking-duration"></p>
                            <p class="booking-price"></p>
                        </div>
                        <div class="info-section">
                            <h3>Court Information</h3>
                            <p class="court-name"></p>
                            <p class="court-type"></p>
                            <p class="court-location"></p>
                        </div>
                        <div class="info-section">
                            <h3>Customer Information</h3>
                            <p class="customer-name"></p>
                            <p class="customer-email"></p>
                            <p class="customer-phone"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

    <script>
        // Add JavaScript for modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('booking-details-modal');
            const closeBtn = document.querySelector('.close-modal');
            const viewButtons = document.querySelectorAll('.view-details');

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.dataset.bookingId;
                    // Fetch booking details via AJAX and populate modal
                    fetch(`get_booking_details.php?id=${bookingId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Populate modal with booking details
                            document.querySelector('.booking-id').textContent = `Booking ID: ${data.id}`;
                            document.querySelector('.booking-status').textContent = `Status: ${data.status}`;
                            document.querySelector('.booking-date').textContent = `Date: ${data.booking_date}`;
                            document.querySelector('.booking-time').textContent = `Time: ${data.start_time} - ${data.end_time}`;
                            document.querySelector('.booking-duration').textContent = `Duration: ${data.duration_hours} hours`;
                            document.querySelector('.booking-price').textContent = `Total Price: $${data.total_price}`;
                            
                            document.querySelector('.court-name').textContent = `Court: ${data.court_name}`;
                            document.querySelector('.court-type').textContent = `Type: ${data.court_type}`;
                            document.querySelector('.court-location').textContent = `Location: ${data.court_location}`;
                            
                            document.querySelector('.customer-name').textContent = `Name: ${data.customer_name}`;
                            document.querySelector('.customer-email').textContent = `Email: ${data.customer_email}`;
                            document.querySelector('.customer-phone').textContent = `Phone: ${data.customer_phone}`;
                            
                            modal.style.display = 'block';
                        });
                });
            });

            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 