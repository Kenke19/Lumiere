
  <!-- Navigation -->
  <nav class="bg-white shadow-md sticky top-0 z-50">
    <div
      class="container mx-auto px-4 py-3 flex justify-between items-center"
    >
      <div class="flex items-center space-x-2">
        <i class="fas fa-shopping-bag text-2xl text-indigo-600"></i>
        <span class="text-xl font-bold text-indigo-600">Lumière</span>
      </div>

      <div class="hidden md:flex space-x-8">
        <a href="index.php" class="text-gray-700 hover:text-indigo-600 font-medium"
          >Home</a
        >
        <a href="shop.php" class="text-gray-700 hover:text-indigo-600 font-medium"
          >Shop</a
        >
        <a href="categories.php" class="text-gray-700 hover:text-indigo-600 font-medium"
          >Categories</a
        >
        <a href="#" class="text-gray-700 hover:text-indigo-600 font-medium"
          >About</a
        >
        <a href="#" class="text-gray-700 hover:text-indigo-600 font-medium"
          >Contact</a
        >
        <a href="auth.php" class="text-gray-700 hover:text-indigo-600 font-medium"
          >Login</a
        >
      </div>

      <div class="flex items-center space-x-4">
        <button id="search-btn" class="text-gray-600 hover:text-indigo-600">
          <i class="fas fa-search"></i>
        </button>
        <button
          id="cart-btn"
          class="text-gray-600 hover:text-indigo-600 relative"
          aria-label="Open cart"
        >
          <i class="fas fa-shopping-cart"></i>
          <span
            id="cart-count"
            class="absolute -top-2 -right-2 bg-indigo-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
            >0</span
          >
        </button>
        <button class="md:hidden text-gray-600" id="mobile-menu-btn" aria-label="Toggle mobile menu">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div
      id="mobile-menu"
      class="hidden md:hidden bg-white py-2 px-4 shadow-lg"
      aria-label="Mobile menu"
    >
      <a href="index.php" class="block py-2 text-gray-700 hover:text-indigo-600"
        >Home</a
      >
      <a href="shop.php" class="block py-2 text-gray-700 hover:text-indigo-600"
        >Shop</a
      >
      <a href="categories.php" class="block py-2 text-gray-700 hover:text-indigo-600"
        >Categories</a
      >
      <a href="#" class="block py-2 text-gray-700 hover:text-indigo-600"
        >About</a
      >
      <a href="#" class="block py-2 text-gray-700 hover:text-indigo-600"
        >Contact</a
      >
      <a href="auth.php" class="block py-2 text-gray-700 hover:text-indigo-600"
        >Login</a
      >
    </div>

    <!-- Search Bar -->
<div id="search-bar" class="hidden bg-white py-3 px-4 shadow-inner">
  <div class="container mx-auto flex">
    <form action="shop.php" method="GET" class="flex w-full">
      <input
        type="text"
        name="search"
        id="search-input"
        placeholder="Search for products..."
        class="flex-grow px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
        aria-label="Search products"
        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
      />
      <button
        type="submit"
        id="search-submit"
        class="bg-indigo-600 text-white px-6 py-2 rounded-r-lg hover:bg-indigo-700 transition"
        aria-label="Search"
      >
        <i class="fas fa-search"></i>
      </button>
    </form>
  </div>
</div>
  </nav>
    <!-- Cart Sidebar -->
    <div
    id="cart-sidebar"
    class="fixed top-0 right-0 h-full w-full md:w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300 overflow-y-auto"
    aria-label="Shopping cart sidebar"
  >
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-bold">
          Your Cart (<span id="sidebar-cart-count">0</span>)
        </h3>
        <button id="close-cart" class="text-gray-500 hover:text-gray-700" aria-label="Close cart sidebar">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <div id="cart-items" class="mb-6" aria-live="polite" aria-atomic="true">
        <div id="empty-cart-message" class="text-center py-8">
          <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
          <p class="text-gray-500">Your cart is empty</p>
        </div>
      </div>

      <div id="cart-summary" class="border-t border-gray-200 pt-4 hidden">
        <div class="flex justify-between mb-2">
          <span class="text-gray-600">Subtotal:</span>
          <span id="cart-subtotal" class="font-bold">$0.00</span>
        </div>
        <div class="flex justify-between mb-4">
          <span class="text-gray-600">Shipping:</span>
          <span class="font-bold">Free</span>
        </div>
        <div class="flex justify-between text-lg font-bold mb-6">
          <span>Total:</span>
          <span id="cart-total">$0.00</span>
        </div>

        <button
          id="checkout-btn"
          class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition mb-4"
        >
          Proceed to Checkout
        </button>
        <button
          id="continue-shopping-btn"
          class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-bold hover:bg-gray-100 transition"
        >
          Continue Shopping
        </button>
      </div>
    </div>
  </div>

  <!-- Cart Overlay -->
  <div
    id="cart-overlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"
    aria-hidden="true"
  ></div>

  <script src="index.js?v=1.0"></script>