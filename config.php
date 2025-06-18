<?php
session_start();
$servername = "localhost";
$name = "root"; // Default XAMPP name
$password = ""; // Default XAMPP password
$dbname = "el7arefaa";

// Create connection
$conn = new mysqli($servername, $name, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session


// Set character set to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Function to check admin access and redirect if not admin
function require_admin() {
    if (!is_logged_in()) {
        redirect('login.php', 'Please login to access this page', 'error');
    }
    if (!is_admin()) {
        redirect('home.php', 'Access denied. Admin privileges required.', 'error');
    }
}

// Function to redirect with message
function redirect($page, $message = '', $type = 'error') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $page");
    exit();
}
?> 