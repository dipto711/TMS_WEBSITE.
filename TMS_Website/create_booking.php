<?php
include 'includes/db_config.php';
session_start();

$error = null;
$availableVehicles = [];

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Fetch available vehicles
    $sqlVehicles = "SELECT id, vehicle_number, type FROM vehicles WHERE status = 'available'";
    $resultVehicles = $conn->query($sqlVehicles);
    if ($resultVehicles === false) {
        throw new Exception("Error fetching vehicles: " . $conn->error);
    }
    $availableVehicles = $resultVehicles->fetch_all(MYSQLI_ASSOC);
    $resultVehicles->free_result();


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $clientId = $_SESSION['client_id'];
        $source = filter_input(INPUT_POST, 'source', FILTER_SANITIZE_STRING);
        $destination = filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING);
        $vehicleId = filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT);

        // Validate inputs
        if (empty($source) || empty($destination)) {
            $error = "Source and destination are required.";
        } elseif (strlen($source) < 3 || strlen($destination) < 3) {
            $error = "Source and destination must be at least 3 characters long.";
        } elseif ($vehicleId <= 0) {
            $error = "Please select a vehicle.";
        } else {
            try {
                //Check if vehicle exists
                $sqlCheckVehicle = "SELECT id FROM vehicles WHERE id = ?";
                $stmtCheckVehicle = $conn->prepare($sqlCheckVehicle);
                $stmtCheckVehicle->bind_param("i",$vehicleId);
                $stmtCheckVehicle->execute();
                $resultCheckVehicle = $stmtCheckVehicle->get_result();
                if ($resultCheckVehicle->num_rows == 0){
                    throw new Exception("Selected vehicle does not exist.");
                }
                $stmtCheckVehicle->close();

                $stmt = $conn->prepare("INSERT INTO bookings (user_id, source, destination, vehicle_id, booking_date, status, shipment_id) VALUES (?, ?, ?, ?, NOW(), 'pending', NULL)");
                if (!$stmt) {
                    throw new Exception("SQL prepare failed: " . $conn->error);
                }
                $stmt->bind_param("issi", $clientId, $source, $destination, $vehicleId);
                $stmt->execute();
                $bookingId = $conn->insert_id; // Get the ID of the newly created booking
                if ($stmt->affected_rows <= 0) {
                    throw new Exception("Booking insertion failed.");
                }
                $stmt->close();

                // Redirect to create the shipment
                header("Location: create_shipment.php?booking_id=" . $bookingId);
                exit;

            } catch (Exception $e) {
                $error = "Error creating booking: " . $e->getMessage();
                error_log("Booking creation error: " . $e->getMessage());
            } finally {
                if (isset($conn)) {
                    $conn->close();
                }
            }
        }
    }
} catch (Exception $e) {
    $error = "An unexpected error occurred: " . $e->getMessage();
    error_log("Error in create_booking.php: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Booking</title>
    <link rel="stylesheet" href="css/create_booking.css">
</head>
<body>
    <h1>Create New Booking</h1>
    <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="source">Source:</label>
        <input type="text" id="source" name="source" required><br><br>
        <label for="destination">Destination:</label>
        <input type="text" id="destination" name="destination" required><br><br>
        <label for="vehicle_id">Select Vehicle:</label>
        <select id="vehicle_id" name="vehicle_id" required>
            <option value="">Select a vehicle</option>
            <?php foreach ($availableVehicles as $vehicle): ?>
                <option value="<?php echo $vehicle['id']; ?>">
                    <?php echo $vehicle['vehicle_number'] . ' (' . $vehicle['type'] . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>
        <input type="submit" value="Create Booking">
    </form>
</body>
</html>
