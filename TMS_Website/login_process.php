<?php
session_start();
include 'includes/db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    try {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                
                // Direct file paths without subdirectories
                switch($user['role']) {
                    case 'admin':
                        $_SESSION['admin_id'] = $user['id'];
                        header("Location: admin_dashboard.php");
                        break;
                    case 'client':
                        $_SESSION['client_id'] = $user['id'];
                        header("Location: client_dashboard.php");
                        break;
                    case 'driver':
                        $_SESSION['driver_id'] = $user['id'];
                        header("Location: driver_dashboard.php");
                        break;
                    default:
                        throw new Exception("Invalid user role");
                }
                exit();
            } else {
                $_SESSION['error'] = "Invalid password";
            }
        } else {
            $_SESSION['error'] = "User not found";
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
    }
    
    header("Location: auth.php");
    exit();
}
?>
