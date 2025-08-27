<?php
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $shipmentId = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : 0;
    $newStatus = isset($_POST['status']) ? filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING) : null;

    //Improved Input Validation
    if (!$shipmentId || $shipmentId <= 0 || !$newStatus) {
        die("Invalid data provided for update.");
    }


    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Prevent unauthorized status changes and only allow 'picked_up' from 'pending'
        $sqlCheck = "SELECT status FROM shipments WHERE id = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("i", $shipmentId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $rowCheck = $resultCheck->fetch_assoc();

        if ($rowCheck === null || ($rowCheck['status'] !== 'pending' && $newStatus != 'picked_up')) {
            die("Invalid status change request.");
        }
        $stmtCheck->close();

        $sql = "UPDATE shipments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        $stmt->bind_param("si", $newStatus, $shipmentId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Status updated successfully! Shipment ID: " . $shipmentId;
            ?>
            <script>
                window.location.href = "driver_dashboard.php"; 
            </script>
            <?php
            exit();
        } else {
            throw new Exception("Error updating status: No rows affected.");
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        error_log("Update Status Error (Shipment ID: " . $shipmentId . ", New Status: " . $newStatus . "): " . $e->getMessage());
        die("An unexpected error occurred. Please contact support."); 
    }
}
?>