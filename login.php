<?php
require_once 'config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Check for messages
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - El 7arefaa</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">⚽ El 7arefaa</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-outline active">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        </div>
    </header>

    <main class="page-container">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>Welcome Back</h1>
                    <p>Login to your account to manage your bookings</p>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form action="process_login.php" method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary">Login</button>

                    <div class="login-footer">
                        <p>Don't have an account? <a href="register.php">Register now</a></p>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>2025 El 7arefaa. Book your football pitch online anytime, anywhere. Easy scheduling, affordable prices, and guaranteed fun</p>
                <div class="social-icons">
                    <a href="https://wa.me/966567780260" target="_blank" title="Chat on WhatsApp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
                    </a>
                    <a href="https://www.instagram.com/p.sweepy12" target="_blank" title="Follow us on Instagram">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram">
                    </a>
                    <a href="https://www.facebook.com/Pest%20sweepy" target="_blank" title="Follow us on Facebook">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                    </a>
                    <a href="https://twitter.com/pestsweepy" target="_blank" title="Follow us on Twitter">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6f/Logo_of_Twitter.svg" alt="Twitter">
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h3>Links</h3>
                <ul>                                                                            
                    <li><a href="home.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>If You Want To Get An Appointment</h3>
                <div class="contact-info">
                    <i class="phone-icon">📞</i>
                    <p>01204551879</p>
                </div>
                <div class="social-icons">
                    <a href="https://wa.me/966567780260" target="_blank" title="Chat on WhatsApp">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
                    </a>
                    <a href="https://www.instagram.com/p.sweepy12" target="_blank" title="Follow us on Instagram">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg" alt="Instagram">
                    </a>
                    <a href="https://www.facebook.com/Pest%20sweepy" target="_blank" title="Follow us on Facebook">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
                    </a>
                    <a href="https://twitter.com/pestsweepy" target="_blank" title="Follow us on Twitter">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6f/Logo_of_Twitter.svg" alt="Twitter">
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Court. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/utilities.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        }
    </script>
</body>
</html> 