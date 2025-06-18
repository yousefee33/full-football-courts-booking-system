<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
    $comment = filter_var($_POST['comment'], FILTER_SANITIZE_STRING);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5 stars.";
    } else {
        // Check if booking exists and belongs to user
        $stmt = $conn->prepare("
            SELECT b.* 
            FROM bookings b
            WHERE b.id = ? AND b.user_id = ? AND b.status = 'completed'
        ");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();

        if ($booking) {
            // Check if already rated
            $stmt = $conn->prepare("
                SELECT id FROM ratings 
                WHERE booking_id = ? AND user_id = ?
            ");
            $stmt->bind_param("ii", $booking_id, $user_id);
            $stmt->execute();
            $existing_rating = $stmt->get_result()->fetch_assoc();

            if ($existing_rating) {
                // Update existing rating
                $stmt = $conn->prepare("
                    UPDATE ratings 
                    SET rating = ?, 
                        comment = ?,
                        updated_at = NOW()
                    WHERE booking_id = ? AND user_id = ?
                ");
                $stmt->bind_param("isii", $rating, $comment, $booking_id, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Rating updated successfully!";
                } else {
                    $error_message = "Error updating rating. Please try again.";
                }
            } else {
                // Insert new rating
                $stmt = $conn->prepare("
                    INSERT INTO ratings (booking_id, user_id, rating, comment)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("iiis", $booking_id, $user_id, $rating, $comment);
                
                if ($stmt->execute()) {
                    $success_message = "Thank you for your rating!";
                } else {
                    $error_message = "Error submitting rating. Please try again.";
                }
            }
        } else {
            $error_message = "Invalid booking or booking not completed yet.";
        }
    }
}

// Fetch completed bookings that can be rated
$stmt = $conn->prepare("
    SELECT 
        b.*,
        c.name AS court_name,
        r.rating AS existing_rating,
        r.comment AS existing_comment
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    LEFT JOIN ratings r ON b.id = r.booking_id AND r.user_id = ?
    WHERE b.user_id = ? AND b.status = 'completed'
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate average ratings for each court
$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        COUNT(r.id) as total_ratings,
        ROUND(AVG(r.rating), 1) as average_rating
    FROM courts c
    LEFT JOIN bookings b ON c.id = b.court_id
    LEFT JOIN ratings r ON b.id = r.booking_id
    GROUP BY c.id
    ORDER BY average_rating DESC
");
$stmt->execute();
$court_ratings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Experience - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .rating-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .court-ratings {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .court-rating-card {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
        }
        .court-rating-card h3 {
            margin: 0 0 1rem 0;
        }
        .star-rating {
            color: #ffc107;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .total-ratings {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .booking-list {
            margin-top: 2rem;
        }
        .booking-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .booking-details {
            margin-bottom: 1rem;
        }
        .rating-form {
            margin-top: 1rem;
        }
        .star-input {
            display: none;
        }
        .star-label {
            color: #dee2e6;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-label:hover,
        .star-label:hover ~ .star-label,
        .star-input:checked ~ .star-label {
            color: #ffc107;
        }
        .comment-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 1rem 0;
            resize: vertical;
        }
        .submit-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-btn:hover {
            background: #218838;
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
    <nav class="navbar">
        <div class="logo">⚽ El 7arefaa Court</div>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
        </div>
    </nav>

    <div class="rating-container">
        <h1>Rate Your Experience</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <section>
            <h2>Court Ratings</h2>
            <div class="court-ratings">
                <?php foreach ($court_ratings as $court): ?>
                    <div class="court-rating-card">
                        <h3><?php echo htmlspecialchars($court['name']); ?></h3>
                        <div class="star-rating">
                            <?php
                            $rating = $court['average_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="total-ratings">
                            <?php echo $court['total_ratings']; ?> ratings
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="booking-list">
            <h2>Your Completed Bookings</h2>
            <?php if (empty($bookings)): ?>
                <p>No completed bookings found.</p>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <div class="booking-details">
                            <h3><?php echo htmlspecialchars($booking['court_name']); ?></h3>
                            <p>Date: <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></p>
                            <p>Time: <?php echo date('g:i A', strtotime($booking['start_time'])) . 
                                              ' - ' . date('g:i A', strtotime($booking['end_time'])); ?></p>
                        </div>

                        <form method="POST" class="rating-form">
                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" 
                                           id="star<?php echo $booking['id'] . '_' . $i; ?>" 
                                           class="star-input"
                                           <?php echo ($booking['existing_rating'] == $i) ? 'checked' : ''; ?>>
                                    <label for="star<?php echo $booking['id'] . '_' . $i; ?>" 
                                           class="star-label">★</label>
                                <?php endfor; ?>
                            </div>
                            <textarea name="comment" class="comment-input" 
                                      placeholder="Share your experience (optional)"><?php 
                                echo htmlspecialchars($booking['existing_comment'] ?? ''); 
                            ?></textarea>
                            <button type="submit" name="submit_rating" class="submit-btn">
                                <?php echo $booking['existing_rating'] ? 'Update Rating' : 'Submit Rating'; ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // Reverse star rating hover effect (since stars are in reverse order in HTML)
        document.querySelectorAll('.star-rating').forEach(container => {
            const labels = container.querySelectorAll('.star-label');
            labels.forEach((label, index) => {
                label.addEventListener('mouseover', () => {
                    for (let i = 0; i <= index; i++) {
                        labels[i].style.color = '#ffc107';
                    }
                });
                label.addEventListener('mouseout', () => {
                    labels.forEach(l => {
                        l.style.removeProperty('color');
                    });
                });
            });
        });
    </script>
</body>
</html>
