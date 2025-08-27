<?php
session_start();
include 'includes/db_config.php';

// Sanitize and validate input (consider using a function for reusability)
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
$role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

// Driver-specific fields
$license = ($role === 'driver') ? filter_input(INPUT_POST, 'license', FILTER_SANITIZE_STRING) : null;
$vehicleType = ($role === 'driver') ? filter_input(INPUT_POST, 'vehicleType', FILTER_SANITIZE_STRING) : null;
$vehicleNumber = ($role === 'driver') ? filter_input(INPUT_POST, 'vehicle_number', FILTER_SANITIZE_STRING) : null;
$capacity = ($role === 'driver') ? filter_input(INPUT_POST, 'capacity', FILTER_SANITIZE_NUMBER_INT) : null;


$errors = [];
if (!$name || !$email || !$password || !$role) {
    $errors[] = "Please fill in all required fields.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

//Robust Driver Field Validation: Check explicitly for all fields
if ($role === 'driver') {
    if (!$license || !$vehicleType || !$vehicleNumber || !$capacity || $capacity <= 0) {
        $errors[] = "Please fill in all required fields for Driver registration. Capacity must be greater than 0.";
    }
}


if (empty($errors)) {
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Email already registered.");
        }
        $stmt->close();

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // REMOVED status column from the INSERT statement
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"); 
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
        $stmt->execute();
        $userId = $conn->insert_id;
        $stmt->close();

        if ($role === 'driver') {
            $stmt = $conn->prepare("INSERT INTO vehicles (license_number, type, vehicle_number, capacity, status, driver_id) VALUES (?, ?, ?, ?, ?, ?)");
            $status = 'available';
            $stmt->bind_param("ssissi", $license, $vehicleType, $vehicleNumber, $capacity, $status, $userId);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        $_SESSION['success'] = "Registration successful!";
        header("Location: auth.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Database error: " . $e->getMessage();
        $_SESSION['errors'] = $errors;
        header("Location: auth.php");
        exit();
    }
} else {
    $_SESSION['errors'] = $errors;
    header("Location: auth.php");
    exit();
}

$conn->close();
?>