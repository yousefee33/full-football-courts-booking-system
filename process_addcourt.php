<?php
require_once 'config.php';

// Check if user is admin
require_admin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price_per_hour = sanitize_input($_POST['price_per_hour']);
    $capacity = sanitize_input($_POST['capacity']);
    $status = sanitize_input($_POST['status']);

    // Validate input
    if (empty($name) || empty($description) || empty($price_per_hour) || empty($capacity)) {
        redirect('addcourt.php', 'Please fill in all required fields', 'error');
    }

    // Validate numeric fields
    if (!is_numeric($price_per_hour) || $price_per_hour < 0) {
        redirect('addcourt.php', 'Invalid price value', 'error');
    }

    if (!is_numeric($capacity) || $capacity < 1) {
        redirect('addcourt.php', 'Invalid capacity value', 'error');
    }

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        // Verify file extension
        if (!in_array(strtolower($filetype), $allowed)) {
            redirect('addcourt.php', 'Invalid image format. Allowed formats: jpg, jpeg, png, gif', 'error');
        }

        // Create unique filename
        $new_filename = uniqid() . '.' . $filetype;
        $upload_path = '../uploads/courts/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $new_filename)) {
            $image_url = 'uploads/courts/' . $new_filename;
        } else {
            redirect('addcourt.php', 'Failed to upload image', 'error');
        }
    }

    // Insert court into database
    $stmt = $conn->prepare("
        INSERT INTO courts (name, description, price_per_hour, capacity, image_url, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssddss", $name, $description, $price_per_hour, $capacity, $image_url, $status);

    if ($stmt->execute()) {
        redirect('dashboard.php', 'Court added successfully', 'success');
    } else {
        redirect('addcourt.php', 'Failed to add court. Please try again', 'error');
    }

    $stmt->close();
} else {
    redirect('addcourt.php');
}
?> 