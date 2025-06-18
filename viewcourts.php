<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to view courts', 'error');
}

// Handle court actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize_input($_POST['name']);
                $description = sanitize_input($_POST['description']);
                $price = sanitize_input($_POST['price']);
                $capacity = sanitize_input($_POST['capacity']);
                $image_url = sanitize_input($_POST['image_url']);
                
                $sql = "INSERT INTO courts (name, description, price_per_hour, capacity, image_url) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdis", $name, $description, $price, $capacity, $image_url);
                if ($stmt->execute()) {
                    redirect('viewcourts.php', 'Court added successfully', 'success');
                }
                break;

            case 'edit':
                $court_id = sanitize_input($_POST['court_id']);
                $name = sanitize_input($_POST['name']);
                $description = sanitize_input($_POST['description']);
                $price = sanitize_input($_POST['price']);
                $capacity = sanitize_input($_POST['capacity']);
                $image_url = sanitize_input($_POST['image_url']);
                $status = sanitize_input($_POST['status']);
                
                $sql = "UPDATE courts SET name = ?, description = ?, price_per_hour = ?, 
                        capacity = ?, image_url = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdiisi", $name, $description, $price, $capacity, $image_url, $status, $court_id);
                if ($stmt->execute()) {
                    redirect('viewcourts.php', 'Court updated successfully', 'success');
                }
                break;

            case 'delete':
                if (is_admin()) {
                    $court_id = sanitize_input($_POST['court_id']);
                    
                    // First check if there are any active bookings
                    $sql = "SELECT COUNT(*) as booking_count FROM bookings 
                            WHERE court_id = ? AND status IN ('pending', 'confirmed')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $court_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['booking_count'] > 0) {
                        redirect('viewcourts.php', 'Cannot delete court with active bookings', 'error');
                    } else {
                        $sql = "DELETE FROM courts WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $court_id);
                        if ($stmt->execute()) {
                            redirect('viewcourts.php', 'Court deleted successfully', 'success');
                        }
                    }
                }
                break;
        }
    }
}

// Get all courts
$sql = "SELECT c.*,
    (SELECT COUNT(*) 
     FROM bookings b 
     WHERE b.court_id = c.id AND b.status IN ('pending', 'confirmed')) AS active_bookings,
    (SELECT AVG(r.rating) 
     FROM ratings r 
     WHERE r.court_id = c.id) AS avg_rating
FROM courts c
ORDER BY c.status ASC, c.name ASC";
$result = $conn->query($sql);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .court-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .court-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .court-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .status-available { color: #28a745; }
        .status-maintenance { color: #dc3545; }
        .rating-stars {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Football Courts</h1>
            <?php if (is_admin()): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourtModal">
                    <i class="fas fa-plus"></i> Add New Court
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php while ($court = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card court-card">
                        <img src="<?php echo htmlspecialchars($court['image_url']); ?>" 
                             class="card-img-top court-image" 
                             alt="<?php echo htmlspecialchars($court['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($court['name']); ?></h5>
                            
                            <p class="card-text"><?php echo htmlspecialchars($court['description']); ?></p>
                            
                            <p class="card-text">
                                <i class="fas fa-users"></i> Capacity: <?php echo $court['capacity']; ?> players
                            </p>
                            
                            <p class="card-text">
                                <i class="fas fa-money-bill"></i> 
                                $<?php echo number_format($court['price_per_hour'], 2); ?>/hour
                            </p>

                            <p class="card-text">
                                <span class="status-<?php echo strtolower($court['status']); ?>">
                                    <i class="fas fa-circle"></i> 
                                    <?php echo ucfirst($court['status']); ?>
                                </span>
                            </p>

                            <?php if ($court['avg_rating']): ?>
                                <p class="card-text">
                                    <span class="rating-stars">
                                        <?php
                                        $rating = round($court['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </span>
                                    (<?php echo number_format($court['avg_rating'], 1); ?>)
                                </p>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <?php if ($court['status'] === 'available'): ?>
                                    <a href="booking.php?court_id=<?php echo $court['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-calendar-plus"></i> Book Now
                                    </a>
                                <?php endif; ?>

                                <?php if (is_admin()): ?>
                                    <div class="btn-group">
                                        <button type="button" 
                                                class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCourtModal<?php echo $court['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($court['active_bookings'] == 0): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="court_id" value="<?php echo $court['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" 
                                                        class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this court?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (is_admin()): ?>
                    <!-- Edit Court Modal -->
                    <div class="modal fade" id="editCourtModal<?php echo $court['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Court</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="court_id" value="<?php echo $court['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Court Name</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?php echo htmlspecialchars($court['name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3" required><?php 
                                                echo htmlspecialchars($court['description']); 
                                            ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Price per Hour</label>
                                            <input type="number" class="form-control" name="price" 
                                                   value="<?php echo $court['price_per_hour']; ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Capacity</label>
                                            <input type="number" class="form-control" name="capacity" 
                                                   value="<?php echo $court['capacity']; ?>" 
                                                   min="1" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Image URL</label>
                                            <input type="url" class="form-control" name="image_url" 
                                                   value="<?php echo htmlspecialchars($court['image_url']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status" required>
                                                <option value="available" <?php echo $court['status'] === 'available' ? 'selected' : ''; ?>>
                                                    Available
                                                </option>
                                                <option value="maintenance" <?php echo $court['status'] === 'maintenance' ? 'selected' : ''; ?>>
                                                    Maintenance
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endwhile; ?>

            <?php if ($result->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No courts available.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (is_admin()): ?>
            <!-- Add Court Modal -->
            <div class="modal fade" id="addCourtModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Court</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="add">
                                
                                <div class="mb-3">
                                    <label class="form-label">Court Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Price per Hour</label>
                                    <input type="number" class="form-control" name="price" 
                                           step="0.01" min="0" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" class="form-control" name="capacity" 
                                           min="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Image URL</label>
                                    <input type="url" class="form-control" name="image_url" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Add Court</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="home.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 