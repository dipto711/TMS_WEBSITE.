<?php
require_once('vendor/autoload.php'); // Path to your autoloader

\Stripe\Stripe::setApiKey('sk_test_51QVdj1G71oah2ZYt1j1eIVUZKCzqpQ1ETtAfGYtBmtwZMRoezylcrmCyBWjsTJNfwUCIIa1gR8gkUNu3UUJrA2J100OyaHK0J7'); // Your TEST secret key

$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['amount'], $data['shipmentId'])) {
    $amount = $data['amount'];
    $shipmentId = $data['shipmentId'];

    try {
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Stripe uses cents
            'currency' => 'usd',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);


        include 'includes/db_config.php'; //Include your database connection
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        $sql = "INSERT INTO payments (shipment_id, amount, method, transaction_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idss", $shipmentId, $amount, 'stripe', $paymentIntent->id);
        $stmt->execute();
        $stmt->close();
        $conn->close();


        echo json_encode(['success' => true, 'clientSecret' => $paymentIntent->client_secret]);

    } catch (\Stripe\Exception\CardException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
}
?>
