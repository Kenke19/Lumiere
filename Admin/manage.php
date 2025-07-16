<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

$errorCat = $successCat = '';
$errorShip = $successShip = '';

// Handle Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'category') {
    $name = trim($_POST['name'] ?? '');
    if (!$name) {
        $errorCat = 'Category name cannot be empty.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errorCat = 'Category already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $successCat = 'Category added successfully.';
        }
    }
}

// Handle Shipping POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'shipping') {
    $id = $_POST['id'] ?? null;
    $country = trim($_POST['country'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $shipping_fee = $_POST['shipping_fee'] ?? 0;
    $active = isset($_POST['active']) ? 1 : 0;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE shipping_zones SET country=?, state=?, shipping_fee=?, active=? WHERE id=?");
        $stmt->execute([$country, $state, $shipping_fee, $active, $id]);
        $successShip = 'Shipping zone updated successfully.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO shipping_zones (country, state, shipping_fee, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$country, $state, $shipping_fee, $active]);
        $successShip = 'Shipping zone added successfully.';
    }
}

// Handle shipping delete
if (isset($_GET['delete_shipping'])) {
    $id = (int)$_GET['delete_shipping'];
    $stmt = $pdo->prepare("DELETE FROM shipping_zones WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: manage.php');
    exit;
}

// Fetch data
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$zones = $pdo->query("SELECT * FROM shipping_zones ORDER BY country, state")->fetchAll();

// For editing shipping zone
$editZone = null;
if (isset($_GET['edit_shipping'])) {
    $stmt = $pdo->prepare("SELECT * FROM shipping_zones WHERE id=?");
    $stmt->execute([$_GET['edit_shipping']]);
    $editZone = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Lumière | Manage Categories & Shipping Zones</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('[data-tab-target]');
    const tabContents = document.querySelectorAll('[data-tab-content]');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tabTarget;
        tabContents.forEach(c => c.classList.add('hidden'));
        tabs.forEach(t => t.classList.remove('border-blue-600', 'text-blue-600', 'font-semibold'));
        document.querySelector(target).classList.remove('hidden');
        tab.classList.add('border-blue-600', 'text-blue-600', 'font-semibold');
      });
    });

    if(tabs.length > 0) {
      tabs[0].click();
    }
  });
</script>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen flex flex-col">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main -->
    <main class="flex-1 p-6 md:pl-64 ml-2">
      <header class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Management</h1>
      </header>

      <nav class="mb-6 flex border-b border-gray-200 text-sm font-medium text-gray-600 space-x-8" role="tablist">
        <button data-tab-target="#categoryTab" type="button" role="tab" aria-selected="true" class="border-b-2 border-transparent pb-2 hover:text-blue-600 focus:outline-none focus:text-blue-600">
          Categories
        </button>
        <button data-tab-target="#shippingTab" type="button" role="tab" aria-selected="false" class="border-b-2 border-transparent pb-2 hover:text-blue-600 focus:outline-none focus:text-blue-600">
          Shipping Zones
        </button>
      </nav>

      <!-- Categories Tab -->
      <section id="categoryTab" data-tab-content class="hidden">
        <?php if ($errorCat): ?>
          <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700 ring-1 ring-red-400"><?= htmlspecialchars($errorCat) ?></div>
        <?php elseif ($successCat): ?>
          <div class="mb-6 rounded-md bg-green-50 p-4 text-green-700 ring-1 ring-green-400"><?= htmlspecialchars($successCat) ?></div>
        <?php endif; ?>
          <section class="rounded-md bg-white p-6 shadow overflow-x-auto">
          <h3 class="mb-4 text-lg font-semibold text-gray-800"> Categories</h3>
          <table class="w-full border-collapse text-left table-auto">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">ID</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">Name</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($categories) === 0): ?>
                <tr>
                  <td colspan="2" class="py-4 text-center text-gray-500">No categories found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                  <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="whitespace-nowrap px-4 py-3 text-gray-700"><?= $cat['id'] ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($cat['name']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </section>
        <form method="POST" class="mb-8 max-w-md rounded-md bg-white p-6 shadow mt-4" novalidate>
        <h3 class="mb-4 text-lg text-center font-semibold text-gray-800">Add New Category</h3>
          <input type="hidden" name="form_type" value="category" />
          <label for="cat_name" class="block text-sm font-medium text-gray-700 mb-2">New Category Name</label>
          <input
            type="text"
            id="cat_name"
            name="name"
            required
            class="block w-full rounded-md border border-gray-300 p-3 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-400 focus:outline-none"
            placeholder="Enter category name"
          />
          <button type="submit" class="mt-4 w-full rounded-md bg-blue-600 py-3 text-white font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
            Add Category
          </button>
        </form>

        
      </section>

      <!-- Shipping Zones Tab -->
      <section id="shippingTab" data-tab-content class="hidden">
        <?php if ($errorShip): ?>
          <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700 ring-1 ring-red-400"><?= htmlspecialchars($errorShip) ?></div>
        <?php elseif ($successShip): ?>
          <div class="mb-6 rounded-md bg-green-50 p-4 text-green-700 ring-1 ring-green-400"><?= htmlspecialchars($successShip) ?></div>
        <?php endif; ?>

        <section class="rounded-md bg-white p-6 shadow overflow-x-auto">
          <h3 class="mb-4 text-lg font-semibold text-gray-800">Shipping Zones</h3>
          <table class="w-full border-collapse table-auto text-left">
            <thead class="border-b border-gray-200 bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">ID</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">Country</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">State</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">Shipping Fee</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">Active</th>
                <th class="px-4 py-3 text-xs font-medium uppercase text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($zones) === 0): ?>
                <tr>
                  <td colspan="6" class="py-4 text-center text-gray-500">No shipping zones found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($zones as $zone): ?>
                  <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="whitespace-nowrap px-4 py-3 text-gray-700"><?= $zone['id'] ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($zone['country']) ?></td>
                    <td class="px-4 py-3"><?= htmlspecialchars($zone['state']) ?></td>
                    <td class="px-4 py-3">₦<?= number_format($zone['shipping_fee'], 2) ?></td>
                    <td class="px-4 py-3"><?= $zone['active'] ? 'Yes' : 'No' ?></td>
                    <td class="px-4 py-3 space-x-3 flex items-center space-x-4">
                      <a href="manage.php?edit_shipping=<?= $zone['id'] ?>" title="Edit" class="text-blue-600 hover:text-blue-800"><i class="fas fa-pen-to-square fa-xs"></i>
                      </a>
                      <a href="manage.php?delete_shipping=<?= $zone['id'] ?>" title="Delete" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this shipping zone?')">
                        <i class="fas fa-trash fa-xs"></i>
                      </a>
                    </td>

                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </section>

        <section class="mb-8 max-w-md rounded-md bg-white p-6 shadow mt-4">
          <h3 class="mb-4 text-lg font-semibold text-gray-800 text-center"><?= $editZone ? "Edit Shipping Zone" : "Add Shipping Zone" ?></h3>
          <form method="POST" action="manage.php" class="space-y-4" novalidate>
            <input type="hidden" name="form_type" value="shipping" />
            <input type="hidden" name="id" value="<?= htmlspecialchars($editZone['id'] ?? '') ?>" />

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1" for="country">Country</label>
              <input type="text" name="country" id="country" required class="block w-full rounded-md border border-gray-300 p-3" value="<?= htmlspecialchars($editZone['country'] ?? '') ?>" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1" for="state">State</label>
              <input type="text" name="state" id="state" required class="block w-full rounded-md border border-gray-300 p-3" value="<?= htmlspecialchars($editZone['state'] ?? '') ?>" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1" for="shipping_fee">Shipping Fee</label>
              <input type="number" name="shipping_fee" id="shipping_fee" step="0.01" min="0" required class="block w-full rounded-md border border-gray-300 p-3" value="<?= htmlspecialchars($editZone['shipping_fee'] ?? '0.00') ?>" />
            </div>
            <div class="flex items-center space-x-2">
              <input type="checkbox" id="active" name="active" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= (!isset($editZone) || $editZone['active']) ? 'checked' : '' ?> />
              <label for="active" class="text-gray-700 select-none">Active</label>
            </div>

            <button type="submit" class="w-full rounded-md bg-blue-600 py-3 text-white font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"><?= $editZone ? "Update" : "Add" ?></button>
          </form>
        </section>
      </section>
    </main>

  </div>
</body>
</html>
