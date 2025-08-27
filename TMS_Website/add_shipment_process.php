<?php
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $clientId = $_POST['client_id'];
    $source = $_POST['source'];
    $destination = $_POST['destination'];
    $driverId = isset($_POST['driver_id']) ? $_POST['driver_id'] : null;
    $vehicleId = isset($_POST['vehicle_id']) ? $_POST['vehicle_id'] : null;

    $sql = "INSERT INTO shipments (client_id, source, destination, driver_id, vehicle_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt->bind_param("isiii", $clientId, $source, $destination, $driverId, $vehicleId); // Bind parameters

    if ($stmt->execute()) {
        echo "New shipment added successfully.";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error adding shipment: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
