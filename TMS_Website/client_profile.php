<?php
session_start();
include 'includes/db_config.php';

// Client authorization
if (!isset($_SESSION['client_id'])) {
    header("Location: index.php");
    exit();
}

$clientId = $_SESSION['client_id'];
$clientData = null;
$errorMessage = null;
$successMessage = null;
$editMode = false;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['edit_profile'])) {
            $editMode = true;
        } else if (isset($_POST['update_profile'])) {
            // Sanitize inputs
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
            $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

            $targetDir = "uploads/";
            $targetFile = "";
            $uploadOk = 0;

            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
                $targetFile = $targetDir . basename($_FILES["profile_pic"]["name"]);
                $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

                // Image validation (Improve for production)
                $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
                if ($check === false) {
                    $errorMessage = "Invalid image file.";
                } else if ($_FILES["profile_pic"]["size"] > 500000) {
                    $errorMessage = "File too large.";
                } else if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $errorMessage = "Invalid file type.";
                } else {
                    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
                        $successMessage = "File uploaded successfully!";
                        $uploadOk = 1;
                    } else {
                        $errorMessage = "File upload failed.";
                        error_log("File upload failed: " . error_get_last()['message']);
                    }
                }
            }

            // Update user data
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?";
            if ($uploadOk == 1) {
                $sql .= ", profile_pic = ?";
            }
            $sql .= " WHERE id = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            if ($uploadOk == 1) {
                $stmt->bind_param("sssssi", $name, $email, $phone, $address, $targetFile, $clientId);
            } else {
                $stmt->bind_param("ssssi", $name, $email, $phone, $address, $clientId);
            }

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $successMessage = "Profile updated successfully!";
                    $editMode = false;
                } else {
                    $errorMessage = "No changes made."; //More informative message
                }
            } else {
                $errorMessage = "Profile update failed: " . $stmt->error; //Show specific error
            }
            $stmt->close();

        }
    }

    // Fetch client data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $clientData = $result->fetch_assoc();
    } else {
        throw new Exception("Client not found.");
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
    error_log("Error in client_profile.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Profile</title>
    <link rel="stylesheet" href="css/client_profile.css">
</head>
<body>
    <div class="container">
        <h1>Client Profile</h1>
        <?php if (isset($successMessage)): ?>
            <p class="success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <?php if (isset($errorMessage)): ?>
            <p class="error"><?php echo $errorMessage; ?></p>
        <?php endif; ?>

        <?php if (!$editMode): ?>
            <!-- Display mode -->
            <p><strong>Name:</strong> <?php echo htmlspecialchars($clientData['name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($clientData['email'] ?? ''); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($clientData['phone'] ?? ''); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($clientData['address'] ?? ''); ?></p>
            <?php if (isset($clientData['profile_pic'])): ?>
                <img src="<?php echo htmlspecialchars($clientData['profile_pic']); ?>" alt="Profile Picture" width="100">
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="submit" name="edit_profile" value="Edit Profile">
            </form>
        <?php else: ?>
            <!-- Edit mode -->
            <form method="post" enctype="multipart/form-data">
                <label for="name">Name:</label><br>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($clientData['name'] ?? ''); ?>"><br><br>

                <label for="email">Email:</label><br>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($clientData['email'] ?? ''); ?>"><br><br>

                <label for="phone">Phone:</label><br>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($clientData['phone'] ?? ''); ?>"><br><br>

                <label for="address">Address:</label><br>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($clientData['address'] ?? ''); ?>"><br><br>

                <label for="profile_pic">Profile Picture:</label><br>
                <input type="file" id="profile_pic" name="profile_pic"><br><br>
                <?php if (isset($clientData['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($clientData['profile_pic']); ?>" alt="Profile Picture" width="100">
                <?php endif; ?>

                <input type="submit" name="update_profile" value="Update Profile">
            </form>
        <?php endif; ?>
        <a href="client_dashboard.php" class="button">Back to Dashboard</a>
    </div>
</body>
</html>

