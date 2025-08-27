<?php
session_start();
include 'includes/db_config.php';

//Check if the driver is logged in. If not, redirect to the index page
if (!isset($_SESSION['driver_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: index.php");
    exit();
}

//Get shipment ID from URL parameter
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $shipmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($shipmentId === false || $shipmentId <= 0) {
        die("Invalid shipment ID.");
    }

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Corrected query to include bookings and vehicles tables
        $stmt = $conn->prepare("SELECT s.*, c.name AS client_name, c.email AS client_email, c.client_phone AS client_phone, b.source, b.destination
                                 FROM shipments s
                                 JOIN users c ON s.client_id = c.id
                                 JOIN bookings b ON s.booking_id = b.id
                                 WHERE s.id = ?");

        $stmt->bind_param("i", $shipmentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $shipmentDetails = $result->fetch_assoc();
        } else {
            throw new Exception("Shipment not found.");
        }

        $stmt->close();
        $conn->close();

    } catch (mysqli_sql_exception $e) {
        error_log("MySQL Error in driver_shipment_details.php: " . $e->getMessage() . " - Code: " . $e->getCode());
        die("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error in driver_shipment_details.php: " . $e->getMessage());
        die("An unexpected error occurred.");
    }

    // Display shipment details (HTML)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Shipment Details</title>
    </head>
    <body>
        <h1>Shipment Details</h1>
        <p><strong>Shipment ID:</strong> <?php echo $shipmentDetails['id']; ?></p>
        <p><strong>Client:</strong> <?php echo $shipmentDetails['client_name']; ?></p>
        <p><strong>Client Email:</strong> <?php echo $shipmentDetails['client_email']; ?></p>
        <p><strong>Client Phone:</strong> <?php echo $shipmentDetails['client_phone'] ?: 'Not provided'; ?></p>
        <p><strong>Source:</strong> <?php echo $shipmentDetails['source']; ?></p>
        <p><strong>Destination:</strong> <?php echo $shipmentDetails['destination']; ?></p>
        <p><strong>Status:</strong> <?php echo $shipmentDetails['status']; ?></p>
        <!-- Add other shipment details as needed -->
        <?php if ($shipmentDetails['destination'] == 0): ?>
            <p style="color: red;">Warning: Destination is set to 0.</p>
        <?php endif; ?>
    </body>
    </html>
    <?php

} else {
    die("Shipment ID not provided.");
}

?>
