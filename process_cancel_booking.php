<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to cancel a booking', 'error');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize booking ID
    $booking_id = sanitize_input($_POST['booking_id']);
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($booking_id)) {
        redirect('mybookings.php', 'Invalid booking selected', 'error');
    }

    // Check if booking exists and belongs to the user
    $stmt = $conn->prepare("
        SELECT status, booking_date, start_time 
        FROM bookings 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
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

    // Update booking status to cancelled
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = 'cancelled' 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);

    if ($stmt->execute()) {
        redirect('mybookings.php', 'Booking cancelled successfully', 'success');
    } else {
        redirect('mybookings.php', 'Failed to cancel booking. Please try again', 'error');
    }

    $stmt->close();
} else {
    redirect('mybookings.php');
}
?> 