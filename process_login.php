<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Validate input
    if (empty($email) || empty($password)) {
        redirect('login.php', 'Please fill in all fields', 'error');
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), "/"); // 30 days
                
                // Store token in database (you would need to add a remember_token column to users table)
                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->bind_param("si", $token, $user['id']);
                $stmt->execute();
            }

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('dashboard.php', 'Welcome back, Admin!', 'success');
            } else {
                redirect('home.php', 'Welcome back!', 'success');
            }
        } else {
            redirect('login.php', 'Invalid email or password', 'error');
        }
    } else {
        redirect('login.php', 'Invalid email or password', 'error');
    }

    $stmt->close();
} else {
    redirect('login.php');
}
?> 