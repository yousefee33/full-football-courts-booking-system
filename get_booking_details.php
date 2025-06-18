<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Booking ID is required']);
    exit();
}

$booking_id = sanitize_input($_GET['id']);

// Fetch booking details
$query = "
    SELECT 
        b.*,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        c.name as court_name,
        c.type as court_type,
        c.location as court_location,
        c.price_per_hour
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['error' => 'Booking not found']);
    exit();
}

// Return booking details as JSON
header('Content-Type: application/json');
echo json_encode($booking);
?> 