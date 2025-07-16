<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

// Fetch customers with order count and total spent
$stmt = $pdo->query("
  SELECT u.*, 
    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
    (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id) AS total_spent
  FROM users u
  ORDER BY u.created_at DESC
");
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Lumiere | Customer Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc;
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
  </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
  <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 p-6 md:pl-72 overflow-auto">
    <div class="max-w-7xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">Customers</h2>

      <!-- Filter Buttons -->
      <div class="flex flex-wrap items-center gap-3 mb-4" id="filter-buttons">
        <button class="px-3 py-1.5 rounded-lg bg-phoenix-primary text-white text-sm font-medium filter-btn" data-filter="all">
          <i class="fas fa-filter text-xs mr-1"></i> All customers
        </button>
        <button class="px-3 py-1.5 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="active">
          <i class="fas fa-circle-check text-xs mr-1"></i> Active
        </button>
        <button class="px-3 py-1.5 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="inactive">
          <i class="fas fa-circle-xmark text-xs mr-1"></i> Inactive
        </button>
        <button class="px-3 py-1.5 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="banned">
          <i class="fas fa-ban text-xs mr-1"></i> Banned
        </button>
      </div>

      <!-- Search and Export -->
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-grow max-w-md">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
          <input type="text" id="search-customers" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-phoenix-primary focus:border-phoenix-primary" placeholder="Search customers by name or email">
        </div>
        <div class="ml-auto">
          <button class="text-gray-700 hover:text-gray-900 text-sm font-medium" id="export-btn">
            <i class="fas fa-file-export text-xs mr-2"></i>Export
          </button>
        </div>
      </div>

      <!-- Customers Table -->
      <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto scrollbar">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <input type="checkbox" id="select-all" class="h-4 w-4 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Customer</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Email</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Orders</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Total Spent</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="customers-table-body">
              <?php if (count($customers) === 0): ?>
                <tr><td colspan="7" class="text-center py-4">No customers found.</td></tr>
              <?php else: ?>
                <?php foreach ($customers as $cust): ?>
                  <?php
                    $status = 'active';
                  ?>
                  <tr class="hover:bg-gray-50 customer-row" data-status="<?= $status ?>" data-search="<?= htmlspecialchars($cust['name'] . ' ' . $cust['email']) ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <input type="checkbox" class="customer-checkbox h-3 w-3 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap flex items-center gap-3">
                      <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-white font-small">
                        <?= strtoupper(substr($cust['name'], 0, 2)) ?>
                      </div>
                      <span class="font-medium text-sm text-gray-700"><?= htmlspecialchars($cust['name']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($cust['email']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-2 py-1 text-xs font-medium rounded-full
                        <?= $status === 'active' ? 'bg-green-100 text-green-800' : ($status === 'inactive' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-800') ?>">
                        <?= ucfirst($status) ?>
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= $cust['order_count'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?= number_format($cust['total_spent'], 2) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="relative inline-block text-left">
                        <button type="button" class="inline-flex justify-center w-full rounded-md px-2 py-1 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none action-btn">
                          <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="hidden origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 dropdown-menu z-10">
                          <div class="py-1" role="menu">
                            <a href="customer_details.php?id=<?= $cust['id'] ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">View details</a>
                            
                            <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">Delete</a>
                          </div>
                        </div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Dropdown toggle
      document.querySelectorAll('.action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
          e.stopPropagation();
          const menu = this.nextElementSibling;
          document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m !== menu) m.classList.add('hidden');
          });
          menu.classList.toggle('hidden');
        });
      });
      document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
      });

      // Filter buttons
      document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');
          document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('bg-phoenix-primary', 'text-white'));
          this.classList.add('bg-phoenix-primary', 'text-white');
          document.querySelectorAll('.customer-row').forEach(row => {
            if (filter === 'all' || row.getAttribute('data-status') === filter) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
      });

      // Search input
      const searchInput = document.getElementById('search-customers');
      searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.customer-row').forEach(row => {
          const searchText = row.getAttribute('data-search').toLowerCase();
          row.style.display = searchText.includes(val) ? '' : 'none';
        });
      });

      // Select all checkbox
      const selectAll = document.getElementById('select-all');
      selectAll.addEventListener('change', function() {
        document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = this.checked);
      });

      // Export button (placeholder)
      document.getElementById('export-btn').addEventListener('click', function() {
        alert('Export functionality coming soon!');
      });
    });
  </script>
</body>
</html>
