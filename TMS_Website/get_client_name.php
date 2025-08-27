<?php
include 'includes/db_config.php';

$clientId = isset($_GET['id']) ? $_GET['id'] : null; //Improved input handling

if ($clientId === null) {
    echo json_encode(['success' => false, 'error' => 'Invalid client ID']);
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$sql = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
  exit;
}
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($row) {
    echo json_encode(['success' => true, 'name' => $row['name']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Client not found']);
}
?>
