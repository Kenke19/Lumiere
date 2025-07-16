<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();
require 'includes/mailer.php';
require 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    header('Location: orders.php');
    exit();
}

$orderId = (int)$_POST['order_id'];

$stmt = $pdo->prepare("
    SELECT o.paystack_reference, o.payment_status, o.status, u.email AS customer_email, u.name AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");

$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

if (!$order['paystack_reference']) {
    die('No Paystack reference found for this order');
}

$paymentData = verifyPaystackPayment($order['paystack_reference']);

if ($paymentData) {
    // Update payment status to success
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'success' WHERE id = ?");
    $stmt->execute([$orderId]);

    // Update order status to processing if currently pending
    if ($order['status'] === 'pending') {
        $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $stmtUpdate->execute([$orderId]);
    }

    // Send confirmation email
    $subject = "Payment Verified - Order #$orderId";
    $htmlBody = "<p>Dear " . htmlspecialchars($order['billing_name']) . ",</p><p>Your payment for order #$orderId has been successfully verified.</p><p>Thank you for shopping with us.</p>";
    $plainBody = strip_tags($htmlBody);

    sendEmail($order['customer_email'], $order['customer_name'], $subject, $htmlBody, $plainBody);

    header('Location: orders.php?msg=Payment verified successfully');
} else {
    header('Location: orders.php?error=Payment verification failed');
}
exit();
