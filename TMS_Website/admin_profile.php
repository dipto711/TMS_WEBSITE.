<?php
session_start();
include 'includes/db_config.php';

// Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$adminId = $_SESSION['user_id'];
$adminData = null;
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
                } else if ($_FILES["profile_pic"]["size"] > 500000) { // Adjust max size as needed
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

            // Update admin data
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?";
            if ($uploadOk == 1) {
                $sql .= ", profile_pic = ?";
            }
            $sql .= " WHERE id = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            if ($uploadOk == 1) {
                $stmt->bind_param("ssssi", $name, $email, $phone, $targetFile, $adminId);
            } else {
                $stmt->bind_param("sssi", $name, $email, $phone, $adminId);
            }

            if ($stmt->execute()) {
                $successMessage = "Profile updated successfully!";
                $editMode = false;
            } else {
                $errorMessage = "Profile update failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Fetch admin data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $adminData = $result->fetch_assoc();
    } else {
        throw new Exception("Admin not found.");
    }
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $errorMessage = "An error occurred: " . $e->getMessage();
    error_log("Error in admin_profile.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_profile.css">
</head>
<body>
    <div class="container">
        <?php if (!$editMode): ?>
            <!-- Display Mode -->
            <div class="profile-header">
                <div class="profile-image-container">
                    <?php if (isset($adminData['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($adminData['profile_pic']); ?>" alt="Profile Picture" class="profile-pic">
                    <?php else: ?>
                        <img src="images/default-avatar.png" alt="Default Profile" class="profile-pic">
                    <?php endif; ?>
                </div>
                <h1><?php echo htmlspecialchars($adminData['name'] ?? ''); ?></h1>
                <span class="profile-role">
                    <i class="fas fa-shield-alt"></i> Administrator
                </span>
            </div>

            <div class="profile-content">
                <?php if (isset($successMessage)): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errorMessage)): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-user"></i> Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($adminData['name'] ?? ''); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($adminData['email'] ?? ''); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($adminData['phone'] ?? ''); ?></span>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" id="editProfileBtn" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Edit Mode -->
            <div class="profile-header">
                <h1>Edit Profile</h1>
                <span class="profile-role">Update Your Information</span>
            </div>

            <div class="profile-content">
                <form id="updateProfileForm" method="post" enctype="multipart/form-data" class="edit-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($adminData['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($adminData['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($adminData['phone'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="file-input-group">
                            <label for="profile_pic" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Choose a new profile picture
                            </label>
                            <input type="file" id="profile_pic" name="profile_pic" class="file-input" 
                                   accept="image/*">
                        </div>
                        <?php if (isset($adminData['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($adminData['profile_pic']); ?>" 
                                 alt="Current Profile Picture" class="profile-pic preview" width="100">
                        <?php endif; ?>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" id="cancelEditBtn" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/admin_profile.js"></script>
</body>
</html>
