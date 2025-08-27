<?php
session_start();
include 'includes/db_config.php';

//Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);

    $allowedRoles = ['client', 'driver'];
    if ($user_id === false || $user_id <= 0 || !in_array($new_role, $allowedRoles)) {
        echo "<p class='error'>Invalid input</p>";
        return;
    }

    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if($stmt){
        $stmt->bind_param("si", $new_role, $user_id);
        if ($stmt->execute()) {
            echo "<p class='success'>User role updated successfully!</p>";
        } else {
            echo "<p class='error'>Error updating user role: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
}

$sql = "SELECT * FROM users WHERE role != 'admin'";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Users</title>
    <link rel="stylesheet" href="css/admin_users.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/admin_users.js" defer></script>
</head>
<body>
    <div class="container">
    <header>
        <h2><i class="fas fa-users"></i> Manage Users</h2>
    </header>
        <div class="table-container" data-aos="fade-up">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Update Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <form method="post">
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td>
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <select name="new_role">
                                        <option value="client" <?php if ($row['role'] == 'client') echo 'selected'; ?>>Client</option>
                                        <option value="driver" <?php if ($row['role'] == 'driver') echo 'selected'; ?>>Driver</option>
                                    </select>
                                </td>
                                <td><input type="submit" name="update_role" value="Update"></td>
                            </tr>
                        </form>
                    <?php } ?>
                </tbody>
            </table>
        <a href="admin_dashboard.php" class="button">Back to Dashboard</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>
