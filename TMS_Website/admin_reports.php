<?php
session_start();
include 'includes/db_config.php';

// Robust Admin Authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$whereClause = "";

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];

    $sql = "SELECT status, COUNT(*) AS count FROM shipments WHERE shipment_date BETWEEN ? AND ? GROUP BY status";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        die("Error preparing statement: " . $conn->error);
    }

} else {
    $sql = "SELECT status, COUNT(*) AS count FROM shipments GROUP BY status";
    $result = $conn->query($sql);
    if ($result === false) {
        die("Error executing query: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_reports.css">
    <script src="js/admin_reports.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-chart-bar"></i> Shipment Status Report</h2>
        </header>

        <form method="get">
            Start Date: <input type="date" name="start_date"><br>
            End Date: <input type="date" name="end_date"><br>
            <input type="submit" value="Filter">
        </form>

        <div class="table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <a href="admin_dashboard.php" class="button">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
$conn->close();
?>
