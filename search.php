<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Initialize search parameters
$search_query = isset($_GET['query']) ? filter_var($_GET['query'], FILTER_SANITIZE_STRING) : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$time_slot = isset($_GET['time_slot']) ? $_GET['time_slot'] : '';
$min_price = isset($_GET['min_price']) ? filter_var($_GET['min_price'], FILTER_VALIDATE_FLOAT) : '';
$max_price = isset($_GET['max_price']) ? filter_var($_GET['max_price'], FILTER_VALIDATE_FLOAT) : '';
$rating = isset($_GET['rating']) ? filter_var($_GET['rating'], FILTER_VALIDATE_INT) : '';
$availability = isset($_GET['availability']) ? filter_var($_GET['availability'], FILTER_VALIDATE_BOOLEAN) : false;

// Build the base query
$query = "
    SELECT 
        c.*,
        COALESCE(AVG(r.rating), 0) as average_rating,
        COUNT(DISTINCT r.id) as total_ratings,
        GROUP_CONCAT(DISTINCT f.name) as features,
        (
            SELECT COUNT(*) 
            FROM bookings b 
            WHERE b.court_id = c.id 
            AND b.booking_date = ? 
            AND b.status != 'cancelled'
        ) as bookings_count
    FROM courts c
    LEFT JOIN ratings r ON c.id = r.court_id
    LEFT JOIN court_features cf ON c.id = cf.court_id
    LEFT JOIN features f ON cf.feature_id = f.id
    WHERE 1=1
";

$params = array($date);
$types = "s";

// Add search conditions
if (!empty($search_query)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($min_price)) {
    $query .= " AND c.price_per_hour >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if (!empty($max_price)) {
    $query .= " AND c.price_per_hour <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($rating)) {
    $query .= " HAVING average_rating >= ?";
    $params[] = $rating;
    $types .= "d";
}

if ($availability) {
    $query .= " AND (
        SELECT COUNT(*) 
        FROM bookings b 
        WHERE b.court_id = c.id 
        AND b.booking_date = ?
        AND b.status != 'cancelled'
    ) < c.slots_per_day";
    $params[] = $date;
    $types .= "s";
}

$query .= " GROUP BY c.id ORDER BY average_rating DESC, c.name ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all available features for filter
$features_query = "SELECT DISTINCT name FROM features ORDER BY name";
$features_result = $conn->query($features_query);
$available_features = $features_result->fetch_all(MYSQLI_ASSOC);

// Get price range
$price_query = "SELECT MIN(price_per_hour) as min_price, MAX(price_per_hour) as max_price FROM courts";
$price_result = $conn->query($price_query);
$price_range = $price_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Courts - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .filter-section {
            margin-bottom: 1.5rem;
        }
        .filter-section h3 {
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .filter-group input[type="text"],
        .filter-group input[type="number"],
        .filter-group input[type="date"],
        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
        }
        .price-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
            transition: transform 0.2s;
        }
        .court-card:hover {
            transform: translateY(-5px);
        }
        .court-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .court-info {
            padding: 1.5rem;
        }
        .court-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .court-rating {
            color: #ffc107;
            margin-bottom: 0.5rem;
        }
        .court-price {
            font-size: 1.1rem;
            color: #28a745;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        .court-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .feature-tag {
            background: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .book-btn {
            width: 100%;
            padding: 0.75rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .book-btn:hover {
            background: #218838;
        }
        .availability {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .availability-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .available {
            background: #28a745;
        }
        .limited {
            background: #ffc107;
        }
        .full {
            background: #dc3545;
        }
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">⚽ El 7arefaa Court</div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="search-container">
        <aside class="filters">
            <form id="searchForm" method="GET" action="search.php">
                <div class="filter-section">
                    <h3>Search</h3>
                    <div class="filter-group">
                        <input type="text" name="query" placeholder="Search courts..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Date & Time</h3>
                    <div class="filter-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" 
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo $date; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="time_slot">Time Slot</label>
                        <select id="time_slot" name="time_slot">
                            <option value="">Any Time</option>
                            <option value="morning" <?php echo $time_slot === 'morning' ? 'selected' : ''; ?>>Morning</option>
                            <option value="afternoon" <?php echo $time_slot === 'afternoon' ? 'selected' : ''; ?>>Afternoon</option>
                            <option value="evening" <?php echo $time_slot === 'evening' ? 'selected' : ''; ?>>Evening</option>
                        </select>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Price Range</h3>
                    <div class="price-range">
                        <div class="filter-group">
                            <label for="min_price">Min Price</label>
                            <input type="number" id="min_price" name="min_price" 
                                   min="<?php echo $price_range['min_price']; ?>" 
                                   value="<?php echo $min_price; ?>">
                        </div>
                        <div class="filter-group">
                            <label for="max_price">Max Price</label>
                            <input type="number" id="max_price" name="max_price" 
                                   max="<?php echo $price_range['max_price']; ?>"
                                   value="<?php echo $max_price; ?>">
                        </div>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Rating</h3>
                    <div class="filter-group">
                        <select name="rating">
                            <option value="">Any Rating</option>
                            <?php for ($i = 4; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $rating == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>+ Stars
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-section">
                    <h3>Availability</h3>
                    <div class="filter-group">
                        <label>
                            <input type="checkbox" name="availability" value="1" 
                                   <?php echo $availability ? 'checked' : ''; ?>>
                            Show only available courts
                        </label>
                    </div>
                </div>

                <button type="submit" class="book-btn">Apply Filters</button>
            </form>
        </aside>

        <main class="courts-grid">
            <?php if (empty($courts)): ?>
                <div class="no-results">
                    <h2>No courts found</h2>
                    <p>Try adjusting your search filters</p>
                </div>
            <?php else: ?>
                <?php foreach ($courts as $court): ?>
                    <div class="court-card">
                        <img src="<?php echo htmlspecialchars($court['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($court['name']); ?>"
                             class="court-image">
                        <div class="court-info">
                            <h3 class="court-name"><?php echo htmlspecialchars($court['name']); ?></h3>
                            
                            <div class="court-rating">
                                <?php
                                $rating = round($court['average_rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                echo " ({$court['total_ratings']} reviews)";
                                ?>
                            </div>

                            <div class="court-price">
                                $<?php echo number_format($court['price_per_hour'], 2); ?> per hour
                            </div>

                            <?php
                            $availability_class = '';
                            $availability_text = '';
                            $slots_taken = $court['bookings_count'];
                            $total_slots = $court['slots_per_day'];
                            
                            if ($slots_taken >= $total_slots) {
                                $availability_class = 'full';
                                $availability_text = 'Fully Booked';
                            } elseif ($slots_taken >= $total_slots * 0.7) {
                                $availability_class = 'limited';
                                $availability_text = 'Limited Availability';
                            } else {
                                $availability_class = 'available';
                                $availability_text = 'Available';
                            }
                            ?>
                            <div class="availability">
                                <span class="availability-indicator <?php echo $availability_class; ?>"></span>
                                <?php echo $availability_text; ?>
                            </div>

                            <?php if (!empty($court['features'])): ?>
                                <div class="court-features">
                                    <?php foreach (explode(',', $court['features']) as $feature): ?>
                                        <span class="feature-tag">
                                            <?php echo htmlspecialchars($feature); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <a href="booking.php?court_id=<?php echo $court['id']; ?>&date=<?php echo $date; ?>" 
                               class="book-btn">
                                Book Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Real-time price range validation
        const minPrice = document.getElementById('min_price');
        const maxPrice = document.getElementById('max_price');

        minPrice.addEventListener('change', function() {
            if (maxPrice.value && Number(this.value) > Number(maxPrice.value)) {
                maxPrice.value = this.value;
            }
        });

        maxPrice.addEventListener('change', function() {
            if (minPrice.value && Number(this.value) < Number(minPrice.value)) {
                minPrice.value = this.value;
            }
        });

        // Date validation
        const dateInput = document.getElementById('date');
        dateInput.min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>
