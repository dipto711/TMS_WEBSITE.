<?php
include 'includes/db_config.php';
session_start();

if (isset($_GET['booking_id'])) {
    $bookingId = filter_var($_GET['booking_id'], FILTER_VALIDATE_INT);

    if ($bookingId === false || $bookingId <= 0) {
        echo "<p class='error'>Invalid booking ID</p>";
        exit;
    }

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        if (!$stmt->execute()) {
            throw new Exception("Error cancelling booking: " . $stmt->error);
        }
        $stmt->close();
        header("Location: client_dashboard.php");
        exit;

    } catch (Exception $e) {
        error_log("Error in cancel_booking.php: " . $e->getMessage());
        echo "<p class='error'>Error cancelling booking: " . $e->getMessage() . "</p>"; // More user-friendly error message
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo "<p class='error'>Booking ID not provided</p>";
}
?>
