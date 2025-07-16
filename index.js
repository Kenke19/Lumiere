let products = [];

// DOM Elements
const productsContainer = document.getElementById('products-container');
const cartBtn = document.getElementById('cart-btn');
const closeCartBtn = document.getElementById('close-cart');
const cartSidebar = document.getElementById('cart-sidebar');
const cartOverlay = document.getElementById('cart-overlay');
const cartItemsContainer = document.getElementById('cart-items');
const emptyCartMessage = document.getElementById('empty-cart-message');
const cartSummary = document.getElementById('cart-summary');
const cartCount = document.getElementById('cart-count');
const sidebarCartCount = document.getElementById('sidebar-cart-count');
const cartSubtotal = document.getElementById('cart-subtotal');
const cartTotal = document.getElementById('cart-total');
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
const searchBtn = document.getElementById('search-btn');
const searchBar = document.getElementById('search-bar');
const checkoutBtn = document.getElementById('checkout-btn');
const continueShoppingBtn = document.getElementById('continue-shopping-btn');

// Load products from API
async function loadProducts(searchQuery = '') {
  try {
    let url = '/E-shop/api/products.php';
    if (searchQuery) {
      url += `?search=${encodeURIComponent(searchQuery)}`;
    }
    const response = await fetch(url);
    if (!response.ok) throw new Error('Failed to load products');
    products = await response.json();
    renderProducts(products);
  } catch (err) {
    console.error('Error loading products:', err);
    productsContainer.innerHTML =
      '<div class="text-center text-red-500">Failed to load products.</div>';
  }
}


// Render rating stars (returns HTML string)
function renderRatingStars(rating) {
  let stars = '';
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;
  for (let i = 0; i < fullStars; i++) {
    stars += '<i class="fas fa-star text-yellow-400"></i>';
  }
  if (hasHalfStar) {
    stars += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
  }
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
  for (let i = 0; i < emptyStars; i++) {
    stars += '<i class="far fa-star text-yellow-400"></i>';
  }
  return stars;
}

// Render products
function renderProducts(products) {
  productsContainer.innerHTML = '';

  products.forEach((product) => {
    const productCard = document.createElement('div');
    productCard.className =
      'product-card bg-white rounded-lg overflow-hidden shadow-md transition duration-300';
    productCard.innerHTML = `
            <div class="relative">
                <img src="${product.image}" alt="${product.name}" class="w-full h-48 object-cover" />
                <div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">NEW</div>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg">${product.name}</h3>
                    <span class="text-indigo-600 font-bold">$${parseFloat(
                      product.price
                    ).toFixed(2)}</span>
                </div>
                <div class="flex items-center mb-2">
                    <span class="text-gray-500 text-sm">${product.category_name}</span>
                </div>
                <div class="flex items-center mb-3">
                    ${renderRatingStars(product.rating || 0)}
                </div>
                <button class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition font-medium add-to-cart" data-id="${product.id}" aria-label="Add ${product.name} to cart">
                    Add to Cart
                </button>
            </div>`;
    productsContainer.appendChild(productCard);
  });

  // Add event listeners to "Add to Cart" buttons
  document.querySelectorAll('.add-to-cart').forEach((button) => {
    button.addEventListener('click', async (e) => {
      const productId = parseInt(e.target.getAttribute('data-id'));
      await addToCartAPI(productId);
    });
  });
}

// Add to Cart API
async function addToCartAPI(productId, quantity = 1) {
  const res = await fetch('/E-shop/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ productId, quantity }),
  });
  const data = await res.json();
  if (!data.success) {
    alert('Failed to add to cart');
  } else {
    await loadCart();
  }
}

// Load Cart and Render Cart Sidebar
async function loadCart() {
  const res = await fetch('/E-shop/api/cart.php');
  const data = await res.json();
  if (!data.success) return;

  const { cartItems, cartCount } = data;
  updateCartCount(cartCount);

  if (cartItems.length === 0) {
    cartItemsContainer.innerHTML = `
      <div class="text-center py-8 text-gray-500">
        <i class="fas fa-shopping-cart text-4xl mb-4"></i>
        Your cart is empty
      </div>`;
    cartSummary.classList.add('hidden');
    emptyCartMessage.classList.remove('hidden');
    return;
  }

  emptyCartMessage.classList.add('hidden');
  cartSummary.classList.remove('hidden');

  cartItemsContainer.innerHTML = '';
  let subtotal = 0;

  cartItems.forEach((item) => {
    const priceNum = parseFloat(item.price);
    subtotal += priceNum * item.quantity;

    const cartItem = document.createElement('div');
    cartItem.className = 'flex py-4 border-b border-gray-200 cart-item';
    cartItem.dataset.id = item.product_id;
    cartItem.innerHTML = `
      <div class="w-20 h-20 bg-gray-100 rounded overflow-hidden mr-4">
        <img src="${item.image}" alt="${item.name}" class="w-full h-full object-cover" />
      </div>
      <div class="flex-grow">
        <h4 class="font-medium">${item.name}</h4>
        <p class="text-gray-600">$${priceNum.toFixed(2)} x <span class="item-quantity">${item.quantity}</span></p>
        <div class="flex items-center mt-2 space-x-2">
          <button class="quantity-btn bg-gray-200 px-2 rounded" data-action="decrease" aria-label="Decrease quantity">-</button>
          <span>${item.quantity}</span>
          <button class="quantity-btn bg-gray-200 px-2 rounded" data-action="increase" aria-label="Increase quantity">+</button>
        </div>
      </div>
      <div class="flex flex-col items-end">
        <span class="font-bold">$${(priceNum * item.quantity).toFixed(2)}</span>
        <button class="remove-btn text-red-500 mt-2" aria-label="Remove item">&times;</button>
      </div>
    `;
    cartItemsContainer.appendChild(cartItem);
  });

  cartSubtotal.textContent = `$${subtotal.toFixed(2)}`;
  cartTotal.textContent = `$${subtotal.toFixed(2)}`;
}

//cart count
function updateCartCount(count) {
  cartCount.textContent = count;
  sidebarCartCount.textContent = count;
}

//quantity and remove buttons
cartItemsContainer.addEventListener('click', async (e) => {
  const target = e.target;
  const cartItemElem = target.closest('.cart-item');
  if (!cartItemElem) return;

  const productId = cartItemElem.dataset.id;

  if (target.closest('.quantity-btn')) {
    const action = target.closest('.quantity-btn').dataset.action;
    await updateCartQuantity(productId, action);
  } else if (target.closest('.remove-btn')) {
    await removeFromCart(productId);
  }
});

//quantity API call (PUT)
async function updateCartQuantity(productId, action) {
  const res = await fetch('/E-shop/api/cart.php');
  const data = await res.json();
  if (!data.success) return;

  const item = data.cartItems.find((i) => i.product_id == productId);
  if (!item) return;

  let newQuantity = item.quantity;
  if (action === 'increase') newQuantity++;
  else if (action === 'decrease') newQuantity--;

  if (newQuantity < 0) return; 

  // Call PUT API to update quantity
  const putRes = await fetch('/E-shop/api/cart.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `productId=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(newQuantity)}`,
  });
  const putData = await putRes.json();
  if (putData.success) {
    await loadCart();
  }
}

// Remove item API call
async function removeFromCart(productId) {
  const res = await fetch(`/E-shop/api/cart.php?productId=${encodeURIComponent(productId)}`, {
    method: 'DELETE',
  });
  const data = await res.json();
  if (data.success) {
    await loadCart();
  }
}

// Checkout button click handler
checkoutBtn.addEventListener('click', async () => {
  try {
    const res = await fetch('/E-shop/api/check_login.php');
    if (!res.ok) throw new Error('Network response not ok');

    const data = await res.json();
    console.log('Login check response:', data);

    if (data.loggedIn) {
      window.location.href = '/E-shop/api/checkout.php';
    } else {
    
      window.location.href = '/E-shop/auth.php?redirect=/E-shop/api/checkout.php';
    }
  } catch (err) {
    console.error('Error checking login:', err);
    alert('Please login to proceed to checkout.');
    window.location.href = '/E-shop/auth.php?redirect=/E-shop/api/checkout.php';
  }
});




// Continue shopping button closes cart sidebar
continueShoppingBtn.addEventListener('click', () => {
  closeCartSidebar();
});

// UI Event Listeners for Cart Sidebar and Overlay
cartBtn.addEventListener('click', () => {
  cartSidebar.classList.remove('translate-x-full');
  cartOverlay.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
});
closeCartBtn.addEventListener('click', closeCartSidebar);
cartOverlay.addEventListener('click', closeCartSidebar);

function closeCartSidebar() {
  cartSidebar.classList.add('translate-x-full');
  cartOverlay.classList.add('hidden');
  document.body.style.overflow = 'auto';
}

// Mobile menu toggle
mobileMenuBtn.addEventListener('click', () => {
  mobileMenu.classList.toggle('hidden');
});

// Countdown timer for special offer
function updateCountdown() {
  const now = new Date();
  const endDate = new Date();
  endDate.setDate(now.getDate() + 2); // 2 days from now

  const diff = endDate - now;
  const days = Math.floor(diff / (1000 * 60 * 60 * 24));
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((diff % (1000 * 60)) / 1000);

  document.getElementById('days').textContent = days.toString().padStart(2, '0');
  document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
  document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
  document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
}
// Search functionality

if (searchBtn && searchBar) {
    searchBtn.addEventListener('click', () => {
      searchBar.classList.toggle('hidden');

      // Focus input when shown
      if (!searchBar.classList.contains('hidden')) {
        document.getElementById('search-input').focus();
      }
    });
  }

// Initialize
loadProducts();
loadCart();
updateCountdown();
setInterval(updateCountdown, 1000);
