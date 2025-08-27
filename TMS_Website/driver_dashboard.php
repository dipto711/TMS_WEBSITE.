<?php
session_start();
include 'includes/db_config.php';

// Robust driver authorization
if (!isset($_SESSION['driver_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: auth.php");
    exit();
}

$driverId = $_SESSION['driver_id'];
$currentShipment = null;
$upcomingShipments = [];
$errorMessage = null;

// Debugging: Check session data
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    var_dump($_SESSION);
}

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT s.*, c.name AS client_name, c.email AS client_email, c.phone AS client_phone, 
                    s.special_instructions, s.items_list, b.source, b.destination, b.booking_date, v.vehicle_number, v.type
            FROM shipments s
            JOIN users c ON s.client_id = c.id
            LEFT JOIN bookings b ON s.booking_id = b.id
            LEFT JOIN vehicles v ON s.vehicle_id = v.id
            WHERE s.driver_id = ?
            AND s.status IN ('pending', 'picked_up')
            ORDER BY s.status ASC, s.pickup_time ASC"; //Removed LOWER for more accurate sorting

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("i", $driverId);
    if (!$stmt->execute()) {
        throw new Exception("Statement execution failed: " . $stmt->error);
    }
    $result = $stmt->get_result();


    if ($result && $result->num_rows > 0) {
        $shipments = $result->fetch_all(MYSQLI_ASSOC);

        //Separate pending and picked_up shipments
        $pendingShipments = [];
        $pickedUpShipments = [];

        foreach ($shipments as $shipment) {
            if ($shipment['status'] === 'pending') {
                $pendingShipments[] = $shipment;
            } elseif ($shipment['status'] === 'picked_up') {
                $pickedUpShipments[] = $shipment;
            }
        }

        //Allow only one pending shipment to be displayed/managed at a time
        $currentShipment = isset($pendingShipments[0]) ? $pendingShipments[0] : null;
        $upcomingShipments = $pickedUpShipments;
    }
    $stmt->close();
    $conn->close();


} catch (mysqli_sql_exception $e) {
    $errorMessage = "MySQL Error: " . $e->getMessage();
    error_log("MySQL Error in driver_dashboard.php: " . $e->getMessage());
} catch (Exception $e) {
    $errorMessage = "An unexpected error occurred: " . $e->getMessage();
    error_log("Error in driver_dashboard.php: " . $e->getMessage());
}

// More specific error handling
if ($errorMessage) {
    echo "<p class='error'>" . $errorMessage . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <link rel="stylesheet" href="css/driver_dashboard.css">
</head>
<body>
    <div class="container">
        <nav>
            <a href="driver_dashboard.php">Dashboard</a>
            <a href="driver_profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
        <h1>Driver Dashboard</h1>

        <?php if ($currentShipment): ?>
            <section class="shipment current-shipment">
                <h2>Current Shipment</h2>
                <p><strong>Shipment ID:</strong> <?php echo htmlspecialchars($currentShipment['id']); ?></p>
                <p><strong>Client:</strong> <?php echo htmlspecialchars($currentShipment['client_name']); ?> (<?php echo htmlspecialchars($currentShipment['client_email']); ?>) - <?php echo htmlspecialchars($currentShipment['client_phone']); ?></p>
                <p><strong>Pickup:</strong> <?php echo htmlspecialchars($currentShipment['source']); ?></p>
                <p><strong>Delivery:</strong> <?php echo htmlspecialchars($currentShipment['destination']); ?></p>
                <p><strong>Special Instructions:</strong> <?php echo htmlspecialchars($currentShipment['special_instructions']); ?></p>
                <p><strong>Items:</strong> <?php echo htmlspecialchars($currentShipment['items_list']); ?></p>

                <form method="post" action="update_status.php?id=<?php echo $currentShipment['id']; ?>">
                    <button type="submit" name="status" value="Picked Up">Pick Up</button>
                    <button type="submit" name="status" value="In Transit">In Transit</button>
                    <button type="submit" name="status" value="Delayed">Delayed</button>
                    <button type="submit" name="status" value="Delivered">Delivered</button>
                    <button type="submit" name="status" value="Cancelled">Cancelled</button>
                </form>
            </section>
        <?php else: ?>
            <p>No current shipments.</p>
        <?php endif; ?>

        <?php if (!empty($upcomingShipments)): ?>
            <h2>Upcoming Shipments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Shipment ID</th>
                        <th>Client</th>
                        <th>Pickup</th>
                        <th>Delivery</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingShipments as $shipment): ?>
                        <tr>
                            <td><a href="driver_shipment_details.php?id=<?php echo $shipment['id']; ?>"><?php echo $shipment['id']; ?></a></td>
                            <td><?php echo $shipment['client_name']; ?></td>
                            <td><?php echo $shipment['source']; ?></td>
                            <td><?php echo $shipment['destination']; ?></td>
                            <td><?php echo $shipment['status']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No upcoming shipments.</p>
        <?php endif; ?>

        <a href="logout.php" class="button">Logout</a>
    </div>
</body>
</html>