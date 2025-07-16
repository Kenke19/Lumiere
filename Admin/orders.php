<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();
require 'includes/mailer.php';

// Define allowed status flow
$statusFlow = [
    'pending' => ['pending', 'processing', 'cancelled'],
    'processing' => ['processing', 'shipped', 'cancelled'],
    'shipped' => ['shipped', 'delivered', 'cancelled'],
    'delivered' => ['delivered'],
    'cancelled' => ['cancelled']
];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    // Fetch current status from DB
    $stmtCurrent = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmtCurrent->execute([$orderId]);
    $orderCurrent = $stmtCurrent->fetch();

    if (!$orderCurrent) {
        die('Order not found.');
    }

    $currentStatus = $orderCurrent['status'];
    $allowedNextStatuses = $statusFlow[$currentStatus] ?? [$currentStatus];

    // Server-side validation of status transition
    if (!in_array($newStatus, $allowedNextStatuses)) {
        die('Invalid status transition.');
    }

    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // Fetch customer email and name for this order
    $stmtUser = $pdo->prepare("SELECT u.email, u.name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmtUser->execute([$orderId]);
    $user = $stmtUser->fetch();

    if ($user) {
        $toEmail = $user['email'];
        $toName = $user['name'];

        // Prepare email subject and body based on status
        $subject = "Your Order #$orderId Status Update";
        $statusFriendly = ucfirst($newStatus);

        $htmlBody = "<p>Dear " . htmlspecialchars($toName) . ",</p>";
        $htmlBody .= "<p>Your order <strong>#$orderId</strong> status has been updated to <strong>$statusFriendly</strong>.</p>";

        switch ($newStatus) {
            case 'processing':
                $htmlBody .= "<p>We are currently processing your order. We will notify you once it ships.</p>";
                break;
            case 'shipped':
                $htmlBody .= "<p>Your order has been shipped! You should receive it soon.</p>";
                break;
            case 'delivered':
                $htmlBody .= "<p>Your order has been delivered. We hope you enjoy your purchase!</p>";
                break;
            case 'cancelled':
                $htmlBody .= "<p>Your order has been cancelled. If you have any questions, please contact support.</p>";
                break;
            default:
                $htmlBody .= "<p>Status updated to $statusFriendly.</p>";
        }

        $htmlBody .= "<p>Thank you for shopping with us.</p>";
        $plainBody = strip_tags($htmlBody);

        // Send the email
        $sent = sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);
        if (!$sent) {
            error_log("Failed to send status update email to $toEmail for order #$orderId");
        }
    }

    header('Location: orders.php');
    exit();
}
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$totalOrders = $stmt->fetchColumn();

// Fetch orders with user info
$stmt = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC");
$orders = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumiere | Orders Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
    }
    .card {
      background: white;
      border-radius: 0.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .inline-form select {
      padding: 0.25rem 0.5rem;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      background-color: white;
      cursor: pointer;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th, td {
      padding: 0.75rem 1rem;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }
    th {
      background-color: #f9fafb;
      font-weight: 600;
      font-size: 0.875rem;
      text-transform: uppercase;
      color: #6b7280;
    }
    .table-btn {
      color: #2563eb;
      cursor: pointer;
      margin-left: 0.5rem;
      font-size: 1.25rem;
    }
    .table-btn:hover {
      color: #1d4ed8;
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
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Orders Management</h2>
                <div class="text-gray-600 font-semibold bg-gray-100 px-4 py-2 mt-4 rounded">
                  Total Orders: <?= htmlspecialchars($totalOrders) ?>
                </div>
            </div>

            <!-- Filter Buttons and Add Product -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex flex-wrap items-center gap-3" id="filter-buttons">
                        <button class="px-3 py-1.5 rounded-lg bg-phoenix-primary text-blue text-grey-700 font-medium hover:bg-gray-50 filter-btn" data-filter="all">
                            <i class="fas fa-filter text-xs mr-1"></i> All Orders
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-white border border-green-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="shipped">
                            <i class="fas fa-plane text-xs mr-1"></i> Shipped
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-white border border-blue-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="delivered">
                            <i class="fas fa-box text-xs mr-1"></i> Delivered
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-white border border-red-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="cancelled">
                            <i class="fas fa-ban text-xs mr-1"></i> Cancelled
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-white border border-yellow-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="pending">
                            <i class="fas fa-hourglass-half text-xs mr-1"></i> Pending
                        </button>
                    </div>
                </div>

                <!-- Search and Export -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <div class="relative flex-grow max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="search-orders" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-phoenix-primary focus:border-phoenix-primary" placeholder="Search orders ...">
                    </div>
                    <div class="ml-auto">
                        <button class="text-gray-700 hover:text-gray-900 text-sm font-medium" id="export-btn">
                            <i class="fas fa-file-export text-xs mr-2"></i>Export
                        </button>
                    </div>
                </div>

        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto scrollbar">
            <table aria-label="Orders Table" role="table"class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input type="checkbox" id="select-all" class="h-4 w-4 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>

              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="orders-table-body">
              <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                  <tr class="hover:bg-gray-50 order-row" data-status="<?= htmlspecialchars($order['status']) ?>" data-customer="<?= htmlspecialchars($order['customer_name']) ?>" data-id="<?= htmlspecialchars($order['id'])?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="orders-checkbox h-4 w-4 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary">
                    </td>
                    <td class="px-6 py-3 text-sm text-xs font-medium text-gray-800 uppercase tracking-wider whitespace-nowrap">#<?= htmlspecialchars($order['id']) ?></td>
                    <td class="px-6 py-3 text-sm text-xs font-medium text-gray-700 uppercase tracking-wider whitespace-nowrap"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                    <td class="px-6 py-3 text-sm text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                    <td class="px-6 py-3 text-sm text-xs font-medium text-gray-900 uppercase tracking-wider whitespace-nowrap">$<?= number_format($order['total'], 2) ?></td>
                    <td class="px-6 py-3 text-sm text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap">
                      <?php
                      $currentStatus = $order['status'];
                      $allowedNextStatuses = $statusFlow[$currentStatus] ?? [$currentStatus];
                      $allStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                      ?>
                      <form method="POST" class="inline-form" aria-label="Update order status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>" />
                        <select name="status" onchange="this.form.submit()">
                          <?php foreach ($allStatuses as $statusOption):
                            $disabled = !in_array($statusOption, $allowedNextStatuses) ? 'disabled' : '';
                            if ($statusOption === 'cancelled' && $currentStatus === 'delivered') {
                              $disabled = 'disabled';
                            }
                            $selected = ($currentStatus === $statusOption) ? 'selected' : '';
                          ?>
                            <option value="<?= $statusOption ?>" <?= $disabled ?> <?= $selected ?>>
                              <?= ucfirst($statusOption) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </form>
                    </td>
                    <td class="px-6 py-3 text-xs font-medium text-gray-600 uppercase tracking-wider whitespace-nowrap"><?= htmlspecialchars(ucfirst($order['payment_status'] ?? 'unknown')) ?></td>
                    <td>
                        <?php if ($order['payment_status'] !== 'success'): ?>
                            <form method="POST" action="verify_payment.php" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>" />
                            <button type="submit" class="table-btn btn-verify" title="Verify Payment" style="font-size: 13px; margin-left: 15px;">
                                <i class="fas fa-check-circle"></i> Verify Payment
                            </button>
                            </form>
                        <?php else: ?>
                            <span>Paid</span>       
                        <?php endif; ?>
                    </td>
                    <td>
                      <a href="order_detail.php?id=<?= htmlspecialchars($order['id']) ?>" class="table-btn" title="View Order Details">
                        <i class="fas fa-eye text-sm"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8" class="text-center py-4">No orders found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
        </div>
        </div>
    </main>

  </div>
  <script>
// Filter functionality
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = btn.getAttribute('data-filter');
        // Highlight active button
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('bg-phoenix-primary', 'text-white'));
        btn.classList.add('bg-phoenix-primary', 'text-white');
        // Show/hide rows
        document.querySelectorAll('.order-row').forEach(row => {
            if (filter === 'all' || row.getAttribute('data-status') === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

// Optional: search functionality
const searchInput = document.getElementById('search-orders');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const val = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('.order-row').forEach(row => {
            const id = row.getAttribute('data-id') || '';
            const customer = row.getAttribute('data-customer') || '';
            if (id.toLowerCase().includes(val) || customer.toLowerCase().includes(val)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Select all checkboxes
    const selectAll = document.getElementById('select-all');
    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.orders-checkbox').forEach(cb => cb.checked = selectAll.checked);
    });


    // Export CSV (basic)
    document.getElementById('export-btn').addEventListener('click', function(e) {
        e.preventDefault();
        let csv = '';
        document.querySelectorAll('table thead tr th').forEach(th => {
            if (th.innerText.trim() !== '') csv += `"${th.innerText.trim()}",`;
        });
        csv = csv.slice(0, -1) + '\n';
        document.querySelectorAll('table tbody tr').forEach(tr => {
            if (tr.style.display === 'none') return; 
            tr.querySelectorAll('td').forEach((td, i) => {
                if (i === 1) {
                    const a = td.querySelector('a');
                    csv += `"${a ? a.innerText.trim() : td.innerText.trim()}",`;
                } else if (i !== 0 && i !== tr.children.length - 1) {
                    csv += `"${td.innerText.trim()}",`;
                }
            });
            csv = csv.slice(0, -1) + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'orders.csv';
        a.click();
        URL.revokeObjectURL(url);
    });
</script>

</body>
</html>
