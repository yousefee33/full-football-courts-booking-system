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
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total number of payments
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM payments p 
    WHERE p.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Fetch payments with booking details
$stmt = $conn->prepare("
    SELECT 
        p.*,
        b.booking_date,
        b.start_time,
        b.end_time,
        c.name AS court_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN courts c ON b.court_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $per_page, $offset);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle receipt generation
if (isset($_GET['generate_receipt']) && isset($_GET['payment_id'])) {
    $payment_id = filter_var($_GET['payment_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Fetch payment details for receipt
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            b.booking_date,
            b.start_time,
            b.end_time,
            c.name AS court_name,
            u.email,
            u.name,
            u.phone
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN courts c ON b.court_id = c.id
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ? AND p.user_id = ?
    ");
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $receipt_data = $stmt->get_result()->fetch_assoc();

    if ($receipt_data) {
        // Generate PDF receipt
        require_once('tcpdf/tcpdf.php');

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('El 7arefaa Court');
        $pdf->SetAuthor('El 7arefaa Court');
        $pdf->SetTitle('Payment Receipt');

        $pdf->AddPage();

        // Add logo and header
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'El 7arefaa Court - Payment Receipt', 0, 1, 'C');
        $pdf->Ln(10);

        // Add receipt details
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Receipt #: ' . $payment_id, 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($receipt_data['created_at'])), 0, 1);
        $pdf->Cell(0, 10, 'Customer: ' . $receipt_data['name'], 0, 1);
        $pdf->Cell(0, 10, 'Email: ' . $receipt_data['email'], 0, 1);
        $pdf->Ln(5);

        // Booking details
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Booking Details', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Court: ' . $receipt_data['court_name'], 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($receipt_data['booking_date'])), 0, 1);
        $pdf->Cell(0, 10, 'Time: ' . date('g:i A', strtotime($receipt_data['start_time'])) . 
                         ' - ' . date('g:i A', strtotime($receipt_data['end_time'])), 0, 1);
        $pdf->Ln(5);

        // Payment details
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Payment Details', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Payment Method: ' . ucfirst($receipt_data['payment_method']), 0, 1);
        $pdf->Cell(0, 10, 'Amount Paid: $' . number_format($receipt_data['amount'], 2), 0, 1);
        $pdf->Cell(0, 10, 'Status: ' . ucfirst($receipt_data['status']), 0, 1);

        // Output PDF
        $pdf->Output('Receipt_' . $payment_id . '.pdf', 'D');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - El 7arefaa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/common.css">
    <style>
        .payment-history-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .payment-table th,
        .payment-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .payment-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .payment-table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background: #cce5ff;
            color: #004085;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
        }
        .pagination a:hover {
            background: #f8f9fa;
        }
        .pagination .active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .btn-receipt {
            background: #28a745;
            color: white;
        }
        .btn-receipt:hover {
            background: #218838;
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

    <div class="payment-history-container">
        <h1>Payment History</h1>
        
        <?php if (empty($payments)): ?>
            <p>No payment records found.</p>
        <?php else: ?>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Court</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($payment['court_name']); ?></td>
                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $payment['status']; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?generate_receipt=1&payment_id=<?php echo $payment['id']; ?>" 
                                   class="btn btn-receipt">
                                    <i class="fas fa-file-pdf"></i> Receipt
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="<?php echo $page === $i ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 