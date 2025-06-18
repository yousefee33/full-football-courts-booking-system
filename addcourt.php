<?php
require_once 'config.php';

// Check if user is admin
require_admin();

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
    <title>Add Court - El 7arefaa</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/addcourt.css">
    <style>
        .add-court-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .image-preview {
            max-width: 200px;
            margin-top: 1rem;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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
            <a href="dashboard.php" class="btn btn-outline">Dashboard</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </header>

    <main class="page-container">
        <div class="add-court-container">
            <h1>Add New Court</h1>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form action="process_addcourt.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Court Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="price_per_hour">Price per Hour (in $) *</label>
                    <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity (number of players) *</label>
                    <input type="number" id="capacity" name="capacity" min="1" required>
                </div>

                <div class="form-group">
                    <label for="image">Court Image</label>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    <div id="imagePreview" class="image-preview"></div>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Add Court</button>
                    <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Information</h3>
                <p>2025 El 7arefaa. Book your football pitch online anytime, anywhere.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 El 7arefaa. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
