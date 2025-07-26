  <?php
require 'Admin/includes/db.php';

$searchQuery = $_GET['search'] ?? '';
$categoryName = $_GET['category'] ?? '';
$pageTitle = "Home";
$pageHeading = "All Products";

if ($searchQuery) {
    $pageTitle = "Search results for '" . htmlspecialchars($searchQuery) . "'";
    $pageHeading = "Search results for '" . htmlspecialchars($searchQuery) . "'";
} elseif ($categoryName) {
    $pageTitle = "Products in " . htmlspecialchars($categoryName);
    $pageHeading = "Category: " . htmlspecialchars($categoryName);
}

$stmtFeatured = $pdo->prepare("
    SELECT id, name, price, image 
    FROM products
    WHERE stock > 0
    ORDER BY created_at DESC
    LIMIT 10
");
$stmtFeatured->execute();
$featuredProducts = $stmtFeatured->fetchAll(PDO::FETCH_ASSOC);

// Now fetch images for all featured products
$productIds = array_column($featuredProducts, 'id');

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

// Attach first image or placeholder to each featured product
foreach ($featuredProducts as &$product) {
    $pid = $product['id'];
    $product['image_url'] = $imagesByProduct[$pid][0] ?? 'uploads/products/placeholder.jpg';
}
unset($product);


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

  <!-- Hero Section -->
<section class="bg-gradient-to-r from-indigo-600 via-purple-700 to-pink-600 text-white py-20">
  <div class="container mx-auto px-6 flex flex-col md:flex-row items-center">
    
    <!-- Text Content -->
    <div class="md:w-1/2 mb-10 md:mb-0">
      <h1 class="text-3xl md:text-4xl font-extrabold leading-tight mb-6 drop-shadow-lg">
        Illuminate Your Style<br class="hidden sm:block" />
        with Lumière.
      </h1>
      <p class="text-lg md:text-xl max-w-lg mb-8 text-indigo-200 drop-shadow-md">
        Step into the season with confidence and elegance. Explore our exclusive range of premium fashion pieces crafted to elevate your everyday look — from breezy dresses to statement accessories.
      </p>
      <a href="shop.php" class="inline-block bg-white text-indigo-700 font-semibold px-10 py-4 rounded-lg shadow-lg hover:bg-indigo-50 transition">
        Shop Now
      </a>
    </div>
    
    <!-- Image Content -->
    <div class="md:w-1/2 flex justify-center">
      <img
        src="https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80"
        alt="Fashion Model wearing summer collection"
        class="rounded-xl shadow-2xl max-w-full h-auto object-cover"
      />
    </div>
  </div>
</section>
  <hr>

  <!-- Featured Products -->
  <section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold">Featured Products</h2>
        <a href="#" class="text-indigo-600 hover:underline font-medium"
          >View All</a
        >
      </div>

      <div
  class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8"
  id="products-container"
  aria-live="polite"
>
  <?php if (!empty($featuredProducts)): ?>
    <?php foreach ($featuredProducts as $product): ?>
      <div class="product-card bg-white p-4 rounded-lg shadow hover:shadow-lg transition">
        <a href="product.php?id=<?= htmlspecialchars($product['id']) ?>" class="block">
          <img 
            src="/E-shop/<?= htmlspecialchars($product['image_url'] ?: 'uploads/products/placeholder.jpg') ?>" 
            alt="<?= htmlspecialchars($product['name']) ?>" 
            class="w-full h-48 object-cover rounded mb-4"
            loading="lazy"
          />
          <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($product['name']) ?></h3>
          <p class="text-indigo-600 font-bold mt-1">$<?= number_format($product['price'], 2) ?></p>
        </a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="text-center col-span-full py-8 text-gray-500">No featured products found.</p>
  <?php endif; ?>
</div>
    </div>
  </section>

  <!-- Special Offer -->
  <section class="py-16 bg-indigo-600 text-white">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-3xl font-bold mb-4">Special Offer - Limited Time!</h2>
      <p class="text-xl mb-8 max-w-2xl mx-auto">
        Get 30% off on all orders above $100. Use code
        <span
          class="font-bold bg-white text-indigo-600 px-2 py-1 rounded"
          >SUMMER30</span
        >
        at checkout.
      </p>
      <div class="flex justify-center space-x-4 mb-8">
        <div
          class="bg-white bg-opacity-20 rounded-lg p-4 text-center"
          aria-label="Days remaining"
        >
          <div class="text-3xl font-bold" id="days">00</div>
          <div class="text-sm">Days</div>
        </div>
        <div
          class="bg-white bg-opacity-20 rounded-lg p-4 text-center"
          aria-label="Hours remaining"
        >
          <div class="text-3xl font-bold" id="hours">00</div>
          <div class="text-sm">Hours</div>
        </div>
        <div
          class="bg-white bg-opacity-20 rounded-lg p-4 text-center"
          aria-label="Minutes remaining"
        >
          <div class="text-3xl font-bold" id="minutes">00</div>
          <div class="text-sm">Minutes</div>
        </div>
        <div
          class="bg-white bg-opacity-20 rounded-lg p-4 text-center"
          aria-label="Seconds remaining"
        >
          <div class="text-3xl font-bold" id="seconds">00</div>
          <div class="text-sm">Seconds</div>
        </div>
      </div>
      <button
        class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition shadow-lg"
      >
        Shop Now
      </button>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-12 bg-white">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-12">What Our Customers Say</h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
          <div class="flex items-center mb-4">
            <div
              class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold mr-4"
            >
              RG
            </div>
            <div>
              <h4 class="font-bold">Rachael Green</h4>
              <div class="flex text-yellow-400" aria-label="5 star rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">
            "The quality of the products exceeded my expectations. Fast shipping
            and excellent customer service!"
          </p>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
          <div class="flex items-center mb-4">
            <div
              class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold mr-4"
            >
              JP
            </div>
            <div>
              <h4 class="font-bold">Jessica Pearson</h4>
              <div class="flex text-yellow-400" aria-label="4.5 star rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">
            "I've ordered multiple times and always had a great experience. Highly
            recommend this store!"
          </p>
        </div>

        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
          <div class="flex items-center mb-4">
            <div
              class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold mr-4"
            >
              GM
            </div>
            <div>
              <h4 class="font-bold">Gabriella Montez</h4>
              <div class="flex text-yellow-400" aria-label="5 star rating">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
            </div>
          </div>
          <p class="text-gray-600">
            "The summer collection is amazing! Comfortable, stylish, and reasonably
            priced. Will shop again!"
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Newsletter -->
  <section class="py-12 bg-gray-100">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-3xl font-bold mb-4">Subscribe to Our Newsletter</h2>
      <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
        Stay updated with our latest products, exclusive offers, and fashion tips.
      </p>
      <div class="max-w-md mx-auto flex">
        <input
          type="email"
          placeholder="Your email address"
          class="flex-grow px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          aria-label="Email address"
        />
        <button
          class="bg-indigo-600 text-white px-6 py-3 rounded-r-lg hover:bg-indigo-700 transition font-medium"
        >
          Subscribe
        </button>
      </div>
    </div>
  </section>
  <?php include 'footer.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>

  <script src="./assets/index.js"></script>
</body>
</html>