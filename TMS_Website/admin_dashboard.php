<?php
// Start the session
session_start();
include 'includes/db_config.php';

// Check admin authorization
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

// Fetch statistics
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Fetch total shipments
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_shipments FROM shipments");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_shipments = $result->fetch_assoc()['total_shipments'];

    // Fetch total vehicles
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_vehicles FROM vehicles");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_vehicles = $result->fetch_assoc()['total_vehicles'];

    // Fetch total users
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_users = $result->fetch_assoc()['total_users'];

    // Fetch pending shipments
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending_shipments FROM shipments WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_shipments = $result->fetch_assoc()['pending_shipments'];

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Error in admin_dashboard.php: " . $e->getMessage());
    die("An unexpected error occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/admin_dashboard.js" defer></script>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <p>Welcome back, Admin!</p>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="dashboard-summary">
                <div class="summary-grid">
                    <div class="stat-card" data-stat="shipments">
                        <i class="fas fa-shipping-fast stat-icon"></i>
                        <div class="stat-number" data-target="<?php echo $total_shipments; ?>"><?php echo $total_shipments; ?></div>
                        <div class="stat-label">Total Shipments</div>
                    </div>
                    <div class="stat-card" data-stat="vehicles">
                        <i class="fas fa-truck stat-icon"></i>
                        <div class="stat-number" data-target="<?php echo $total_vehicles; ?>"><?php echo $total_vehicles; ?></div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    <div class="stat-card" data-stat="users">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-number" data-target="<?php echo $total_users; ?>"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card" data-stat="pending">
                        <i class="fas fa-clock stat-icon"></i>
                        <div class="stat-number" data-target="<?php echo $pending_shipments; ?>"><?php echo $pending_shipments; ?></div>
                        <div class="stat-label">Pending Shipments</div>
                    </div>
                </div>
            </section>

            <section class="dashboard-actions">
                <div class="actions-grid">
                    <a href="admin_shipments.php" class="action-item">
                        <i class="fas fa-boxes"></i> Manage Shipments
                    </a>
                    <a href="admin_vehicles.php" class="action-item">
                        <i class="fas fa-truck"></i> Manage Vehicles
                    </a>
                    <a href="admin_users.php" class="action-item">
                        <i class="fas fa-users-cog"></i> Manage Users
                    </a>
                    <a href="admin_reports.php" class="action-item">
                        <i class="fas fa-chart-line"></i> Generate Reports
                    </a>
                    <a href="admin_bookings.php" class="action-item">
                        <i class="fas fa-book"></i> Manage Bookings
                    </a>
                    <a href="admin_profile.php" class="action-item">
                        <i class="fas fa-user-cog"></i> Profile
                    </a>
                    <a href="logout.php" class="action-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
