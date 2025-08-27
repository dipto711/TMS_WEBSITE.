<?php
session_start();
include 'includes/db_config.php';

// Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $bookingId = filter_var($_POST['booking_id'], FILTER_VALIDATE_INT);
    $newStatus = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    if ($bookingId <= 0 || !in_array($newStatus, ['confirmed', 'rejected'])) {
        echo "<p class='error'>Invalid booking ID or status.</p>";
        exit;
    }

    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $bookingId);
        $stmt->execute();

        if ($stmt->execute()) {
            if ($newStatus === 'confirmed') {
                // Redirect to create_shipment.php after successful confirmation
                header("Location: create_shipment.php?booking_id=" . $bookingId);
                exit;
            } else {
                echo "<p class='success'>Booking status updated successfully!</p>";
                header("Location: admin_bookings.php");
                exit;
            }
        } else {
            throw new Exception("Error updating booking status: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("update_booking_status.php Error: " . $e->getMessage());
        die("An unexpected error occurred.");
    } finally {
        if (isset($conn)) $conn->close();
    }
} else {
    echo "<p class='error'>Invalid request or missing parameters.</p>";
}

?>
