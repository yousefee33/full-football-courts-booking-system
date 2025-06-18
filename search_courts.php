<?php
require_once 'config.php';

// Initialize variables
$search_term = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$courts = [];
$search_history = [];

// Get search history if user is logged in
if (is_logged_in()) {
    $stmt = $conn->prepare("
        SELECT sh.search_term, sh.created_at, c.name as court_name
        FROM search_history sh
        LEFT JOIN courts c ON sh.court_id = c.id
        WHERE sh.user_id = ?
        ORDER BY sh.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $search_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Perform search if search term is provided
if (!empty($search_term)) {
    $search_pattern = "%{$search_term}%";
    $stmt = $conn->prepare("
        SELECT * FROM courts 
        WHERE name LIKE ? OR description LIKE ?
        ORDER BY name ASC
    ");
    $stmt->bind_param("ss", $search_pattern, $search_pattern);
    $stmt->execute();
    $courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Store search in history if user is logged in
    if (is_logged_in()) {
        $stmt = $conn->prepare("
            INSERT INTO search_history (user_id, search_term) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $_SESSION['user_id'], $search_term);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Courts - El 7arefaa</title>
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .search-box input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .search-history {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .search-history h2 {
            margin-bottom: 1rem;
        }
        .search-history ul {
            list-style: none;
            padding: 0;
        }
        .search-history li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }
        .courts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
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
        .court-info h3 {
            margin: 0 0 1rem 0;
        }
        .court-price {
            color: #28a745;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .court-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }
        .no-results {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">⚽ El 7arefaa</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="search_courts.php" class="active">Find Courts</a>
            <a href="booking.php">Book Now</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="auth-buttons">
            <?php if (is_logged_in()): ?>
                <a href="profile.php" class="btn btn-outline">Profile</a>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="search-container">
        <h1>Search Football Courts</h1>
        
        <form action="" method="GET" class="search-box">
            <input type="text" name="search" placeholder="Search by court name or description..." 
                   value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if (is_logged_in() && !empty($search_history)): ?>
        <div class="search-history">
            <h2>Recent Searches</h2>
            <ul>
                <?php foreach ($search_history as $history): ?>
                <li>
                    <a href="?search=<?php echo urlencode($history['search_term']); ?>">
                        <?php echo htmlspecialchars($history['search_term']); ?>
                    </a>
                    <?php if (!empty($history['court_name'])): ?>
                        - Found: <?php echo htmlspecialchars($history['court_name']); ?>
                    <?php endif; ?>
                    <small>(<?php echo date('M j, Y', strtotime($history['created_at'])); ?>)</small>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($search_term)): ?>
            <?php if (!empty($courts)): ?>
                <div class="courts-grid">
                    <?php foreach ($courts as $court): ?>
                    <div class="court-card">
                        <?php if (!empty($court['image_url'])): ?>
                        <div class="court-image">
                            <img src="../<?php echo htmlspecialchars($court['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($court['name']); ?>">
                        </div>
                        <?php endif; ?>
                        <div class="court-info">
                            <h3><?php echo htmlspecialchars($court['name']); ?></h3>
                            <div class="court-price">
                                $<?php echo number_format($court['price_per_hour'], 2); ?> per hour
                            </div>
                            <div class="court-status status-<?php echo $court['status']; ?>">
                                <?php echo ucfirst($court['status']); ?>
                            </div>
                            <p><?php echo htmlspecialchars($court['description']); ?></p>
                            <p><strong>Capacity:</strong> <?php echo $court['capacity']; ?> players</p>
                            <?php if ($court['status'] === 'available'): ?>
                            <a href="booking.php?court_id=<?php echo $court['id']; ?>" 
                               class="btn btn-primary">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <h2>No courts found</h2>
                    <p>Try different search terms or browse all available courts.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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
</body>
</html> 