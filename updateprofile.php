<?php
require_once '../config.php';

if (!is_logged_in()) {
    redirect('login.php', 'Please login to access profile', 'error');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);

    if (empty($name) || empty($email)) {
        redirect('profile.php', 'Name and email are required', 'error');
    }

    $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);

    if ($stmt->execute()) {
        $_SESSION['name'] = $name;
        $_SESSION['user_email'] = $email;
        redirect('profile.php', 'Profile updated successfully', 'success');
    } else {
        redirect('profile.php', 'Failed to update profile', 'error');
    }
}

redirect('profile.php');
?>