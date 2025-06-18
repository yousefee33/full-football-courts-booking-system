<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('login.php', 'Access denied. Admin privileges required.', 'error');
}

$message = '';
$message_type = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = sanitize_input($_POST['user_id']);
        $action = sanitize_input($_POST['action']);
        
        switch ($action) {
            case 'update':
                $name = sanitize_input($_POST['name']);
                $email = sanitize_input($_POST['email']);
                $phone = sanitize_input($_POST['phone']);
                $role = sanitize_input($_POST['role']);
                $status = sanitize_input($_POST['status']);
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Invalid email format.";
                    $message_type = "error";
                    break;
                }
                
                // Check if email already exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $message = "Email already exists.";
                    $message_type = "error";
                    break;
                }
                
                // Update user
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $user_id);
                
                if ($stmt->execute()) {
                    $message = "User updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error updating user.";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                // Don't allow deleting the last admin
                $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $admin_count = $stmt->get_result()->fetch_assoc()['admin_count'];
                
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user_role = $stmt->get_result()->fetch_assoc()['role'];
                
                if ($user_role === 'admin' && $admin_count <= 1) {
                    $message = "Cannot delete the last admin user.";
                    $message_type = "error";
                    break;
                }
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $message = "User deleted successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error deleting user.";
                    $message_type = "error";
                }
                break;
                
            case 'add':
                $name = sanitize_input($_POST['name']);
                $email = sanitize_input($_POST['email']);
                $phone = sanitize_input($_POST['phone']);
                $password = $_POST['password'];
                $role = sanitize_input($_POST['role']);
                
                // Validate inputs
                if (empty($name) || empty($email) || empty($phone) || empty($password)) {
                    $message = "All fields are required.";
                    $message_type = "error";
                    break;
                }
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Invalid email format.";
                    $message_type = "error";
                    break;
                }
                
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $message = "Email already exists.";
                    $message_type = "error";
                    break;
                }
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);
                
                if ($stmt->execute()) {
                    $message = "User added successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error adding user.";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get filter parameters
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$search_query = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build the query
$query = "
    SELECT 
        u.*,
        COUNT(b.id) as total_bookings,
        MAX(b.created_at) as last_booking
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE 1=1
";
$params = [];
$types = "";

if ($role_filter) {
    $query .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if ($status_filter) {
    $query .= " AND u.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search_query) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .filters { margin-bottom: 20px; display: flex; gap: 10px; }
        .filters select, .filters input { padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
        .users-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .users-table th, .users-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .users-table th { background-color: #f5f5f5; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.9em; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-banned { background-color: #fff3cd; color: #856404; }
        .action-buttons { display: flex; gap: 5px; }
        .btn { padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-warning { background-color: #ffc107; color: black; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 10% auto; padding: 20px; width: 50%; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <header>
        <div class="logo">⚽ Football Admin Panel</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search.php">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="mybooking.php">My Bookings</a>
            <a href="viewRating.php">Reviews</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="Dashboard.php" class="active">Admin</a>
        </nav>
        <div class="auth-buttons">
            <a href="profile.php" class="btn btn-outline">Profile</a>
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </header>

    <main class="container">
        <div class="page-header">
            <h1>Manage Users</h1>
            <button class="btn btn-primary" onclick="showAddUserModal()">Add New User</button>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search users...">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                </select>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="banned" <?= $status_filter === 'banned' ? 'selected' : '' ?>>Banned</option>
                </select>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Total Bookings</th>
                    <th>Last Booking</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $user['status'] ?>">
                                <?= ucfirst(htmlspecialchars($user['status'])) ?>
                            </span>
                        </td>
                        <td><?= $user['total_bookings'] ?></td>
                        <td><?= $user['last_booking'] ? date('Y-m-d', strtotime($user['last_booking'])) : 'Never' ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-sm" onclick="showEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)">Edit</button>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Add User</button>
                <button type="button" class="btn btn-danger" onclick="hideAddUserModal()">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <h2>Edit User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" id="edit_phone" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="banned">Banned</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Update User</button>
                <button type="button" class="btn btn-danger" onclick="hideEditUserModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function showAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
        }

        function hideAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        function showEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_status').value = user.status;
            document.getElementById('editUserModal').style.display = 'block';
        }

        function hideEditUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
