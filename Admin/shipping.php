<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

// Handle form submission for add / edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null; // for edit
    $country = trim($_POST['country']);
    $state = trim($_POST['state']);
    $shipping_fee = $_POST['shipping_fee'];
    $active = isset($_POST['active']) ? 1 : 0;

    if ($id) {
        // Update existing zone
        $stmt = $pdo->prepare("UPDATE shipping_zones SET country=?, state=?, shipping_fee=?, active=? WHERE id=?");
        $stmt->execute([$country, $state, $shipping_fee, $active, $id]);
    } else {
        // Insert new
        $stmt = $pdo->prepare("INSERT INTO shipping_zones (country, state, shipping_fee, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$country, $state, $shipping_fee, $active]);
    }
    header('Location: shipping.php');
    exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delStmt = $pdo->prepare("DELETE FROM shipping_zones WHERE id=?");
    $delStmt->execute([$_GET['delete']]);
    header('Location: shipping.php');
    exit;
}

// Fetch all zones
$zones = $pdo->query("SELECT * FROM shipping_zones ORDER BY country, state")->fetchAll();

// For edit form
$editZone = null;
if (isset($_GET['edit'])) {
  $editId = intval($_GET['edit']);
  $stmt = $pdo->prepare("SELECT * FROM shipping_zones WHERE id=?");
  $stmt->execute([$editId]);
  $editZone = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumière | Manage Shipping Zones</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="font-sans min-h-full bg-gray-50">
  <div class="flex min-h-screen flex-col md:flex-row">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:pl-64 ml-2">
      <header class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900">Shipping Zones Management</h2>
      </header>

      <section class="mb-8 max-w-md rounded-md bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800"><?= $editZone ? "Edit Shipping Zone" : "Add Shipping Zone" ?></h3>
        <form method="POST" action="shipping.php" class="space-y-4">
          <input type="hidden" name="id" value="<?= htmlspecialchars($editZone['id'] ?? '') ?>" />

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="country">Country</label>
            <input type="text" id="country" name="country" required class="block w-full rounded-md border border-gray-300 p-3 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($editZone['country'] ?? '') ?>" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="state">State</label>
            <input type="text" id="state" name="state" required class="block w-full rounded-md border border-gray-300 p-3 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($editZone['state'] ?? '') ?>" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="shipping_fee">Shipping Fee</label>
            <input type="number" id="shipping_fee" name="shipping_fee" step="0.01" min="0" required class="block w-full rounded-md border border-gray-300 p-3 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-400 focus:outline-none" value="<?= htmlspecialchars($editZone['shipping_fee'] ?? '0.00') ?>" />
          </div>

          <div class="flex items-center space-x-2">
            <input type="checkbox" id="active" name="active" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" <?= (!isset($editZone) || $editZone['active']) ? 'checked' : '' ?> />
            <label for="active" class="text-gray-700 select-none">Active</label>
          </div>

          <button type="submit" class="w-full rounded-md bg-blue-600 py-3 text-white font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
            <?= $editZone ? "Update" : "Add" ?>
          </button>
        </form>
      </section>

      <section class="rounded-md bg-white p-6 shadow overflow-x-auto">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Existing Shipping Zones</h3>
        <table class="w-full table-auto border-collapse text-left">
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
                  <td class="px-4 py-3 space-x-3">
                    <a href="shipping.php?edit=<?= $zone['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                    <a href="shipping.php?delete=<?= $zone['id'] ?>" onclick="return confirm('Are you sure you want to delete this shipping zone?')" class="text-red-600 hover:underline">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>

  </div>
</body>
</html>
