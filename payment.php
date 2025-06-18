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

// Handle refund request
if (isset($_POST['request_refund']) && isset($_POST['payment_id'])) {
    $payment_id = filter_var($_POST['payment_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Check if payment exists and belongs to user
    $stmt = $conn->prepare("
        SELECT p.*, b.status as booking_status 
        FROM payments p 
        JOIN bookings b ON p.booking_id = b.id 
        WHERE p.id = ? AND p.user_id = ? AND p.status = 'completed'
    ");
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if ($payment && $payment['booking_status'] !== 'completed') {
        // Start transaction for refund
        $conn->begin_transaction();

        try {
            // Update payment status
            $stmt = $conn->prepare("
                UPDATE payments 
                SET status = 'refunded', 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();

            // Update booking status
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'cancelled',
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $payment['booking_id']);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            $success_message = "Refund request processed successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error processing refund. Please try again.";
        }
    } else {
        $error_message = "Invalid refund request or payment not eligible for refund.";
    }
}

// Get booking details if booking_id is provided
if (isset($_GET['booking_id'])) {
    $booking_id = filter_var($_GET['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            c.name AS court_name,
            c.price_per_hour,
            c.image_url,
            u.email,
            u.phone
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending'
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        header('Location: mybooking.php');
        exit();
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $payment_method = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
    
    $errors = [];
    
    if ($payment_method === 'credit_card' || $payment_method === 'debit_card') {
        $card_number = filter_var($_POST['card_number'], FILTER_SANITIZE_STRING);
        $card_holder = filter_var($_POST['card_holder'], FILTER_SANITIZE_STRING);
        $expiry_date = filter_var($_POST['expiry_date'], FILTER_SANITIZE_STRING);
        $cvv = filter_var($_POST['cvv'], FILTER_SANITIZE_STRING);

        // Basic validation
        if (empty($card_number) || strlen($card_number) < 16) {
            $errors[] = "Invalid card number";
        }
        if (empty($card_holder)) {
            $errors[] = "Card holder name is required";
        }
        if (empty($expiry_date) || !preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $expiry_date)) {
            $errors[] = "Invalid expiry date (MM/YY format required)";
        }
        if (empty($cvv) || !preg_match("/^[0-9]{3,4}$/", $cvv)) {
            $errors[] = "Invalid CVV";
        }
    } elseif ($payment_method === 'paypal') {
        // PayPal validation would go here
        // In a real implementation, this would integrate with PayPal's API
        $paypal_email = filter_var($_POST['paypal_email'], FILTER_VALIDATE_EMAIL);
        if (!$paypal_email) {
            $errors[] = "Invalid PayPal email address";
        }
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Create payment record
            $stmt = $conn->prepare("
                INSERT INTO payments (
                    booking_id,
                    user_id,
                    amount,
                    payment_method,
                    card_last_four,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ");

            $card_last_four = $payment_method === 'paypal' ? null : substr($card_number, -4);
            $stmt->bind_param("iidss", 
                $booking_id,
                $user_id,
                $booking['total_price'],
                $payment_method,
                $card_last_four
            );
            $stmt->execute();
            $payment_id = $conn->insert_id;

            // Update booking status
            $stmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'active',
                    payment_id = ?,
                    updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("iii", $payment_id, $booking_id, $user_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            // Send confirmation email
            $to = $booking['email'];
            $subject = "Booking Confirmation - El 7arefaa Court";
            $message = "
                <html>
                <body>
                    <h2>Booking Confirmation</h2>
                    <p>Thank you for your booking at El 7arefaa Court!</p>
                    <h3>Booking Details:</h3>
                    <ul>
                        <li>Court: {$booking['court_name']}</li>
                        <li>Date: " . date('F j, Y', strtotime($booking['booking_date'])) . "</li>
                        <li>Time: " . date('g:i A', strtotime($booking['start_time'])) . " - " . 
                                    date('g:i A', strtotime($booking['end_time'])) . "</li>
                        <li>Amount Paid: $" . number_format($booking['total_price'], 2) . "</li>
                        <li>Booking ID: #{$booking_id}</li>
                    </ul>
                    <p>We look forward to seeing you!</p>
                </body>
                </html>
            ";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: El 7arefaa Court <noreply@el7arefaa.com>' . "\r\n";

            mail($to, $subject, $message, $headers);

            $success_message = "Payment processed successfully! A confirmation email has been sent.";
            header("refresh:3;url=mybooking.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error processing payment. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .total-row {
            border-top: 2px solid #dee2e6;
            margin-top: 1rem;
            padding-top: 1rem;
            font-weight: 600;
            font-size: 1.1em;
        }
        .payment-form {
            display: grid;
            gap: 1.5rem;
        }
        .form-group {
            display: grid;
            gap: 0.5rem;
        }
        .form-group label {
            font-weight: 500;
        }
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 1rem;
        }
        .card-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
        }
        .submit-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
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
        .payment-methods {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .payment-method {
            flex: 1;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .payment-method i {
            font-size: 2.5rem;
            color: #6c757d;
        }
        .payment-method.selected {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .payment-method.selected i {
            color: #007bff;
        }
        .payment-form-section {
            display: none;
        }
        .payment-form-section.active {
            display: block;
        }
        .paypal-section {
            text-align: center;
            padding: 2rem;
        }
        .paypal-logo {
            max-width: 200px;
            margin-bottom: 1rem;
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

    <div class="payment-container">
        <h1>Payment Details</h1>

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

        <?php if (isset($booking)): ?>
            <div class="booking-summary">
                <h2>Booking Summary</h2>
                <div class="summary-row">
                    <span>Court:</span>
                    <span><?php echo htmlspecialchars($booking['court_name']); ?></span>
                </div>
                <div class="summary-row">
                    <span>Date:</span>
                    <span><?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></span>
                </div>
                <div class="summary-row">
                    <span>Time:</span>
                    <span>
                        <?php 
                        echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . 
                             date('g:i A', strtotime($booking['end_time']));
                        ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Duration:</span>
                    <span>
                        <?php 
                        $duration = (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600;
                        echo $duration . ' hour(s)';
                        ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Rate:</span>
                    <span>$<?php echo number_format($booking['price_per_hour'], 2); ?> per hour</span>
                </div>
                <div class="summary-row total-row">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($booking['total_price'], 2); ?></span>
                </div>
            </div>

            <form method="POST" class="payment-form" id="paymentForm">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                
                <div class="payment-methods">
                    <div class="payment-method selected" onclick="selectPaymentMethod('credit_card', this)">
                        <i class="fas fa-credit-card"></i>
                        <div>Credit Card</div>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('debit_card', this)">
                        <i class="fas fa-credit-card"></i>
                        <div>Debit Card</div>
                    </div>
                    <div class="payment-method" onclick="selectPaymentMethod('paypal', this)">
                        <i class="fab fa-paypal"></i>
                        <div>PayPal</div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="payment_method" value="credit_card">

                <div id="cardSection" class="payment-form-section active">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" required 
                               maxlength="16" placeholder="1234 5678 9012 3456"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <div class="form-group">
                        <label for="card_holder">Card Holder Name</label>
                        <input type="text" id="card_holder" name="card_holder" required 
                               placeholder="John Doe">
                    </div>

                    <div class="card-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" name="expiry_date" required 
                                   placeholder="MM/YY" maxlength="5"
                                   oninput="formatExpiryDate(this)">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="password" id="cvv" name="cvv" required 
                                   maxlength="4" placeholder="123"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>
                </div>

                <div id="paypalSection" class="payment-form-section">
                    <div class="paypal-section">
                        <img src="images/paypal-logo.png" alt="PayPal" class="paypal-logo">
                        <div class="form-group">
                            <label for="paypal_email">PayPal Email</label>
                            <input type="email" id="paypal_email" name="paypal_email" 
                                   placeholder="Enter your PayPal email">
                        </div>
                    </div>
                </div>

                <button type="submit" name="process_payment" class="submit-btn">
                    Pay $<?php echo number_format($booking['total_price'], 2); ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        function selectPaymentMethod(method, element) {
            document.getElementById('payment_method').value = method;
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            element.classList.add('selected');

            // Show/hide appropriate form sections
            document.getElementById('cardSection').classList.toggle('active', 
                method === 'credit_card' || method === 'debit_card');
            document.getElementById('paypalSection').classList.toggle('active', 
                method === 'paypal');
        }

        function formatExpiryDate(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            input.value = value;
        }

        // Format card number with spaces
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });
    </script>
</body>
</html>
