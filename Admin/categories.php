<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if (!$name) {
        $error = 'Category name cannot be empty.';
    } else {
        // Check if category already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Category already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = 'Category added successfully.';
        }
    }
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lumiere | Manage Categories</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen flex flex-col ">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:pl-64 ml-2">
      <header class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-900">Category Management</h2>
      </header>

      <?php if ($error): ?>
        <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700 ring-1 ring-red-400">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php elseif ($success): ?>
        <div class="mb-6 rounded-md bg-green-50 p-4 text-green-700 ring-1 ring-green-400">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="mb-8 max-w-md rounded-md bg-white p-6 shadow">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">New Category Name</label>
        <input
          type="text"
          id="name"
          name="name"
          required
          class="block w-full rounded-md border border-gray-300 p-3 text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-400 focus:outline-none"
          placeholder="Enter category name"
        />
        <button
          type="submit"
          class="mt-4 w-full rounded-md bg-blue-600 py-3 text-white font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1"
        >
          Add Category
        </button>
      </form>

      <section class="rounded-md bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Existing Categories</h3>
        <div class="overflow-x-auto">
          <table class="w-full table-auto border-collapse text-left">
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
        </div>
      </section>
    </main>
  </div>
</body>
</html>
