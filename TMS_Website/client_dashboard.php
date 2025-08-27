<?php
session_start();
include 'includes/db_config.php';

// Client authorization
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: auth.php");
    exit();
}

$clientId = $_SESSION['client_id'];

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Fetch bookings
    $stmtBookings = $conn->prepare("SELECT id, source, destination, booking_date, status FROM bookings WHERE user_id = ? ORDER BY booking_date DESC");
    $stmtBookings->bind_param("i", $clientId);
    $stmtBookings->execute();
    $resultBookings = $stmtBookings->get_result();
    $bookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
    $resultBookings->free_result();
    $stmtBookings->close();

    // Fetch shipments
    $stmtShipments = $conn->prepare("SELECT s.*, v.vehicle_number FROM shipments s LEFT JOIN vehicles v ON s.vehicle_id = v.id WHERE client_id = ? ORDER BY pickup_time DESC");
    $stmtShipments->bind_param("i", $clientId);
    $stmtShipments->execute();
    $resultShipments = $stmtShipments->get_result();
    $shipments = $resultShipments->fetch_all(MYSQLI_ASSOC);
    $resultShipments->free_result();
    $stmtShipments->close();

    // Fetch payments
    $stmtPayments = $conn->prepare("SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC");
    $stmtPayments->bind_param("i", $clientId);
    $stmtPayments->execute();
    $resultPayments = $stmtPayments->get_result();
    $payments = $resultPayments->fetch_all(MYSQLI_ASSOC);
    $resultPayments->free_result();
    $stmtPayments->close();


    $conn->close();

} catch (Exception $e) {
    error_log("Client Dashboard Error: " . $e->getMessage());
    die("Error: An unexpected error occurred. Please try again later.");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="css/client_dashboard.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div class="container">
        <nav>
            <a href="client_dashboard.php">Dashboard</a>
            <a href="client_profile.php">Profile</a>  <!-- Added Profile link -->
            <a href="logout.php">Logout</a>
        </nav>
        <h1>Client Dashboard</h1>

        <!-- Bookings Table -->
        <h2>Your Bookings</h2>
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo $booking['source']; ?></td>
                        <td><?php echo $booking['destination']; ?></td>
                        <td><?php echo $booking['booking_date']; ?></td>
                        <td><?php echo $booking['status']; ?></td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <a href="cancel_booking.php?booking_id=<?php echo $booking['id']; ?>">Cancel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Shipments Table -->
        <h2>Your Shipments</h2>
        <table>
            <thead>
                <tr>
                    <th>Shipment ID</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Pickup Time</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shipments as $shipment): ?>
                    <tr>
                        <td><?php echo $shipment['id']; ?></td>
                        <td><?php echo $shipment['source']; ?></td>
                        <td><?php echo $shipment['destination']; ?></td>
                        <td><?php echo $shipment['pickup_time']; ?></td>
                        <td><?php echo $shipment['vehicle_number']; ?></td>
                        <td><?php echo $shipment['status']; ?></td>
                        <td>
                            <div id="payment-element-<?php echo $shipment['id']; ?>"></div>
                            <button id="submit-<?php echo $shipment['id']; ?>">Pay Now</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Payments Table -->
        <h2>Your Payments</h2>
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Shipment ID</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo $payment['id']; ?></td>
                        <td><?php echo $payment['shipment_id']; ?></td>
                        <td><?php echo $payment['payment_date']; ?></td>
                        <td><?php echo $payment['amount']; ?></td>
                        <td><?php echo $payment['method']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="logout.php" class="button">Logout</a>
        <a href="create_booking.php" class="button">Create New Booking</a>
    </div>

    <script>
        const stripe = Stripe('pk_test_51QVdj1G71oah2ZYtX019tWel9nx7Rw9JgYiEk7IdliYKVbLrPJ7DtwmjUIYeICUcuFC3ppAwic8knqv5vnbnjXYg00ubqLPFkG');

        <?php
        $jsCode = "";
        foreach ($shipments as $shipment) {
            $amount = $shipment['price'] * 100; 
            $shipmentId = $shipment['id'];

            //Improved error handling in JS code
            $jsCode .= sprintf(
                "const paymentElement%s = document.getElementById('payment-element-%s');
                 const submitButton%s = document.getElementById('submit-%s');
                 submitButton%s.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        const { error, clientSecret } = await fetch('/process_payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ amount: %s, shipmentId: %s })
                        }).then(r => r.json());
                        if (error) {
                            alert(error);
                            return;
                        }
                        const result = await stripe.confirmCardPayment(clientSecret, { payment_method: { card: null, billing_details: { name: 'Client Name' } } });
                        if (result.error) {
                            alert(result.error.message);
                        } else {
                            alert('Payment successful!');
                        }
                    } catch (error) {
                        console.error('Payment error:', error);
                        alert('An error occurred during payment. Please try again later.');
                    }
                });",
                $shipmentId, $shipmentId, $shipmentId, $shipmentId, $amount, $shipmentId
            );
        }
        echo $jsCode;
        ?>
    </script>
</body>
</html>
