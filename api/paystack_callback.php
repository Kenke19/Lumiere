<?php
session_start();
require_once '../Admin/includes/db.php';     
require_once '../Admin/includes/mailer.php'; 

if (!isset($_GET['reference'])) {
    die('No transaction reference supplied');
}

$reference = $_GET['reference'];
$paystackSecretKey = PAYSTACK_SECRET_KEY;

// Verify transaction with Paystack API
$ch = curl_init("https://api.paystack.co/transaction/verify/$reference");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $paystackSecretKey"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if ($response === false) {
    die('Curl error: ' . curl_error($ch));
}
curl_close($ch);

$result = json_decode($response, true);

if (!$result['status'] || $result['data']['status'] !== 'success') {
    die('Payment verification failed');
}

// Extract metadata and customer info
$cartToken = null;
$billingName = '';
$billingPhone = '';
$billingAddress1 = '';
$billingAddress2 = '';
$billingCity = '';
$billingState = '';
$billingCountry = '';
$billingEmail = $result['data']['customer']['email'] ?? '';

$deliveryType = ''; 
$shippingFee = 0;
$country = '';
$state = '';

if (isset($result['data']['metadata']['custom_fields'])) {
    foreach ($result['data']['metadata']['custom_fields'] as $field) {
        if ($field['variable_name'] === 'delivery_type') {
            $deliveryType = $field['value'];
        } elseif ($field['variable_name'] === 'shipping_fee') {
            $shippingFee = floatval($field['value']);
        }
        switch($field['variable_name']) {
            case 'cart_token':
                $cartToken = $field['value'];
                break;
            case 'name':
                $billingName = $field['value'];
                break;
            case 'phone':
                $billingPhone = $field['value'];
                break;
            case 'address1':
                $billingAddress1 = $field['value'];
                break;
            case 'address2':
                $billingAddress2 = $field['value'];
                break;
            case 'city':
                $billingCity = $field['value'];
                break;
            case 'state':
                $billingState = $field['value'];
                break;
            case 'country':
                $billingCountry = $field['value'];
                break;
        }
    }
}

if (!$cartToken) {
    die('Cart token missing in payment metadata');
}


// Get the logged-in user's id
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    die('User session expired or invalid');
}

// Update users table with latest delivery details
$stmtUpdateUser = $pdo->prepare("
    UPDATE users SET
        name = :name,
        phone = :phone,
        address1 = :address1,
        address2 = :address2,
        city = :city,
        state = :state,
        country = :country
    WHERE id = :id
");
$stmtUpdateUser->execute([
    ':name' => $billingName,
    ':phone' => $billingPhone,
    ':address1' => $billingAddress1,
    ':address2' => $billingAddress2,
    ':city' => $billingCity,
    ':state' => $billingState,
    ':country' => $billingCountry,
    ':id' => $userId
]);


// Find cart ID by cart token
$stmt = $pdo->prepare("SELECT id FROM carts WHERE cart_token = ?");
$stmt->execute([$cartToken]);
$cart = $stmt->fetch();

if (!$cart) {
    die('Cart not found');
}
$cartId = $cart['id'];

// Fetch cart items and prices
$stmt = $pdo->prepare("
    SELECT ci.product_id, ci.quantity, p.price
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cartId]);
$cartItems = $stmt->fetchAll();

if (!$cartItems) {
    die('Cart is empty');
}

// Calculate order total
$orderAmount = 0;
foreach ($cartItems as $item) {
    $orderAmount += $item['price'] * $item['quantity'];
}

// Insert order into database with status 'processing'
$stmtOrder = $pdo->prepare("INSERT INTO orders 
  (customer_id, order_date, amount, status, total, user_id, billing_name, billing_phone, billing_address, billing_email, paystack_reference, payment_status, delivery_type, shipping_fee) 
  VALUES (?, CURDATE(), ?, 'processing', ?, ?, ?, ?, ?, ?, ?,  'success', ?, ?)");
$stmtOrder->execute([
    null,
    $orderAmount,
    $orderAmount + $shippingFee,
    $userId,
    $billingName,
    $billingPhone,
    $billingAddress,
    $billingEmail,
    $reference,
    $deliveryType,
    $shippingFee
]);
$orderId = $pdo->lastInsertId();

// Insert order items
$stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cartItems as $item) {
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmtStock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
    if ($stmtStock->rowCount() === 0) {
        die('Insufficient stock for product ID ' . $item['product_id']);
    }
    
    $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
}

// Clear cart items and cookie
$stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
$stmt->execute([$cartId]);

setcookie('cart_token', '', time() - 3600, '/');
// Send order confirmation email
$totalWithShipping = $orderAmount + $shippingFee;
$orderItemsHtml = "<ul>";
foreach ($cartItems as $item) {
    $orderItemsHtml .= "<li>" . htmlspecialchars($item['name']) . " &times; " . intval($item['quantity']) . "</li>";
}
$orderItemsHtml .= "</ul>";

$subject = "Order Confirmation - Lumière Order #$orderId";
$htmlBody = "
    <div style=\"font-family: Arial, sans-serif; color: #222; max-width: 600px; margin: auto;\">
        <h2 style=\"color: #1a365d;\">Thank you for shopping with Lumière!</h2>
        <p>Dear <strong>" . htmlspecialchars($billingName) . "</strong>,</p>

        <p>
            We are pleased to confirm your order <strong>#$orderId</strong> placed on <strong>" . date('jS F, Y') . "</strong>.
            Your order is currently being processed and you’ll receive a notification when it ships.
        </p>
        <h3 style=\"color: #1a365d; margin-top: 24px;\">Order Summary</h3>
        <table cellspacing=\"0\" cellpadding=\"8\" border=\"0\" style=\"background: #f8f9fb; border-radius: 6px;\">
            <tr>
                <td><strong>Order Number:</strong></td>
                <td>#$orderId</td>
            </tr>
            <tr>
                <td><strong>Total Amount:</strong></td>
                <td>₦" . number_format($totalWithShipping, 2) . "</td>
            </tr>
            <tr>
                <td><strong>Delivery Type:</strong></td>
                <td>" . ($deliveryType === "pickup" ? "Store Pickup" : "Door Delivery") . "</td>
            </tr>
        </table>

        <h3 style=\"color: #1a365d; margin-top: 24px;\">Delivery Information</h3>
        <p>
            <strong>Name:</strong> " . htmlspecialchars($billingName) . "<br/>
            <strong>Phone:</strong> " . htmlspecialchars($billingPhone) . "<br/>
            <strong>Address:</strong> " . htmlspecialchars($billingAddress) . "<br/>
            <strong>Email:</strong> " . htmlspecialchars($billingEmail) . "
        </p>

        <h3 style=\"color: #1a365d; margin-top: 24px;\">What Happens Next?</h3>
        <ul>
            <li>You’ll receive updates as soon as your order is shipped.</li>
            <li>If you chose <b>Store Pickup</b>, you’ll be notified as soon as your order is ready for collection at our store.</li>
            <li>If you chose <b>Door Delivery</b>, our courier will deliver to the address above.</li>
            <li>For questions or changes to your order, simply reply to this email or contact us using the information below.</li>
        </ul>

        <p style=\"margin-top: 24px;\">
            <strong>Lumière Customer Support</strong><br>
            📨 <a href=\"mailto:support@lumiere.com\">support@lumiere.com</a> &nbsp; | &nbsp; ☎️ +234 123 4567<br>
            <span style=\"color: #778\">Follow us: <a href=\"#\">Instagram</a> | <a href=\"#\">Facebook</a></span>
        </p>

        <hr style=\"margin: 32px 0; color: #eee;\">
        <p style=\"font-size:13px; color: #677;\">Thank you again for choosing Lumière. We look forward to delighting you!</p>
    </div>
";
$plainBody = strip_tags(str_replace(['<br>','<br/>','<br />','</li>'],$orderItemsLineBreak, $htmlBody));


sendEmail($billingEmail, $billingName, $subject, $htmlBody, $plainBody);

// Redirect to success page
header('Location: ../order-success.php?order_id=' . $orderId);
exit;