<?php
session_start();
include 'includes/db_config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_GET['booking_id'])) {
    $bookingId = filter_var($_GET['booking_id'], FILTER_VALIDATE_INT);
    if ($bookingId <= 0) {
        echo "<p class='error'>Invalid booking ID</p>";
        exit;
    }

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT user_id, source, destination, status, special_instructions, items_list, vehicle_id FROM bookings WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing booking select statement: " . $conn->error);
        }
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Booking not found.");
        }
        if (empty($booking['source']) || empty($booking['destination'])) {
            throw new Exception("Source or destination is missing in the booking.");
        }
        if ($booking['status'] !== 'confirmed') {
            throw new Exception("Booking not confirmed.");
        }

        $userId = filter_var($booking['user_id'], FILTER_VALIDATE_INT);
        $source = mysqli_real_escape_string($conn, $booking['source']);
        $destination = mysqli_real_escape_string($conn, $booking['destination']);
        $specialInstructions = mysqli_real_escape_string($conn, $booking['special_instructions'] ?? '');
        $itemsList = mysqli_real_escape_string($conn, $booking['items_list'] ?? '');
        $vehicleId = $booking['vehicle_id'];


        $sqlDriver = "SELECT id FROM users WHERE role = 'driver' AND id NOT IN (SELECT driver_id FROM shipments WHERE status != 'delivered') ORDER BY RAND() LIMIT 1";
        $resultDriver = $conn->query($sqlDriver);
        if ($resultDriver === false) {
            throw new Exception("Error selecting driver: " . $conn->error);
        }
        if ($resultDriver->num_rows === 0) {
            throw new Exception("No available drivers found.");
        }
        $defaultDriverRow = $resultDriver->fetch_assoc();
        $defaultDriver = $defaultDriverRow['id'];
        $resultDriver->free_result();


        $sqlInsert = "INSERT INTO shipments (booking_id, driver_id, vehicle_id, status, special_instructions, items_list, pickup_time) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $conn->prepare($sqlInsert);
        if (!$stmtInsert) {
            throw new Exception("Error preparing shipment insert statement: " . $conn->error);
        }
        $status = 'pending';
        $stmtInsert->bind_param("iiisss", $bookingId, $defaultDriver, $vehicleId, $status, $specialInstructions, $itemsList);
        $stmtInsert->execute();
        if ($stmtInsert->affected_rows <= 0) {
            throw new Exception("Shipment creation failed: " . $stmtInsert->error);
        }

        $shipmentId = $conn->insert_id;

        $updateBookingSql = "UPDATE bookings SET shipment_id = ? WHERE id = ?";
        $updateBookingStmt = $conn->prepare($updateBookingSql);
        if (!$updateBookingStmt) {
            throw new Exception("Error preparing booking update statement: " . $conn->error);
        }
        $updateBookingStmt->bind_param("ii", $shipmentId, $bookingId);
        $updateBookingStmt->execute();
        if ($updateBookingStmt->affected_rows <= 0) {
            throw new Exception("Update of booking failed: " . $updateBookingStmt->error);
        }
        $updateBookingStmt->close();


        echo "<p>Last Inserted ID into shipments: " . $shipmentId . "</p>";
        echo "<p>Shipment created successfully!</p>";
        header("Location: admin_bookings.php");
        exit;

    } catch (Exception $e) {
        error_log("create_shipment.php Error: " . $e->getMessage());
        header("Location: admin_bookings.php?error=" . urlencode($e->getMessage()));
        exit;
    } finally {
        if (isset($conn)) $conn->close();
    }
} else {
    echo "<p class='error'>Booking ID not provided</p>";
}
?>
