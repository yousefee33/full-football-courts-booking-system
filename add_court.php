<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price_per_hour = (float)$_POST['price_per_hour'];
    $capacity = (int)$_POST['capacity'];
    $status = sanitize_input($_POST['status']);

    if (empty($name)) {
        $errors[] = "Court name is required.";
    }

    if ($price_per_hour <= 0) {
        $errors[] = "Price must be greater than 0.";
    }

    if ($capacity <= 0) {
        $errors[] = "Capacity must be greater than 0.";
    }

    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
        } else {
            $file_name = uniqid() . '_' . $_FILES['image']['name'];
            $upload_path = '../images/courts/' . $file_name;
            
            // Create directory if it doesn't exist
            if (!file_exists('../images/courts')) {
                mkdir('../images/courts', 0777, true);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = 'images/courts/' . $file_name;
            } else {
                $errors[] = "Error uploading image.";
            }
        }
    }

    // Insert court if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO courts (
                name, description, price_per_hour, 
                capacity, status, image_url, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("ssdisd", 
            $name,
            $description,
            $price_per_hour,
            $capacity,
            $status,
            $image_url
        );

        if ($stmt->execute()) {
            $success = "Court added successfully!";
            // Clear form data
            $_POST = [];
        } else {
            $errors[] = "Error adding court. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Court - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 2rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .sidebar-menu a:hover {
            background: #34495e;
        }
        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f5f6fa;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
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
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        .image-preview {
            margin-top: 1rem;
            max-width: 300px;
        }
        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            display: none;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
        }
        .btn-submit:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>⚽ El 7arefaa</h2>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_courts.php" class="active">
                        <i class="fas fa-futbol"></i> Manage Courts
                    </a>
                </li>
                <li>
                    <a href="managebookings.php">
                        <i class="fas fa-calendar-alt"></i> Manage Bookings
                    </a>
                </li>
                <li>
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </li>
                <li>
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li>
                    <a href="home.php">
                        <i class="fas fa-arrow-left"></i> Back to Site
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1>Add New Court</h1>
            </div>

            <div class="form-container">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Court Name *</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price_per_hour">Price per Hour (USD) *</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" step="0.01" required
                               value="<?php echo isset($_POST['price_per_hour']) ? htmlspecialchars($_POST['price_per_hour']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (players) *</label>
                        <input type="number" id="capacity" name="capacity" required
                               value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Court Image</label>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview">
                            <img id="preview" src="#" alt="Image preview">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Court
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html> 