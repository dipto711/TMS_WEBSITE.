<?php
session_start();
include 'includes/db_config.php';

// Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$errorMessage = null;
$vehicles = [];

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Add Vehicle
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vehicle'])) {
        // Input validation and sanitization
        $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $capacity = (int)filter_var($_POST['capacity'], FILTER_VALIDATE_INT);
        $license_number = mysqli_real_escape_string($conn, $_POST['license_number']);
        $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'available';

        // Check for duplicate vehicle number
        $stmtCheck = $conn->prepare("SELECT 1 FROM vehicles WHERE vehicle_number = ?");
        $stmtCheck->bind_param("s", $vehicle_number);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            $stmtCheck->close();
            throw new Exception("A vehicle with that number already exists.");
        }
        $stmtCheck->close();

        if ($capacity === false || $capacity <= 0) {
            throw new Exception("Invalid capacity.");
        }

        $stmt = $conn->prepare("INSERT INTO vehicles (vehicle_number, type, capacity, license_number, status) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssss", $vehicle_number, $type, $capacity, $license_number, $status);
        if (!$stmt->execute()) {
            throw new Exception("SQL execution failed: " . $stmt->error);
        }
        $stmt->close();
        echo "<p class='success'>Vehicle added successfully!</p>";
    }

    // Update Vehicle
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_vehicle'])) {
        $vehicle_id = filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT);
        $status = mysqli_real_escape_string($conn, $_POST['status']); 

        if ($vehicle_id === false || $vehicle_id <= 0) {
            throw new Exception("Invalid vehicle ID.");
        }

        $stmt = $conn->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $vehicle_id);
        if (!$stmt->execute()) {
            throw new Exception("Error updating vehicle: " . $stmt->error);
        }
        $stmt->close();
        echo "<p class='success'>Vehicle updated successfully.</p>";
    }

    // Delete Vehicle
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vehicle'])) {
        $vehicle_id = filter_var($_POST['vehicle_id'], FILTER_VALIDATE_INT);
        if ($vehicle_id === false || $vehicle_id <= 0) {
            throw new Exception("Invalid vehicle ID.");
        }

        // Check if the vehicle is used in any shipments
        $stmtCheck = $conn->prepare("SELECT 1 FROM shipments WHERE vehicle_id = ?");
        $stmtCheck->bind_param("i", $vehicle_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            $stmtCheck->close();
            throw new Exception("Cannot delete this vehicle. It is currently associated with one or more shipments.");
        }
        $stmtCheck->close();

        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $vehicle_id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting vehicle: " . $stmt->error);
        }
        $stmt->close();
        echo "<p class='success'>Vehicle deleted successfully!</p>";
    }

    // Select Vehicles
    $vehicles = getVehicles($conn);

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("Error in admin_vehicles.php: " . $errorMessage);
} finally {
    if ($conn) {
        $conn->close();
    }
}

function getVehicles($conn) {
    $vehicles = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM vehicles");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Error fetching vehicles: " . $stmt->error);
        }
        $vehicles = $result->fetch_all(MYSQLI_ASSOC);
        $result->free_result();
        $stmt->close();
    } catch (Exception $e) {
        throw new Exception("Error fetching vehicles: " . $e->getMessage());
    }
    return $vehicles;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Vehicles</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_vehicles.css">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/admin_vehicles.js" defer></script>
</head>

<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-truck-moving jumping-icon vehicle-icon"></i> Manage Vehicles</h1>
            <button class="add-vehicle-btn" onclick="toggleAddVehicleForm()">
                <i class="fas fa-plus"></i> New Vehicle
            </button>
        </header>

        <?php if ($errorMessage): ?>
            <div class="error-message" data-aos="fade-up">
                <i class="fas fa-exclamation-circle"></i>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <div class="add-vehicle-form" id="addVehicleForm" data-aos="fade-down">
            <form method="post" class="animated-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Vehicle Number</label>
                        <input type="text" name="vehicle_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-truck-monster"></i> Vehicle Type</label>
                        <input type="text" name="type" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-weight"></i> Capacity (tons)</label>
                        <input type="number" name="capacity" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> License Number</label>
                        <input type="text" name="license_number">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-info-circle"></i> Status</label>
                        <select name="status">
                            <option value="available">Available</option>
                            <option value="in_transit">In Transit</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_vehicle" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </button>
                    <button type="button" class="cancel-btn" onclick="toggleAddVehicleForm()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>

        <div class="table-container" data-aos="fade-up">
        <table class="vehicles-table">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Vehicle Number</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>License Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$vehicles): ?>
                    <tr>
                        <td colspan="7" class="no-data">No vehicles found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr data-aos="fade-up">
                            <td><?php echo $vehicle['id']; ?></td>
                            <td><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['capacity']); ?> tons</td>
                            <td><?php echo htmlspecialchars($vehicle['license_number']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $vehicle['status']; ?>">
                                    <?php echo ucfirst(htmlspecialchars($vehicle['status'])); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <form method="post" class="update-form">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                        <option value="available" <?php echo $vehicle['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="in_transit" <?php echo $vehicle['status'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                        <option value="maintenance" <?php echo $vehicle['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                    <input type="hidden" name="update_vehicle" value="1">
                                </form>
                                <form method="post" class="delete-form">
                                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                    <button type="submit" name="delete_vehicle" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="admin_dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>
</body>
</html>
