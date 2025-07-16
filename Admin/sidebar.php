<head>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-md transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
  <!-- Sidebar Header -->
  <div class="p-4 border-b border-gray-200">
    <div class="flex items-center space-x-2">
      <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
        L
      </div>
      <span class="text-xl mt-1 mb-2 font-bold text-gray-800">Lumiere</span>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="mt-6 px-2 overflow-y-auto h-[calc(100vh-96px)]">
    <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-600 mb-1">
      <i class="fas fa-chart-pie mr-3 text-blue-500"></i>
      Dashboard
    </a>
    <hr>
    <a href="add_product.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 mb-1">
      <i class="fas fa-plus-circle mr-3 text-gray-500"></i>
      Add product
    </a>
    <hr>
    <a href="products.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
      <i class="fas fa-box-open mr-3 text-gray-500"></i>
      Products
    </a>
    <hr>
    <a href="manage.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
        <i class="fas fa-tags mr-3 text-gray-500"></i>
        Management
    </a>
    <hr>
    <a href="orders.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
        <i class="fas fa-shopping-bag mr-3 text-gray-500"></i>
        Orders
    </a>
    <hr>
    <a href="order_detail.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
        <i class="fas fa-shopping-cart mr-3 text-gray-500"></i>
        Order details
    </a>
    <hr>
    <a href="customers.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
        <i class="fas fa-users mr-3 text-gray-500"></i>
        Customers
    </a>
    <hr>
    <a href="customer_details.php" class="flex items-center mt-2 mb-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">
        <i class="fas fa-users mr-3 text-gray-500"></i>
        Customer details
    </a>
  </nav>
</aside>

<header class="flex items-center justify-between bg-white shadow-sm px-6 py-4 md:pl-72">
  <!-- Mobile menu toggle -->
  <button id="sidebarToggle" class="md:hidden text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-md">
    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
  </button>

  <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>

  <div class="flex items-center space-x-4">
    <button class="p-2 rounded-full hover:bg-gray-100">
      <i class="far fa-bell text-gray-500"></i>
    </button>
    <div class="flex items-center space-x-2">
      <span class="text-sm font-medium">Admin</span>
    </div>
  </div>
</header>
<hr>

<script>
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('-translate-x-full');
  });

  // Optional: Hide sidebar when clicking outside on mobile
  document.addEventListener('click', (event) => {
    const isClickInsideSidebar = sidebar.contains(event.target);
    const isClickOnToggleBtn = toggleBtn.contains(event.target);

    if (!isClickInsideSidebar && !isClickOnToggleBtn && !sidebar.classList.contains('-translate-x-full')) {
      sidebar.classList.add('-translate-x-full');
    }
  });
</script>
