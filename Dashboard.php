<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit();
}

// Get statistics
$stats = [];

// Total courts
$result = $conn->query("SELECT COUNT(*) as count FROM courts");
$stats['total_courts'] = $result->fetch_assoc()['count'];

// Total active bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed' AND end_time > NOW()");
$stats['active_bookings'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'confirmed'");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Get recent bookings
$stmt = $conn->prepare("
    SELECT b.*, u.name, c.name as court_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN courts c ON b.court_id = c.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - El 7arefaa</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0.5rem 0;
        }
        .recent-bookings {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .booking-table th,
        .booking-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .booking-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .btn-edit {
            background: #ffeaa7;
            color: #d68910;
        }
        .btn-delete {
            background: #ffcdd2;
            color: #c62828;
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
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_courts.php">
                        <i class="fas fa-futbol"></i> Manage Courts
                    </a>
                </li>
                <li>
                    <a href="managebookings.php">
                        <i class="fas fa-calendar-alt"></i> Manage Bookings
                    </a>
                </li>
                <li>
                    <a href="manageuser.php">
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
            <h1>Dashboard Overview</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Courts</h3>
                    <div class="value"><?php echo $stats['total_courts']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Bookings</h3>
                    <div class="value"><?php echo $stats['active_bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="value"><?php echo $stats['total_users']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                </div>
            </div>

            <div class="recent-bookings">
                <h2>Recent Bookings</h2>
                <table class="booking-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Court</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['court_name']); ?></td>
                            <td>
                                <?php 
                                echo date('M j, Y g:i A', strtotime($booking['start_time'])) . ' - ' . 
                                     date('g:i A', strtotime($booking['end_time']));
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td class="action-buttons">
                                <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_booking.php?id=<?php echo $booking['id']; ?>" 
                                   class="btn-action btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this booking?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Add any dashboard-specific JavaScript here
    </script>
</body>
</html>
