<?php
require 'Admin/includes/db.php';

$searchQuery = $_GET['search'] ?? '';
$categoryName = $_GET['category'] ?? '';

try {
    if ($searchQuery) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0 AND (p.name LIKE ? OR p.description LIKE ?)
            ORDER BY p.name
        ");
        $likeQuery = '%' . $searchQuery . '%';
        $stmt->execute([$likeQuery, $likeQuery]);
        $products = $stmt->fetchAll();

        $pageTitle = "Search results for '" . htmlspecialchars($searchQuery) . "'";
        $pageHeading = $pageTitle;

    } elseif ($categoryName) {
        $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmtCat->execute([$categoryName]);
        $category = $stmtCat->fetch();

        if (!$category) {
            throw new Exception('Category not found.');
        }

        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0 AND p.category_id = ?
            ORDER BY p.name
        ");
        $stmt->execute([$category['id']]);
        $products = $stmt->fetchAll();

        $pageTitle = "Products in " . htmlspecialchars($categoryName);
        $pageHeading = "Category: " . htmlspecialchars($categoryName);

    } else {
        $stmt = $pdo->query("
            SELECT p.*, c.name AS category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0
            ORDER BY RAND()
        ");
        $products = $stmt->fetchAll();

        $pageTitle = "All Products";
        $pageHeading = "All Products";
    }

    // Now fetch images for all products fetched above (run in ALL cases)
    $productIds = array_column($products, 'id');

    $imagesByProduct = [];
    if ($productIds) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmtImages = $pdo->prepare("SELECT product_id, image_url FROM product_images WHERE product_id IN ($placeholders) ORDER BY is_main DESC, id ASC");
        $stmtImages->execute($productIds);
        $images = $stmtImages->fetchAll();

        foreach ($images as $img) {
            $imagesByProduct[$img['product_id']][] = $img['image_url'];
        }
    }

    // Attach first image or placeholder to each product
    foreach ($products as &$product) {
        $pid = $product['id'];
        $product['image_url'] = $imagesByProduct[$pid][0] ?? 'uploads/products/placeholder.jpg';
    }
    unset($product);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $pageTitle ?> - Lumière</title>
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
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold"><?= $pageHeading ?></h2>
        
        <?php if ($searchQuery): ?>
          <div class="text-gray-600">
            Found <?= count($products) ?> result<?= count($products) !== 1 ? 's' : '' ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (empty($products)): ?>
        <div class="text-center py-12">
          <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
          <p class="text-gray-600 text-lg">
            <?= $searchQuery ? 'No products found for "' . htmlspecialchars($searchQuery) . '"' : 'No products available' ?>
          </p>
          <?php if ($searchQuery): ?>
            <a href="shop.php" class="text-indigo-600 hover:underline mt-2 inline-block">
              Browse all products
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8" id="products-container">
          <?php foreach ($products as $index => $product): ?>
            <div class="product-card bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition duration-300"
         style="<?= ($index >= 15) ? 'display:none;' : '' ?>">
              <a href="product.php?id=<?= $product['id'] ?>" class="block">
                <div class="relative">
                  <img 
                    src="/E-shop/<?= htmlspecialchars($product['image_url'] ?? 'uploads/products/placeholder.jpg') ?>" 
                    alt="<?= htmlspecialchars($product['name']) ?>" 
                    class="w-full h-48 object-cover"
                    loading="lazy"
                  />
                </div>
                <div class="p-4">
                  <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($product['name']) ?></h3>
                    <span class="text-indigo-600 font-bold">$<?= number_format($product['price'], 2) ?></span>
                  </div>
                  <div class="flex items-center mb-2">
                    <span class="text-gray-500 text-sm"><?= htmlspecialchars($product['category_name']) ?></span>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if (count($products) > 15): ?>
  <div class="text-center mt-8">
    <button id="view-more-btn" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition font-semibold">
      View More
    </button>
  </div>
<?php endif; ?>

    </div>
  </section>

  <?php include 'footer.php'; ?>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('view-more-btn');
    if (!btn) return;

    btn.addEventListener('click', () => {
      // Show all hidden product cards
      document.querySelectorAll('#products-container > div[style*="display:none"]').forEach(el => {
        el.style.display = '';
      });

      // Hide the button after showing more
      btn.style.display = 'none';
    });
  });
</script>

</body>
</html>