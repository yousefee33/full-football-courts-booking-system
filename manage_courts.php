<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit();
}

// Handle court deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $court_id = (int)$_GET['delete'];
    
    // First check if there are any future bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE court_id = ? 
        AND status = 'confirmed' 
        AND end_time > NOW()
    ");
    $stmt->bind_param("i", $court_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = "Cannot delete court: There are future bookings for this court.";
    } else {
        // Delete the court
        $stmt = $conn->prepare("DELETE FROM courts WHERE id = ?");
        $stmt->bind_param("i", $court_id);
        if ($stmt->execute()) {
            $success = "Court deleted successfully.";
        } else {
            $error = "Error deleting court.";
        }
    }
}

// Get all courts
$stmt = $conn->prepare("
    SELECT c.*, 
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'confirmed' AND b.end_time > NOW() THEN 1 ELSE 0 END) as active_bookings
    FROM courts c
    LEFT JOIN bookings b ON c.id = b.court_id
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute();
$courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courts - El 7arefaa</title>
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .btn-add {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .courts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .court-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .court-image {
            height: 200px;
            overflow: hidden;
        }
        .court-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .court-info {
            padding: 1.5rem;
        }
        .court-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
        }
        .court-details {
            margin-bottom: 1rem;
        }
        .court-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .court-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
        }
        .btn-edit {
            background: #ffeaa7;
            color: #d68910;
        }
        .btn-delete {
            background: #ffcdd2;
            color: #c62828;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
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
                    <a href="manage_bookings.php">
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
                <h1>Manage Courts</h1>
                <a href="add_court.php" class="btn-add">
                    <i class="fas fa-plus"></i> Add New Court
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="courts-grid">
                <?php foreach ($courts as $court): ?>
                <div class="court-card">
                    <div class="court-image">
                        <img src="<?php echo htmlspecialchars($court['image_url'] ?? '../images/default-court.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($court['name']); ?>">
                    </div>
                    <div class="court-info">
                        <h3 class="court-name"><?php echo htmlspecialchars($court['name']); ?></h3>
                        <div class="court-details">
                            <div class="court-detail">
                                <span>Status:</span>
                                <span class="status-badge status-<?php echo strtolower($court['status']); ?>">
                                    <?php echo ucfirst($court['status']); ?>
                                </span>
                            </div>
                            <div class="court-detail">
                                <span>Price per Hour:</span>
                                <span>$<?php echo number_format($court['price_per_hour'], 2); ?></span>
                            </div>
                            <div class="court-detail">
                                <span>Capacity:</span>
                                <span><?php echo $court['capacity']; ?> players</span>
                            </div>
                            <div class="court-detail">
                                <span>Total Bookings:</span>
                                <span><?php echo $court['total_bookings']; ?></span>
                            </div>
                            <div class="court-detail">
                                <span>Active Bookings:</span>
                                <span><?php echo $court['active_bookings']; ?></span>
                            </div>
                        </div>
                        <div class="court-actions">
                            <a href="edit_court.php?id=<?php echo $court['id']; ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($court['active_bookings'] == 0): ?>
                            <a href="?delete=<?php echo $court['id']; ?>" 
                               class="btn-action btn-delete"
                               onclick="return confirm('Are you sure you want to delete this court?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html> 