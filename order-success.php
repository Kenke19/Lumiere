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
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full text-center mx-auto">
        <!-- Big green checkmark -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
            <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold mb-4 text-green-600">Thank You for Your Order!</h1>
        
        <?php if ($orderId): ?>
            <p class="mb-6 text-gray-700">Your order ID is <span class="font-semibold text-gray-900">#<?= htmlspecialchars($orderId) ?></span></p>
        <?php else: ?>
            <p class="mb-6 text-gray-700">Your order has been placed successfully.</p>
        <?php endif; ?>
        
        <div class="mt-8">
            <a href="index.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-3 rounded-lg transition duration-200 transform hover:-translate-y-0.5 shadow-md hover:shadow-lg">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
