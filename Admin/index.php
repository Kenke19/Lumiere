<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

// Fetch your stats as before
$stmtTotals = $pdo->query("
    SELECT 
        SUM(total) AS totalSales,
        COUNT(*) AS totalOrders,
        (SELECT COUNT(*) FROM users) AS totalCustomers
    FROM orders
");
$totals = $stmtTotals->fetch(PDO::FETCH_ASSOC);

$totalSales = $totals['totalSales'] ?: 0;
$totalOrders = $totals['totalOrders'] ?: 0;
$totalCustomers = $totals['totalCustomers'] ?: 0;

$stmtNewOrders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
$stmtNewOrders->execute();
$totalNewOrders = $stmtNewOrders->fetchColumn();

$stmtOnHold = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'shipped'");
$stmtOnHold->execute();
$totalOnHoldOrders = $stmtOnHold->fetchColumn();

$stmtOutOfStock = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock <= 0");
$stmtOutOfStock->execute();
$totalOutOfStock = $stmtOutOfStock->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumiere | Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }
    .card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .progress-bar {
      height: 6px;
      border-radius: 3px;
    }
    .divider {
      height: 1px;
      background-color: #e2e8f0;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen flex flex-col">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-6 md:pl-72 overflow-auto">
      <div class="mb-6">
        <p class="text-gray-500">Here's what's going on at your business right now</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500">New orders</p>
              <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars($totalNewOrders) ?></h3>
              <p class="text-xs text-gray-500 mt-2">Awaiting processing</p>
            </div>
            <div class="p-2 rounded-lg bg-blue-50 text-blue-500">
              <i class="fas fa-shopping-cart"></i>
            </div>
          </div>
        </div>
        <div class="card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500">Orders</p>
              <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars($totalOnHoldOrders) ?></h3>
              <p class="text-xs text-gray-500 mt-2">In Transit</p>
            </div>
            <div class="p-2 rounded-lg bg-yellow-50 text-yellow-500">
              <i class="fas fa-pause"></i>
            </div>
          </div>
        </div>
        <div class="card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500">Products</p>
              <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars($totalOutOfStock) ?></h3>
              <p class="text-xs text-gray-500 mt-2">Out of stock</p>
            </div>
            <div class="p-2 rounded-lg bg-red-50 text-red-500">
              <i class="fas fa-box-open"></i>
            </div>
          </div>
        </div>
        <div class="card p-5">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500">Total sales</p>
              <h3 class="text-2xl font-bold mt-1">$<?= number_format($totalSales, 2) ?></h3>
              <p class="text-xs text-gray-500 mt-2">Feb 1 - 31, 2022</p>
            </div>
            <div class="p-2 rounded-lg bg-green-50 text-green-500">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
        </div>
      </div>

      <hr />

      <!-- Full-width Total Sales Chart -->
      <div class="card p-5 mt-4 mb-6 bg-white rounded-xl shadow-md w-full max-w-full">
        <h3 class="font-semibold text-gray-800 mb-4 text-lg sm:text-xl">Sales Over Time</h3>
        <p class="text-sm text-gray-500 mb-6">Payment received across all channels</p>
        <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
          <canvas id="lineChart" class="w-full h-64"></canvas>
        </div>
        <div class="mt-6 flex gap-3">
          <button class="chart-btn active px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm transition hover:bg-indigo-700" data-period="daily" data-chart="lineChart">Day</button>
          <button class="chart-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm transition hover:bg-gray-300" data-period="weekly" data-chart="lineChart">Week</button>
          <button class="chart-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 text-sm transition hover:bg-gray-300" data-period="monthly" data-chart="lineChart">Month</button>
        </div>
      </div>

      <!-- Second Row: Responsive grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6 mb-8 w-full max-w-full">
        <div class="card p-5">
          <h3 class="text-xl font-semibold text-gray-900 mb-6">Customer Growth</h3>
          <canvas id="areaChart" height="160" class="w-full"></canvas>
        </div>

        <div class="card p-5">
          <h3 class="text-xl font-semibold text-gray-900 mb-6">Orders by Category</h3>
          <canvas id="donutChart" height="160" class="w-full"></canvas>
        </div>

        <div class="card p-5">
          <h3 class="text-xl font-semibold text-gray-900 mb-6">Top Selling Products</h3>
          <canvas id="barChart" height="160" class="w-full"></canvas>
        </div>
      </div>
    </main>
  </div>
    <script src="assets/admin.js"></script>
</body>
</html>
