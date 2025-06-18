<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to make a booking', 'error');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $court_id = sanitize_input($_POST['court_id']);
    $booking_date = sanitize_input($_POST['booking_date']);
    $start_time = sanitize_input($_POST['start_time']);
    $end_time = sanitize_input($_POST['end_time']);
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (empty($court_id) || empty($booking_date) || empty($start_time) || empty($end_time)) {
        redirect('booking.php', 'Please fill in all fields', 'error');
    }

    // Validate date (must be future date)
    $booking_timestamp = strtotime($booking_date);
    if ($booking_timestamp < strtotime('today')) {
        redirect('booking.php', 'Booking date must be a future date', 'error');
    }

    // Validate time (end time must be after start time)
    $start_timestamp = strtotime($booking_date . ' ' . $start_time);
    $end_timestamp = strtotime($booking_date . ' ' . $end_time);
    if ($end_timestamp <= $start_timestamp) {
        redirect('booking.php', 'End time must be after start time', 'error');
    }

    // Check if court exists and get price
    $stmt = $conn->prepare("SELECT price_per_hour, status FROM courts WHERE id = ?");
    $stmt->bind_param("i", $court_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        redirect('booking.php', 'Invalid court selected', 'error');
    }

    $court = $result->fetch_assoc();
    if ($court['status'] !== 'available') {
        redirect('booking.php', 'Selected court is not available', 'error');
    }

    // Check for booking conflicts
    $stmt = $conn->prepare("
        SELECT id FROM bookings 
        WHERE court_id = ? 
        AND booking_date = ? 
        AND status != 'cancelled'
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ");
    $stmt->bind_param("isssssss", 
        $court_id, 
        $booking_date, 
        $end_time, 
        $start_time, 
        $end_time, 
        $start_time, 
        $start_time, 
        $end_time
    );
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        redirect('booking.php', 'Selected time slot is already booked', 'error');
    }

    // Calculate total price
    $hours = ($end_timestamp - $start_timestamp) / 3600;
    $total_price = $court['price_per_hour'] * $hours;

    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, total_price, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iisssd", 
        $user_id, 
        $court_id, 
        $booking_date, 
        $start_time, 
        $end_time, 
        $total_price
    );

    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        redirect('payment.php?booking_id=' . $booking_id, 'Booking created successfully. Please proceed with payment', 'success');
    } else {
        redirect('booking.php', 'Booking failed. Please try again', 'error');
    }

    $stmt->close();
} else {
    redirect('booking.php');
}
?> 