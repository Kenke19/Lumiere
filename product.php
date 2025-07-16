<?php
session_start();
require 'Admin/includes/db.php'; 

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid product ID');
}
$productId = (int)$_GET['id'];

// Fetch product info
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die('Product not found');
}

// Fetch up to 3 images for the product
$stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? LIMIT 3");
$stmt->execute([$productId]);
$images = $stmt->fetchAll();

if (!$images) {
    $images = [['image_url' => $product['image'] ?: 'uploads/products/placeholder.jpg']];
}

// Handle Add to Cart submission 
$addToCartMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = max(1, (int)$_POST['quantity']);
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
    $addToCartMessage = "Added $quantity x " . htmlspecialchars($product['name']) . " to your cart.";
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($product['name']) ?> - Lumière</title>

  <!-- Google Fonts: Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />

  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    .thumbnails-scroll::-webkit-scrollbar {
      height: 8px;
    }
    .thumbnails-scroll::-webkit-scrollbar-thumb {
      background-color: #6366f1; 
      border-radius: 4px;
    }
    .option-selector {
      transition: all 0.3s ease;
    }
    
    .option-selector:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .option-selector.selected {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.5);
    }
  </style>
</head>
<body class="min-h-screen flex flex-col bg-gray-100">

  <?php include 'header.php'; ?>

  <main class="flex-grow container mx-auto px-6 py-6 max-w-7xl">
    <div class="bg-white rounded-xl shadow-lg p-8 flex flex-col md:flex-row gap-10">

      <!-- Images Section -->
      <section class="md:w-1/2">
        <img
          id="mainImage"
          src="<?= htmlspecialchars($images[0]['image_url']) ?>"
          alt="<?= htmlspecialchars($product['name']) ?>"
          class="w-full rounded-xl object-cover max-h-[450px] shadow-lg"
        />
        <div class="mt-6 flex space-x-4 overflow-x-auto thumbnails-scroll">
          <?php foreach ($images as $index => $img): ?>
            <img
              src="<?= htmlspecialchars($img['image_url']) ?>"
              alt="Thumbnail <?= $index + 1 ?>"
              class="thumbnail w-20 h-20 rounded-lg object-cover cursor-pointer border-4 <?= $index === 0 ? 'border-indigo-600' : 'border-transparent' ?> transition"
              onclick="document.getElementById('mainImage').src = this.src; setActiveThumbnail(this);"
              loading="lazy"
            />
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Product Info Section -->
      <section class="md:w-1/2 flex flex-col justify-between">

        <div>
          <h1 class="text-4xl font-extrabold text-gray-900 mb-4 tracking-tight">
            <?= htmlspecialchars($product['name']) ?>
          </h1>
          <p class="text-indigo-600 text-3xl font-semibold mb-6">
            $<?= number_format($product['price'], 2) ?>
          </p>
          <!-- Rating & Reviews -->
          <div class="flex items-center mb-6">
            <div class="flex items-center">
              <?php $rating = min(5, max(0, round($product['rating'] ?? 4.5))) ?>
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php if ($i <= $rating): ?>
                  <i class="fas fa-star text-yellow-400"></i>
                <?php elseif ($i - 0.5 <= $rating): ?>
                  <i class="fas fa-star-half-alt text-yellow-400"></i>
                <?php else: ?>
                  <i class="far fa-star text-yellow-400"></i>
                <?php endif; ?>
              <?php endfor; ?>
              <span class="ml-2 text-gray-600"><?= $product['review_count'] ?? 42 ?> reviews</span>
            </div>
            <a href="#reviews" class="ml-4 text-sm text-primary-600 hover:underline">Read reviews</a>
          </div>
          <p class="text-gray-700 leading-relaxed whitespace-pre-line mb-8">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
          </p>
        </div>

        <!-- Color Options -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Color</h3>
            <div class="flex flex-wrap gap-3">
              <button type="button" class="option-selector selected px-4 py-2 border-2 border-primary-500 rounded-lg flex items-center">
                <span class="w-4 h-4 rounded-full bg-amber-900 mr-2"></span>
                <span>Brown</span>
              </button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg flex items-center hover:border-gray-300">
                <span class="w-4 h-4 rounded-full bg-red-600 mr-2"></span>
                <span>Red</span>
              </button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg flex items-center hover:border-gray-300">
                <span class="w-4 h-4 rounded-full bg-yellow-200 mr-2"></span>
                <span>Blonde</span>
              </button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg flex items-center hover:border-gray-300">
                <span class="w-4 h-4 rounded-full bg-gray-900 mr-2"></span>
                <span>Black</span>
              </button>
            </div>
          </div>

        <!-- Size Options -->
          <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Size</h3>
            <div class="flex flex-wrap gap-3">
              <button type="button" class="option-selector selected px-4 py-2 border-2 border-primary-500 rounded-lg">S</button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-gray-300">M</button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-gray-300">L</button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-gray-300">XL</button>
              <button type="button" class="option-selector px-4 py-2 border-2 border-gray-200 rounded-lg hover:border-gray-300">XXL</button>
            </div>
          </div>

        <!-- Quantity & Add to Cart -->
        <div class="flex items-center space-x-4">
          <label for="quantity" class="font-medium text-gray-800">Quantity:</label>
          <input
            type="number"
            id="quantity"
            name="quantity"
            value="1"
            min="1"
            max="<?= (int)$product['stock'] ?>"
            class="w-20 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
            <?php if ($product['stock'] <= 0): ?>
  <span class="inline-block rounded bg-red-100 px-3 py-1 text-sm font-semibold text-red-700">Out of Stock</span>
  <button disabled class="mt-2 w-full cursor-not-allowed rounded bg-gray-300 py-2 font-semibold text-gray-600">Sold Out</button>
<?php else: ?>
  <span class="inline-block rounded bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">In Stock (<?= (int) $product['stock'] ?>)</span>
          <button
            type="button"
            id="add-to-cart-btn"
            data-product-id="<?= $productId ?>"
            class="bg-indigo-600 text-white px-6 py-3 rounded-md font-semibold hover:bg-indigo-700 transition shadow-md flex items-center space-x-2"
          >
            <i class="fas fa-cart-plus"></i>
            <span>Add to Cart</span>
          </button>
<?php endif; ?>
        </div>

        <!-- Success message -->
        <div
          id="add-to-cart-message"
          class="hidden mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow"
          role="alert"
        ></div>

        <!-- Product Meta -->
          <div class="border-t border-gray-200 pt-6">
            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
              <div>
                <span class="font-medium text-gray-900">SKU:</span> <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>
              </div>
              <div>
                <span class="font-medium text-gray-900">Category:</span> <?= htmlspecialchars($product['category_name'] ?? 'N/A') ?>
              </div>
              <div>
                <span class="font-medium text-gray-900">Brand:</span> <?= htmlspecialchars($product['brand'] ?? 'Lumière') ?>
              </div>
              <div>
                <span class="font-medium text-gray-900">Shipping:</span> Free worldwide
              </div>
            </div>
          </div>
      </section>
    </div>
    <hr>
    <hr>
    <!-- Product Tabs -->
    <section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto bg-white border-t mt-4 border-gray-100">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button class="tab-button active py-4 px-1 border-b-2 font-medium text-sm border-primary-500 text-primary-600">
            Description
          </button>
          <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Specifications
          </button>
          <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" id="reviews">
            Reviews (<?= $product['review_count'] ?? 42 ?>)
          </button>
          <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
            Shipping & Returns
          </button>
        </nav>
      </div>
      
      <div class="py-8">
        <div class="tab-content active">
          <h3 class="text-2xl font-serif font-bold mb-6">Product Details</h3>
          <div class="prose max-w-none">
            <p>Experience the perfect blend of style and comfort with our premium <?= htmlspecialchars($product['name']) ?>. Designed for those who appreciate quality craftsmanship and attention to detail.</p>
            <ul>
              <li>High-quality materials for lasting durability</li>
              <li>Ergonomic design for maximum comfort</li>
              <li>Available in multiple colors to match your style</li>
              <li>Easy to clean and maintain</li>
              <li>Backed by our 1-year satisfaction guarantee</li>
            </ul>
            <p>Whether you're looking for everyday comfort or a special occasion piece, the <?= htmlspecialchars($product['name']) ?> delivers on all fronts.</p>
          </div>
        </div>
        
        <div class="tab-content hidden">
          <h3 class="text-2xl font-serif font-bold mb-6">Technical Specifications</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Dimensions</h4>
              <ul class="space-y-2 text-gray-700">
                <li class="flex justify-between">
                  <span>Height</span>
                  <span>24 inches</span>
                </li>
                <li class="flex justify-between">
                  <span>Width</span>
                  <span>18 inches</span>
                </li>
                <li class="flex justify-between">
                  <span>Depth</span>
                  <span>12 inches</span>
                </li>
                <li class="flex justify-between">
                  <span>Weight</span>
                  <span>3.5 lbs</span>
                </li>
              </ul>
            </div>
            <div>
              <h4 class="font-semibold text-gray-900 mb-3">Materials</h4>
              <ul class="space-y-2 text-gray-700">
                <li class="flex justify-between">
                  <span>Primary Material</span>
                  <span>100% Organic Cotton</span>
                </li>
                <li class="flex justify-between">
                  <span>Fill Material</span>
                  <span>Hypoallergenic Polyester</span>
                </li>
                <li class="flex justify-between">
                  <span>Care Instructions</span>
                  <span>Machine Wash Cold</span>
                </li>
                <li class="flex justify-between">
                  <span>Origin</span>
                  <span>Made in USA</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        
        <div class="tab-content hidden">
          <div class="flex flex-col md:flex-row gap-8">
            <div class="md:w-2/3">
              <h3 class="text-2xl font-serif font-bold mb-6">Customer Reviews</h3>
              
              <div class="space-y-6">
                <!-- Review 1 -->
                <div class="border-b border-gray-200 pb-6">
                  <div class="flex items-center mb-3">
                    <div class="flex items-center mr-4">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                    </div>
                    <span class="text-sm text-gray-500">Posted on <?= date('F j, Y', strtotime('-2 weeks')) ?></span>
                  </div>
                  <h4 class="font-semibold text-lg mb-2">Absolutely love it!</h4>
                  <p class="text-gray-700 mb-2">The quality exceeded my expectations. It's comfortable, stylish, and has held up perfectly after several washes. Will definitely buy again!</p>
                  <span class="text-sm text-gray-500">- Sarah J.</span>
                </div>
                
                <!-- Review 2 -->
                <div class="border-b border-gray-200 pb-6">
                  <div class="flex items-center mb-3">
                    <div class="flex items-center mr-4">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="far fa-star text-yellow-400"></i>
                    </div>
                    <span class="text-sm text-gray-500">Posted on <?= date('F j, Y', strtotime('-1 month')) ?></span>
                  </div>
                  <h4 class="font-semibold text-lg mb-2">Great product, minor sizing issue</h4>
                  <p class="text-gray-700 mb-2">The quality is excellent, but it runs slightly large. I would recommend sizing down if you're between sizes.</p>
                  <span class="text-sm text-gray-500">- Michael T.</span>
                </div>
                
                <!-- Review Form -->
                <div class="mt-8">
                  <h4 class="font-semibold text-lg mb-4">Write a Review</h4>
                  <form class="space-y-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Your Rating</label>
                      <div class="flex">
                        <i class="far fa-star text-2xl text-yellow-400 cursor-pointer hover:text-yellow-500"></i>
                        <i class="far fa-star text-2xl text-yellow-400 cursor-pointer hover:text-yellow-500"></i>
                        <i class="far fa-star text-2xl text-yellow-400 cursor-pointer hover:text-yellow-500"></i>
                        <i class="far fa-star text-2xl text-yellow-400 cursor-pointer hover:text-yellow-500"></i>
                        <i class="far fa-star text-2xl text-yellow-400 cursor-pointer hover:text-yellow-500"></i>
                      </div>
                    </div>
                    <div>
                      <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">Review Title</label>
                      <input type="text" id="review-title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                      <label for="review-text" class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                      <textarea id="review-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition">Submit Review</button>
                  </form>
                </div>
              </div>
            </div>
            
            <div class="md:w-1/3">
              <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="font-semibold text-lg mb-4">Review Summary</h4>
                <div class="flex items-center mb-4">
                  <div class="text-4xl font-bold mr-4">4.8</div>
                  <div>
                    <div class="flex items-center mb-1">
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star text-yellow-400"></i>
                      <i class="fas fa-star-half-alt text-yellow-400"></i>
                    </div>
                    <div class="text-sm text-gray-600">Based on <?= $product['review_count'] ?? 42 ?> reviews</div>
                  </div>
                </div>
                
                <div class="space-y-2">
                  <div class="flex items-center">
                    <span class="w-10 text-sm">5 stars</span>
                    <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400" style="width: 85%"></div>
                    </div>
                    <span class="w-10 text-sm text-right">85%</span>
                  </div>
                  <div class="flex items-center">
                    <span class="w-10 text-sm">4 stars</span>
                    <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400" style="width: 10%"></div>
                    </div>
                    <span class="w-10 text-sm text-right">10%</span>
                  </div>
                  <div class="flex items-center">
                    <span class="w-10 text-sm">3 stars</span>
                    <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400" style="width: 3%"></div>
                    </div>
                    <span class="w-10 text-sm text-right">3%</span>
                  </div>
                  <div class="flex items-center">
                    <span class="w-10 text-sm">2 stars</span>
                    <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400" style="width: 1%"></div>
                    </div>
                    <span class="w-10 text-sm text-right">1%</span>
                  </div>
                  <div class="flex items-center">
                    <span class="w-10 text-sm">1 star</span>
                    <div class="flex-1 mx-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-yellow-400" style="width: 1%"></div>
                    </div>
                    <span class="w-10 text-sm text-right">1%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="tab-content hidden">
          <h3 class="text-2xl font-serif font-bold mb-6">Shipping & Returns</h3>
          <div class="prose max-w-none">
            <h4>Shipping Information</h4>
            <ul>
              <li>Free standard shipping on all orders</li>
              <li>Express shipping available at checkout</li>
              <li>Orders typically ship within 1-2 business days</li>
              <li>Delivery times vary by location (3-7 business days for standard shipping)</li>
              <li>International shipping available to most countries</li>
            </ul>
            
            <h4>Returns & Exchanges</h4>
            <ul>
              <li>30-day return policy for unused items</li>
              <li>Free returns for US customers</li>
              <li>Items must be in original condition with tags attached</li>
              <li>Refunds processed within 3-5 business days after return is received</li>
              <li>Exchanges available for size/color if inventory permits</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    
  </main>

  <?php include 'footer.php'; ?>

  <script src="product.js?v=1.0"></script>
  <script>
    // Thumbnail active border toggle
    function setActiveThumbnail(selectedImg) {
      const thumbnails = document.querySelectorAll('.thumbnail');
      thumbnails.forEach((img) => {
        img.classList.remove('border-indigo-600');
        img.classList.add('border-transparent');
      });
      selectedImg.classList.add('border-indigo-600');
    }
    // Star rating for reviews
    document.querySelectorAll('.far.fa-star').forEach(star => {
      star.addEventListener('click', function() {
        const stars = this.parentElement.querySelectorAll('i');
        const clickedIndex = Array.from(stars).indexOf(this);
        
        stars.forEach((s, index) => {
          if (index <= clickedIndex) {
            s.classList.remove('far');
            s.classList.add('fas');
          } else {
            s.classList.remove('fas');
            s.classList.add('far');
          }
        });
      });
    });
    // Tab functionality
    document.querySelectorAll('.tab-button').forEach(button => {
      button.addEventListener('click', () => {
        // Remove active class from all buttons and contents
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active', 'border-primary-500', 'text-primary-600'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        
        // Add active class to clicked button
        button.classList.add('active', 'border-primary-500', 'text-primary-600');
        
        // Show corresponding content
        const tabIndex = Array.from(document.querySelectorAll('.tab-button')).indexOf(button);
        document.querySelectorAll('.tab-content')[tabIndex].classList.remove('hidden');
      });
    });
  </script>
</body>
</html>
