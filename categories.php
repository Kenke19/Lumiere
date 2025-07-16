<?php
require 'Admin/includes/db.php';

$stmt = $pdo->query("
  SELECT c.id, c.name, c.image_url, COUNT(p.id) AS item_count
  FROM categories c
  LEFT JOIN products p ON p.category_id = c.id
  GROUP BY c.id, c.name, c.image_url  -- Group by image_url too if selecting it
  ORDER BY c.name
");
$categories = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Shop by Category - Lumière</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
  <link rel="stylesheet" href="index.css" />
</head>
<body class="bg-gray-50 font-sans">
  <?php include 'header.php'; ?>

  <section class="py-12 bg-white">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-12">Shop by Category</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <?php foreach ($categories as $category):
          $name = $category['name'];
          $count = $category['item_count'];
          $imageUrl = !empty($category['image_url']) ? htmlspecialchars($category['image_url']) : '';
        ?>
        <div
          class="bg-gray-100 rounded-lg overflow-hidden hover:shadow-lg transition cursor-pointer"
          role="button" tabindex="0"
          onclick="window.location.href='shop.php?category=<?= urlencode($name) ?>'"
          onkeypress="if(event.key === 'Enter') window.location.href='shop.php?category=<?= urlencode($name) ?>'"
          aria-label="<?= htmlspecialchars($name) ?> category"
        >
          <div class="h-40 bg-gray-200 flex items-center justify-center relative overflow-hidden">
            <img
              src="<?= $imageUrl ?>"
              alt="<?= htmlspecialchars($name) ?> category image"
              class="object-cover w-full h-full"
              loading="lazy"
            />
          </div>
          <div class="p-4">
            <h3 class="font-bold text-lg"><?= htmlspecialchars($name) ?></h3>
            <p class="text-gray-600"><?= $count ?> items</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php include 'footer.php'; ?>

  <script src="index.js?v=1.0"></script>
</body>
</html>
