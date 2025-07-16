<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$orderId = (int)$_GET['id'];

// Fetch order and user info
$stmt = $pdo->prepare("
  SELECT o.*, u.name AS customer_name, u.email AS customer_email, o.billing_name, o.billing_phone, o.billing_address
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.id
  WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Fetch order items
$stmt = $pdo->prepare("
  SELECT oi.*, p.name AS product_name, p.image
  FROM order_items oi
  LEFT JOIN products p ON oi.product_id = p.id
  WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Calculate subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #e2e8f0 transparent;
        }
        .scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 6px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-menu {
            animation: fadeIn 0.2s ease-out;
        }
        .timeline-item:not(:last-child):after {
            content: '';
            position: absolute;
            left: 7px;
            top: 24px;
            height: calc(100% - 24px);
            width: 1px;
            background-color: #e2e8f0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:pl-72 overflow-auto">
        <div class="max-w-7xl mx-auto">
            <!-- Header with Back Button -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <a href="orders.php" class="flex items-center text-gray-600 hover:text-gray-900">
                        <i class="fas fa-chevron-left mr-2"></i> Back to orders
                    </a>
                    <h2 class="text-2xl font-bold text-gray-900 mt-2">Order #<?= $order['id'] ?></h2>
                </div>
                <div class="flex space-x-3">
                    <button onclick="window.print()" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        <i class="fas fa-file-export mr-2"></i> Export
                    </button>
                </div>
            </div>

            <!-- Order Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Order Status Card -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Order Status</p>
                            <p class="text-lg font-semibold mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?= $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                        ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800' : 
                                        ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Card -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Payment Status</p>
                            <p class="text-lg font-semibold mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                    <?= ucfirst($order['payment_status'] ?? 'Paid') ?>
                                </span>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-green-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Delivery Method Card -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Delivery Method</p>
                            <p class="text-lg font-semibold mt-1"><?= htmlspecialchars($order['delivery_type'] ?? 'N/A') ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                            <i class="fas fa-truck text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Date Card -->
                <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Date</p>
                            <p class="text-lg font-semibold mt-1"><?= date('M d, Y', strtotime($order['order_date'])) ?></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-purple-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2/3 width) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Order Items Card -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Order Items</h3>
                        </div>
                        <div class="overflow-x-auto scrollbar">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if (!empty($item['image'])): ?>
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded" src="/E-shop/<?= htmlspecialchars($item['image']) ?>" alt="">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['product_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?= number_format($item['price'], 2) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $item['quantity'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-500">Subtotal</span>
                                <span class="text-sm font-medium text-gray-900">$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-sm font-medium text-gray-500">Shipping</span>
                                <span class="text-sm font-medium text-gray-900">$<?= number_format($order['shipping_fee'] ?? 0, 2) ?></span>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-sm font-medium text-gray-500">Tax</span>
                                <span class="text-sm font-medium text-gray-900">$<?= number_format($order['tax'] ?? 0, 2) ?></span>
                            </div>
                            <div class="flex justify-between mt-4 pt-4 border-t border-gray-200">
                                <span class="text-base font-bold text-gray-900">Total</span>
                                <span class="text-base font-bold text-gray-900">$<?= number_format($order['total'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information Card -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Shipping Information</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Shipping Address</h4>
                                    <address class="mt-2 text-sm not-italic text-gray-700">
                                        <?= htmlspecialchars($order['shipping_name'] ?? $order['billing_name']) ?><br>
                                        <?= nl2br(htmlspecialchars($order['shipping_address'] ?? $order['billing_address'])) ?><br>
                                        <?= htmlspecialchars($order['shipping_phone'] ?? $order['billing_phone']) ?>
                                    </address>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Shipping Method</h4>
                                    <p class="mt-2 text-sm text-gray-700"><?= htmlspecialchars($order['shipping_method'] ?? 'N/A') ?></p>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        <p class="mt-1 text-sm text-gray-700">Tracking #: <?= htmlspecialchars($order['tracking_number']) ?></p>
                                        <button class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-500">
                                            Track Package
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information Card -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Billing Information</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Billing Address</h4>
                                    <address class="mt-2 text-sm not-italic text-gray-700">
                                        <?= htmlspecialchars($order['billing_name']) ?><br>
                                        <?= nl2br(htmlspecialchars($order['billing_address'])) ?><br>
                                        <?= htmlspecialchars($order['billing_phone']) ?>
                                    </address>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Payment Method</h4>
                                    <div class="mt-2 flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fab fa-cc-visa text-gray-500"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-700"><?= htmlspecialchars($order['payment_method'] ?? 'Card') ?></p>
                                            <?php if (!empty($order['card_last4'])): ?>
                                                <p class="text-sm text-gray-500">**** <?= htmlspecialchars($order['card_last4']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-700">Billed on <?= date('M d, Y', strtotime($order['order_date'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3 width) -->
                <div class="space-y-6">
                    <!-- Customer Card -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Customer</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-lg font-medium text-gray-600">
                                        <?= strtoupper(substr($order['customer_name'] ?? 'G', 0, 2)) ?>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></h4>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($order['customer_email'] ?? '') ?></p>
                                    <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($order['billing_phone'] ?? '') ?></p>
                                </div>
                            </div>
                            <!-- You can add "customer since" if you have that info -->
                        </div>
                    </div>

                    <!-- Order Notes Card -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Order Notes</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="text-sm text-gray-700">
                                <?= nl2br(htmlspecialchars($order['notes'] ?? '')) ?>
                            </div>
                            <!-- Add note form could go here -->
                        </div>
                    </div>

                    <!-- Order Timeline Card (static example, make dynamic if you store status history) -->
                    <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Order Timeline</h3>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flow-root">
                                <ul class="-mb-8">
                                    <?php
                                    // Example: you can make this dynamic if you store order status history
                                    $timeline = [];
                                    if ($order['status'] === 'delivered') {
                                        $timeline[] = ['icon' => 'check', 'color' => 'green', 'label' => 'Order delivered', 'date' => $order['delivered_at'] ?? $order['order_date']];
                                    }
                                    if ($order['status'] === 'shipped' || $order['status'] === 'delivered') {
                                        $timeline[] = ['icon' => 'truck', 'color' => 'blue', 'label' => 'Order shipped', 'date' => $order['shipped_at'] ?? $order['order_date']];
                                    }
                                    if ($order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered') {
                                        $timeline[] = ['icon' => 'shopping-bag', 'color' => 'yellow', 'label' => 'Order processed', 'date' => $order['processed_at'] ?? $order['order_date']];
                                    }
                                    $timeline[] = ['icon' => 'money-bill-wave', 'color' => 'green', 'label' => 'Payment received', 'date' => $order['order_date']];
                                    foreach ($timeline as $i => $event):
                                    ?>
                                    <li class="relative pb-8 timeline-item">
                                        <div class="relative flex items-start space-x-3">
                                            <div class="relative">
                                                <div class="h-8 w-8 bg-<?= $event['color'] ?>-100 rounded-full flex items-center justify-center ring-8 ring-white">
                                                    <i class="fas fa-<?= $event['icon'] ?> text-<?= $event['color'] ?>-500 text-xs"></i>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5">
                                                <div class="flex justify-between">
                                                    <div>
                                                        <p class="text-sm text-gray-700"><?= $event['label'] ?></p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time datetime="<?= htmlspecialchars($event['date']) ?>">
                                                            <?= date('M d, Y', strtotime($event['date'])) ?>
                                                        </time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
    <!-- End Main Content -->
    

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
