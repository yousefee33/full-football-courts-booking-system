<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
        redirect('register.php', 'Please fill in all fields', 'error');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect('register.php', 'Please enter a valid email address', 'error');
    }

    // Validate password match
    if ($password !== $confirmPassword) {
        redirect('register.php', 'Passwords do not match', 'error');
    }

    // Validate password strength
    if (strlen($password) < 8) {
        redirect('register.php', 'Password must be at least 8 characters long', 'error');
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        redirect('register.php', 'Email already exists', 'error');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);

    if ($stmt->execute()) {
        // Get the new user's ID
        $userId = $stmt->insert_id;

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'user';

        redirect('home.php', 'Registration successful! Welcome to El 7arefaa', 'success');
    } else {
        redirect('register.php', 'Registration failed. Please try again', 'error');
    }

    $stmt->close();
} else {
    redirect('register.php');
}
?> 