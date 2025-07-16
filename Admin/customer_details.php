<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();
require 'includes/mailer.php';


if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit();
}

$customerId = (int)$_GET['id'];

// Fetch customer info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: customers.php');
    exit();
}
$messageError = '';
$messageSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $msg = trim($_POST['message'] ?? '');

    if (!$subject || !$msg) {
        $messageError = 'Please fill in both subject and message.';
    } else {
        $toEmail = $customer['email'];
        $toName = $customer['name'];

        $htmlBody = nl2br(htmlspecialchars($msg));
        $plainBody = strip_tags($htmlBody);

        $sent = sendEmail($toEmail, $toName, $subject, $htmlBody, $plainBody);

        if ($sent) {
            $messageSent = true;
            // Clear form input after success
            $_POST['subject'] = '';
            $_POST['message'] = '';
        } else {
            $messageError = 'Failed to send email. Please try again later.';
        }
    }
}

// Fetch customer's orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$customerId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lumiere | Customer Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card { background: white; border-radius: 0.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05);}
        .tab { position: relative; padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500;
            color: #6b7280; border-bottom: 2px solid transparent; transition: all 0.2s;}
        .tab:hover { color: #3b82f6;}
        .tab-active { color: #3b82f6; border-bottom-color: #3b82f6;}
        .tab-content { display: none;}
        .tab-content.active { display: block; animation: fadeIn 0.3s ease;}
        @keyframes fadeIn { from {opacity: 0; transform: translateY(5px);} to {opacity: 1; transform: translateY(0);} }
        .avatar { transition: transform 0.2s ease;}
        .avatar:hover { transform: scale(1.05);}
        .status-badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;}
        .status-active { background-color: #d1fae5; color: #065f46;}
        .status-inactive { background-color: #fee2e2; color: #b91c1c;}
        .form-input {
          border: 1px solid #d1d5db;
          border-radius: 0.375rem;
          padding: 0.5rem 0.75rem;
          outline: none;
        }
        .form-input:focus {
          border-color: #2563eb;
          box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
        }

    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:pl-72 overflow-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm z-10">
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Customer Details</h1>
                        <nav class="flex text-sm text-gray-500 mt-1">
                            <a href="customers.php" class="hover:text-blue-500">Customers</a>
                            <span class="mx-2">/</span>
                            <span class="text-gray-600"><?= htmlspecialchars($customer['name']) ?></span>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="customers.php" class="p-2 rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-700" title="Back">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Customer Header -->
                <div class="card p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center mb-4 md:mb-0">
                            <div class="w-16 h-16 rounded-full mr-4 avatar bg-gray-200 flex justify-center items-center text-2xl text-blue-800 font-bold">
                                <?= strtoupper(substr($customer['name'],0,2)) ?>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($customer['name']) ?></h2>
                                <div class="flex items-center mt-1">
                                    <span class="text-sm text-gray-500 mr-3"><?= htmlspecialchars($customer['email']) ?></span>
                                    <span class="status-badge status-active">Active</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <a href="send_message.php?user_id=<?= htmlspecialchars($customer['id'])?>" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center">
                                <i class="fas fa-envelope mr-2"></i> Message
                            </a>
                            <button class="px-4 py-2 bg-blue-600 rounded-md text-sm font-medium text-white hover:bg-blue-700 flex items-center">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" data-tab="overview" class="tab tab-active">Overview</a>
                        <a href="#" data-tab="orders" class="tab">Orders</a>
                        <a href="#" data-tab="activity" class="tab">Activity</a>
                        <a href="#" data-tab="notes" class="tab">Notes</a>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div id="tab-contents">
                    <!-- Overview Tab -->
                    <div id="overview" class="tab-content active">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Left Column -->
                            <div class="lg:col-span-2 space-y-6">
                                <!-- Customer Details Card -->
                                <div class="card p-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 mb-3">PERSONAL INFORMATION</h4>
                                            <div class="space-y-4">
                                                <div>
                                                    <p class="text-xs text-gray-500">Full Name</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['name']) ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Email Address</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['email']) ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Phone Number</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['phone'] ?? '—') ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Joined</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['created_at']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-500 mb-3">ADDRESS</h4>
                                            <div class="space-y-4">
                                                <div>
                                                    <p class="text-xs text-gray-500">Country</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['country'] ?? '—') ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">State/Region</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['state'] ?? '—') ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">City</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['city'] ?? '—') ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Address</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['address1'] ?? '—') ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Address</p>
                                                    <p class="text-sm font-medium"><?= htmlspecialchars($customer['address2'] ?? '—') ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Recent Orders Card -->
                                <div class="card p-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                                <?= $order['status'] === 'completed' ? 'bg-green-100 text-green-800'
                                                                : ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800'
                                                                : ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800'
                                                                : 'bg-blue-100 text-blue-800')) ?>">
                                                                <?= ucfirst($order['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?= number_format($order['total'], 2) ?></td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <a href="order_detail.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900" title="View"><i class="fas fa-eye text-sm"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($orders)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4">No orders found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- Right Column -->
                          <div class="space-y-6">
                            <!-- Message Customer Card -->
                            <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden p-6">
                              <h3 class="text-lg font-medium text-gray-900 mb-4">Send Message</h3>
                              <?php if (isset($messageSent) && $messageSent): ?>
                                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                                  Message sent successfully!
                                </div>
                              <?php endif; ?>
                              <?php if (!empty($messageError)): ?>
                                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded"><?= htmlspecialchars($messageError) ?></div>
                              <?php endif; ?>
                              
                              <form method="POST" id="messageForm">
                                <input type="hidden" name="send_message" value="1" />
                                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <input name="subject" id="subject" required type="text" class="form-input w-full mb-4" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" />
                              
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea name="message" id="message" rows="5" required class="form-input w-full mb-4"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                              
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg border-2 border-blue-600 hover:bg-white hover:text-blue-600 transition-colors duration-200">
                                  Send Message
                                </button>
                              </form>
                            </div>
                              
                          </div>
                        </div>
                    </div>
                    <!-- Orders/Activity/Notes tabs could be implemented as needed -->
                </div>
            </main>
        </main>
    </div>
    <script>
    // Tab logic
    document.querySelectorAll('.tab').forEach(function(tab) {
        tab.addEventListener('click', function(e){
            e.preventDefault();
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
            this.classList.add('tab-active');
            let activeTab = this.getAttribute('data-tab');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(activeTab).classList.add('active');
        });
    });
    </script>
</body>
</html>
