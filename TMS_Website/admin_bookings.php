<?php
session_start();
include 'includes/db_config.php';

// Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$errorMessage = null;
$bookings = []; // Initialize an empty array to hold bookings

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT b.*, u.name AS client_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.status = ?");
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    $status = "pending";
    $stmt->bind_param("s", $status);
    if (!$stmt->execute()) {
        throw new Exception("Statement execution failed: " . $stmt->error); //Added error handling here
    }
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Result set is empty or an error occurred: " . $stmt->error); //Added error handling here
    }
    $bookings = $result->fetch_all(MYSQLI_ASSOC); //fetch all at once for better efficiency
    $result->free_result();
    $stmt->close();


} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("Error in admin_bookings.php: " . $errorMessage);
} finally {
    if (isset($conn) && is_object($conn) && $conn->connect_errno === 0 ) {
        $conn->close();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Bookings</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_bookings.css">
    <script src="js/admin_bookings.js" defer></script>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-calendar-check"></i> Manage Bookings</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?></p>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No pending bookings found</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo $booking['client_name']; ?></td>
                                <td><?php echo $booking['source']; ?></td>
                                <td><?php echo $booking['destination']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo $booking['status']; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <form method="post" action="update_booking_status.php">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-confirm">
                                            <i class="fas fa-check"></i> Confirm
                                        </button>
                                    </form>
                                    <form method="post" action="update_booking_status.php">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-reject">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <a href="admin_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
