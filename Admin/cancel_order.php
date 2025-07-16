<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();
require 'includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    header('Location: orders.php');
    exit();
}

$orderId = (int)$_POST['order_id'];

$stmt = $pdo->prepare("SELECT paystack_reference, payment_status, billing_email, billing_name, status FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

if ($order['status'] === 'cancelled') {
    header('Location: orders.php');
    exit();
}

$secretKey = PAYSTACK_SECRET_KEY;
$reference = $order['paystack_reference'];

if ($order['payment_status'] === 'success' && $reference) {
    // Refund via Paystack API
    $ch = curl_init("https://api.paystack.co/refund");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "transaction" => $reference,
        "amount" => null // full refund
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!$result['status']) {
        header('Location: orders.php?error=Refund failed: ' . urlencode($result['message']));
        exit();
    }

    // Update payment status to refunded
    $stmtUpdate = $pdo->prepare("UPDATE orders SET payment_status = 'refunded' WHERE id = ?");
    $stmtUpdate->execute([$orderId]);
}

// Update order status to cancelled
$stmtCancel = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
$stmtCancel->execute([$orderId]);

// Send cancellation email
$subject = "Order #$orderId Cancelled and Refunded";
$htmlBody = "<p>Dear " . htmlspecialchars($order['billing_name']) . ",</p><p>Your order #$orderId has been cancelled and refunded.</p><p>If you have any questions, please contact support.</p>";
$plainBody = strip_tags($htmlBody);

sendEmail($order['billing_email'], $order['billing_name'], $subject, $htmlBody, $plainBody);

header('Location: orders.php?msg=Order cancelled and refunded successfully');
exit();
