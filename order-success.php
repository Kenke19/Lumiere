<?php


$orderId = $_GET['order_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Order Success - ShopEase</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet" />
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
        <h1 class="text-3xl font-bold mb-4 text-green-600">Thank You for Your Order!</h1>
        <?php if ($orderId): ?>
            <p class="mb-6 text-gray-700">Your order ID is <strong>#<?= htmlspecialchars($orderId) ?></strong>.</p>
        <?php else: ?>
            <p class="mb-6 text-gray-700">Your order has been placed successfully.</p>
        <?php endif; ?>
        <a href="index.php" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded hover:bg-indigo-700 transition">
            Back to Home
        </a>
    </div>
</body>
</html>
