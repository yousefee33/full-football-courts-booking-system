<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="../css/contact.css">
</head>
<body>
    <header>
        <div class="logo">⚽ Football Court</div>
        <nav>
            <a href="home.php>Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="mybooking.php">My Bookings</a>
            <a href="viewRating.php">Reviews</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
        </nav>
        <div class="auth-buttons">
            <a href="profile.php" class="btn btn-outline">Profile</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </header>

    <main class="contact-page">
        <div class="contact-info">
            <h2>Get in Touch</h2>
            <div class="info-cards">
                <div class="info-card">
                    <img src="../images/icon-location.svg" alt="Location">
                    <h3>Visit Us</h3>
                    <p>123 Sports Avenue<br>Dubai, UAE</p>
                </div>
                <div class="info-card">
                    <img src="https://raw.githubusercontent.com/primer/octicons/main/icons/device-mobile-16.svg" alt="Phone">
                    <h3>Call Us</h3>
                    <p>+971 50 123 4567<br>+971 4 123 4567</p>
                </div>
                <div class="info-card">
                    <img src="https://raw.githubusercontent.com/primer/octicons/main/icons/mail-16.svg" alt="Email">
                    <h3>Email Us</h3>
                    <p>info@footballcourt.com<br>support@footballcourt.com</p>
                </div>
            </div>
            <div class="map">
                <img src="https://images.unsplash.com/photo-1569336415962-a4bd9f69cd83?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwxfDB8MXxyYW5kb218MHx8bWFwfHx8fHx8MTY4NDI0NjQ5OA&ixlib=rb-4.0.3&q=80" alt="Location Map">
            </div>
        </div>

        <form class="contact-form">
            <h3>Send us a Message</h3>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>2025 Football Court. Book your football pitch online anytime, anywhere. Easy scheduling, affordable prices, and guaranteed fun</p>
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
</body>
</html>
