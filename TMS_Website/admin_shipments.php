<?php
session_start();
include 'includes/db_config.php';

//Robust Admin Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Handle adding a new shipment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_shipment'])) {
    $client_id = filter_var($_POST['client_id'], FILTER_VALIDATE_INT);
    $source = mysqli_real_escape_string($conn, $_POST['source']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $vehicle_id = filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT);
    $driver_id = filter_var($_POST['driver_id'], FILTER_VALIDATE_INT);

    if ($client_id === false || $client_id <= 0 || $vehicle_id === false || $vehicle_id <= 0 || $driver_id === false || $driver_id <= 0){
        echo "<p class='error'>Invalid input</p>";
        return;
    }

    $sql = "INSERT INTO shipments (client_id, source, destination, vehicle_id, driver_id, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param("ssssss", $client_id, $source, $destination, $vehicle_id, $driver_id, $status);
        $status = 'pending';
        if ($stmt->execute()) {
            echo "<p class='success'>Shipment added successfully!</p>";
        } else {
            echo "<p class='error'>Error adding shipment: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
}

//Handle updating shipment status (modified to use AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $shipmentId = filter_var($_POST['shipment_id'], FILTER_VALIDATE_INT);
    $newStatus = mysqli_real_escape_string($conn, $_POST['new_status']);

    $errorLog = []; // Array to store error messages

    if ($shipmentId === false || $shipmentId <= 0 || !in_array($newStatus, ['pending', 'picked_up', 'in_transit', 'delivered', 'cancelled'])) {
        $errorLog[] = 'Invalid update request';
    }

    if (empty($errorLog)) { // Proceed only if no errors
        $sql = "UPDATE shipments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $newStatus, $shipmentId);
            if ($stmt->execute()) {
                $result = ['success' => true, 'message' => 'Shipment status updated successfully!', 'newStatus' => $newStatus];
            } else {
                $errorLog[] = 'Error updating shipment status: ' . $stmt->error;
                $result = ['success' => false, 'message' => 'Error updating status'];
            }
            $stmt->close();
        } else {
            $errorLog[] = 'Error preparing statement: ' . $conn->error;
            $result = ['success' => false, 'message' => 'Error preparing statement'];
        }

        // Log the errors for debugging purposes (optional)
        if (!empty($errorLog)) {
            error_log("Error updating shipment: " . json_encode($errorLog));
        }

    } else {
        $result = ['success' => false, 'message' => implode('; ', $errorLog)]; // Send all errors in the message
    }

    echo json_encode($result);  // Send the JSON response
    $conn->close();
    exit;
}

//Fetch shipments
$orderBy = "id";
if (isset($_GET['sort']) && in_array($_GET['sort'], ['client_name', 'source', 'destination', 'status'])) {
    $orderBy = $_GET['sort'];
}

$sql = "SELECT s.*, u.name AS client_name, v.vehicle_number, d.name AS driver_name FROM shipments s 
        JOIN users u ON s.client_id = u.id 
        LEFT JOIN vehicles v ON s.vehicle_id = v.id 
        LEFT JOIN users d ON s.driver_id = d.id ORDER BY $orderBy";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Shipments</title>
    <link rel="stylesheet" href="css/admin_common.css">
    <link rel="stylesheet" href="css/admin_shipments.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">  <!-- AOS CSS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>  <!-- jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>       <!-- AOS JS - MUST BE BEFORE your custom JS -->
    <script src="js/admin_shipments.js" defer></script>                  <!-- Your custom JS -->
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-truck-moving jumping-icon vehicle-icon"></i> Manage Vehicles</h1>
            <button class="add-shipment-btn" onclick="toggleAddShipmentForm()">
                <i class="fas fa-plus"></i> New Shipment
            </button>
        </header>

        <!-- Add Shipment Form -->
        <div class="add-shipment-form" id="addShipmentForm">
            <form method="post" class="animated-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="client_id"><i class="fas fa-user"></i> Client</label>
                        <select name="client_id" required>
                            <option value="">Select Client</option>
                            <?php
                            $client_sql = "SELECT id, name FROM users WHERE role = 'client'";
                            $client_result = $conn->query($client_sql);
                            if ($client_result) {
                                while ($client_row = $client_result->fetch_assoc()) {
                                    echo "<option value='{$client_row['id']}'>{$client_row['name']}</option>";
                                }
                                $client_result->free_result();
                            } else {
                                die("Error fetching clients: " . $conn->error);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="source"><i class="fas fa-map-marker-alt"></i> Source</label>
                        <input type="text" name="source" required placeholder="Enter source location">
                    </div>

                    <div class="form-group">
                        <label for="destination"><i class="fas fa-map-pin"></i> Destination</label>
                        <input type="text" name="destination" required placeholder="Enter destination">
                    </div>

                    <div class="form-group">
                        <label for="vehicle_id"><i class="fas fa-truck"></i> Vehicle</label>
                        <select name="vehicle_id" required>
                            <option value="">Select Vehicle</option>
                            <?php
                            $vehicle_sql = "SELECT id, vehicle_number FROM vehicles";
                            $vehicle_result = $conn->query($vehicle_sql);
                            if ($vehicle_result) {
                                while ($vehicle_row = $vehicle_result->fetch_assoc()) {
                                    echo "<option value='{$vehicle_row['id']}'>{$vehicle_row['vehicle_number']}</option>";
                                }
                                $vehicle_result->free_result();
                            } else {
                                die("Error fetching vehicles: " . $conn->error);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="driver_id"><i class="fas fa-id-card"></i> Driver</label>
                        <select name="driver_id" required>
                            <option value="">Select Driver</option>
                            <?php
                            $driver_sql = "SELECT id, name FROM users WHERE role = 'driver'";
                            $driver_result = $conn->query($driver_sql);
                            if ($driver_result) {
                                while ($driver_row = $driver_result->fetch_assoc()) {
                                    echo "<option value='{$driver_row['id']}'>{$driver_row['name']}</option>";
                                }
                                $driver_result->free_result();
                            } else {
                                die("Error fetching drivers: " . $conn->error);
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_shipment" class="submit-btn"><i class="fas fa-plus"></i> Add Shipment</button>
                <button type="button" class="cancel-btn" onclick="toggleAddShipmentForm()"><i class="fas fa-times"></i> Cancel</button>
            </form>
        </div>

        <!-- Shipments Table -->
         <!-- Shipments Table -->
         <table class="shipments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['source']); ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td><?php echo htmlspecialchars($row['vehicle_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['driver_name'] ?? 'N/A'); ?></td>
                        <td data-shipmentid="<?php echo $row['id']; ?>">
                            <span class="status-badge <?php echo htmlspecialchars($row['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="status-update-container">
                                <select class="status-select" data-shipmentid="<?php echo $row['id']; ?>">
                                    <option value="pending" <?php if ($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="picked_up" <?php if ($row['status'] == 'picked_up') echo 'selected'; ?>>Picked Up</option>
                                    <option value="in_transit" <?php if ($row['status'] == 'in_transit') echo 'selected'; ?>>In Transit</option>
                                    <option value="delivered" <?php if ($row['status'] == 'delivered') echo 'selected'; ?>>Delivered</option>
                                    <option value="cancelled" <?php if ($row['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <span class="update-spinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <a href="admin_dashboard.php" class="button"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
