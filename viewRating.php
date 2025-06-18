<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', 'Please login to view ratings', 'error');
}

// Handle rating actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $booking_id = sanitize_input($_POST['booking_id']);
                $rating = sanitize_input($_POST['rating']);
                $comment = sanitize_input($_POST['comment']);
                
                // Check if user has already rated this booking
                $check_sql = "SELECT id FROM ratings WHERE booking_id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
                $check_stmt->execute();
                $existing_rating = $check_stmt->get_result()->fetch_assoc();
                
                if ($existing_rating) {
                    redirect('viewRating.php', 'You have already rated this booking', 'error');
                } else {
                    $sql = "INSERT INTO ratings (booking_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iiis", $booking_id, $_SESSION['user_id'], $rating, $comment);
                    if ($stmt->execute()) {
                        redirect('viewRating.php', 'Rating added successfully', 'success');
                    }
                }
                break;

            case 'delete':
                if (is_admin()) {
                    $rating_id = sanitize_input($_POST['rating_id']);
                    $sql = "DELETE FROM ratings WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $rating_id);
                    if ($stmt->execute()) {
                        redirect('viewRating.php', 'Rating deleted successfully', 'success');
                    }
                }
                break;
        }
    }
}

// Get unrated bookings for current user
$unrated_sql = "SELECT b.id, c.name as court_name, b.booking_date, b.start_time 
                FROM bookings b 
                JOIN courts c ON b.court_id = c.id 
                LEFT JOIN ratings r ON b.id = r.booking_id 
                WHERE b.user_id = ? 
                AND b.status = 'confirmed' 
                AND r.id IS NULL 
                AND b.booking_date < CURRENT_DATE
                ORDER BY b.booking_date DESC";
$unrated_stmt = $conn->prepare($unrated_sql);
$unrated_stmt->bind_param("i", $_SESSION['user_id']);
$unrated_stmt->execute();
$unrated_result = $unrated_stmt->get_result();

// Get all ratings with related information
$ratings_sql = "SELECT r.*, 
                u.name as user_name,
                c.name as court_name,
                b.booking_date,
                b.start_time
                FROM ratings r
                JOIN bookings b ON r.booking_id = b.id
                JOIN users u ON r.user_id = u.id
                JOIN courts c ON b.court_id = c.id
                ORDER BY r.created_at DESC";
$ratings_result = $conn->query($ratings_sql);

// Get average ratings per court
$avg_ratings_sql = "SELECT 
                    c.id,
                    c.name as court_name,
                    COUNT(r.id) as total_ratings,
                    AVG(r.rating) as avg_rating
                    FROM courts c
                    LEFT JOIN bookings b ON c.id = b.court_id
                    LEFT JOIN ratings r ON b.id = r.booking_id
                    GROUP BY c.id
                    ORDER BY avg_rating DESC";
$avg_ratings_result = $conn->query($avg_ratings_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court Ratings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rating-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .rating-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.2em;
        }
        .rating-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .court-stats {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Court Ratings</h1>

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

        <!-- Court Statistics -->
        <div class="court-stats">
            <h3 class="mb-4">Court Statistics</h3>
            <div class="row">
                <?php while ($court_stats = $avg_ratings_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($court_stats['court_name']); ?></h5>
                                <div class="rating-stars mb-2">
                                    <?php
                                    $avg = round($court_stats['avg_rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $avg ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                                <p class="card-text">
                                    Average: <?php echo $court_stats['avg_rating'] ? number_format($court_stats['avg_rating'], 1) : 'No ratings'; ?>
                                    <br>
                                    Total Reviews: <?php echo $court_stats['total_ratings']; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Unrated Bookings Section -->
        <?php if ($unrated_result->num_rows > 0): ?>
            <div class="mb-5">
                <h3>Your Unrated Bookings</h3>
                <div class="row">
                    <?php while ($booking = $unrated_result->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card rating-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($booking['court_name']); ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('F d, Y', strtotime($booking['booking_date'])); ?>
                                        <br>
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?>
                                    </p>
                                    <button type="button" 
                                            class="btn btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#addRatingModal<?php echo $booking['id']; ?>">
                                        <i class="fas fa-star"></i> Rate Now
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Add Rating Modal -->
                        <div class="modal fade" id="addRatingModal<?php echo $booking['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Rate Your Experience</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <select class="form-select" name="rating" required>
                                                    <option value="">Select rating</option>
                                                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                                    <option value="4">⭐⭐⭐⭐ Very Good</option>
                                                    <option value="3">⭐⭐⭐ Good</option>
                                                    <option value="2">⭐⭐ Fair</option>
                                                    <option value="1">⭐ Poor</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Comment</label>
                                                <textarea class="form-control" name="comment" rows="3" required 
                                                          placeholder="Share your experience..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Submit Rating</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- All Ratings Section -->
        <h3 class="mb-4">All Ratings</h3>
        <div class="row">
            <?php while ($rating = $ratings_result->fetch_assoc()): ?>
                <div class="col-md-6">
                    <div class="card rating-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title"><?php echo htmlspecialchars($rating['court_name']); ?></h5>
                                    <p class="rating-date">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('F d, Y', strtotime($rating['booking_date'])); ?>
                                    </p>
                                </div>
                                <div class="rating-stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars($rating['comment'])); ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    By <?php echo htmlspecialchars($rating['user_name']); ?>
                                </small>
                                
                                <?php if (is_admin()): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="rating_id" value="<?php echo $rating['id']; ?>">
                                        <button type="submit" 
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this rating?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if ($ratings_result->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No ratings available yet.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="home.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
