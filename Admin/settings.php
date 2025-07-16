<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = $_POST['store_name'] ?? '';
    $storeEmail = $_POST['store_email'] ?? '';

    if (!$storeName || !$storeEmail) {
        $error = 'Store name and email are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute(['store_name', $storeName]);
        $stmt->execute(['store_email', $storeEmail]);

        $success = 'Settings updated successfully.';
    }
}

$stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ('store_name', 'store_email')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <title>Settings - MyShop Admin</title>
  <link rel="stylesheet" href="assets/admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
  <div class="dashboard-container">
  <?php include 'sidebar.php'; ?>
  <main class="main-content">
    <?php include 'topbar.php'; ?>

    <section id="settings" class="page active">
      <div class="page-header">
        <h1 class="page-title">Store Settings</h1>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST" class="form-modern fade-in" style="max-width: 600px;">
        <div class="form-group">
          <label class="form-label required">Store Name</label>
          <input type="text" name="store_name" class="form-input" required value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label class="form-label required">Store Email</label>
          <input type="email" name="store_email" class="form-input" required value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>" />
        </div>
        <!-- Add more settings fields as needed -->
        <div class="form-actions">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
      </form>
    </section>
  </main>
  </div>
</body>
</html>
