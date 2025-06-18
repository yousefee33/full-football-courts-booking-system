<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to continue.', 'error');
}

// Check if booking ID is provided
if (!isset($_POST['booking_id'])) {
    redirect('mybooking.php', 'Invalid booking ID.', 'error');
}

$booking_id = sanitize_input($_POST['booking_id']);

// Get booking details to check ownership and status
$stmt = $conn->prepare("
    SELECT b.*, c.name as court_name 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

// Check if booking exists
if (!$booking) {
    redirect('mybooking.php', 'Booking not found.', 'error');
}

// Check if user is authorized to delete this booking
// (either the booking owner or an admin)
if (!is_admin() && $booking['user_id'] != $_SESSION['user_id']) {
    redirect('mybooking.php', 'You are not authorized to delete this booking.', 'error');
}

// Check if booking can be deleted (not too close to booking time)
$booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
$current_time = time();
$hours_until_booking = ($booking_datetime - $current_time) / 3600;

// Allow deletion if:
// 1. User is admin
// 2. Booking is more than 24 hours away
// 3. Booking is in pending status
if (!is_admin() && $hours_until_booking < 24 && $booking['status'] !== 'pending') {
    redirect('mybooking.php', 'Bookings can only be cancelled at least 24 hours before the scheduled time.', 'error');
}

// Delete the booking
$stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    // If deletion was successful, redirect with success message
    $message = "Booking for " . htmlspecialchars($booking['court_name']) . " has been cancelled successfully.";
    $message_type = "success";
} else {
    // If deletion failed, redirect with error message
    $message = "Failed to cancel booking. Please try again.";
    $message_type = "error";
}

// Redirect back to appropriate page
if (is_admin()) {
    redirect('manage_bookings.php', $message, $message_type);
} else {
    redirect('mybooking.php', $message, $message_type);
}
?> 