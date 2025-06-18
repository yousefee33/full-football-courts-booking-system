<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to view bookings', 'error');
}

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $booking_id = sanitize_input($_POST['booking_id']);
        
        switch ($_POST['action']) {
            case 'cancel':
                // Cancel booking
                $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    redirect('viewBooking.php', 'Booking cancelled successfully', 'success');
                }
                break;
                
            case 'delete':
                // Only admin can delete bookings
                if (is_admin()) {
                    $sql = "DELETE FROM bookings WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $booking_id);
                    if ($stmt->execute()) {
                        redirect('viewBooking.php', 'Booking deleted successfully', 'success');
                    }
                }
                break;
        }
    }
}

// Get bookings based on user role
if (is_admin()) {
    $sql = "SELECT b.*, u.name as user_name, c.name as court_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN courts c ON b.court_id = c.id 
            ORDER BY b.booking_date DESC, b.start_time DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT b.*, u.name as user_name, c.name as court_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN courts c ON b.court_id = c.id 
            WHERE b.user_id = ? 
            ORDER BY b.booking_date DESC, b.start_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-pending { color: #ffc107; }
        .status-confirmed { color: #28a745; }
        .status-cancelled { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">
            <?php echo is_admin() ? 'All Bookings' : 'My Bookings'; ?>
        </h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php while ($booking = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card booking-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($booking['court_name']); ?></h5>
                            
                            <p class="card-text">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                            </p>
                            
                            <p class="card-text">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                            </p>

                            <?php if (is_admin()): ?>
                                <p class="card-text">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($booking['user_name']); ?>
                                </p>
                            <?php endif; ?>

                            <p class="card-text">
                                <i class="fas fa-money-bill"></i> 
                                $<?php echo number_format($booking['total_price'], 2); ?>
                            </p>

                            <p class="card-text">
                                <span class="status-<?php echo strtolower($booking['status']); ?>">
                                    <i class="fas fa-circle"></i> 
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </p>

                            <?php if ($booking['status'] === 'pending'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-warning btn-sm" 
                                            onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (is_admin()): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to delete this booking?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if ($result->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No bookings found.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="home.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <?php if (!is_admin()): ?>
                <a href="booking.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Make New Booking
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
