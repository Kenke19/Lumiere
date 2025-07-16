document.addEventListener('DOMContentLoaded', () => {
  const addToCartBtn = document.getElementById('add-to-cart-btn');
  const quantityInput = document.getElementById('quantity');
  const messageBox = document.getElementById('add-to-cart-message');
  const mainImage = document.getElementById('mainImage');
  const thumbnails = document.querySelectorAll('.thumbnail');

  if (!addToCartBtn || !quantityInput || !messageBox) return;

  // Add to Cart button click handler
  addToCartBtn.addEventListener('click', async () => {
    let qty = parseInt(quantityInput.value, 10) || 1;
    const maxStock = parseInt(quantityInput.max, 10);

    if (qty > maxStock) {
      qty = maxStock;
      quantityInput.value = maxStock;
      // Show user feedback
      messageBox.textContent = `Only ${maxStock} in stock!`;
      messageBox.classList.remove('hidden');
      setTimeout(() => {
        messageBox.classList.add('hidden');
        messageBox.textContent = '';
      }, 3500);
      return; // Don't send request if invalid
    }
    if (qty < 1) {
      qty = 1;
      quantityInput.value = 1;
      return;
    }

    const productId = addToCartBtn.dataset.productId;

    try {
      const res = await fetch('/E-shop/api/cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ productId, quantity: qty }),
      });
      const data = await res.json();
      if (data.success) {
        if (typeof loadCart === 'function') loadCart();
        messageBox.textContent = 'Product added successfully!';
        messageBox.classList.remove('hidden');
        setTimeout(() => {
          messageBox.classList.add('hidden');
          messageBox.textContent = '';
        }, 3000);
      } else {
        messageBox.textContent = data.error || 'Quantity exceeds available items.';
        messageBox.classList.remove('hidden');
        setTimeout(() => {
          messageBox.classList.add('hidden');
          messageBox.textContent = '';
        }, 4000);
      }
    } catch (error) {
      messageBox.textContent = 'An error occurred while adding product to cart.';
      messageBox.classList.remove('hidden');
      setTimeout(() => {
        messageBox.classList.add('hidden');
        messageBox.textContent = '';
      }, 4000);
    }
  });

  // Thumbnail click handler to update main image and active border
  if (mainImage && thumbnails.length) {
    thumbnails.forEach((thumb) => {
      thumb.addEventListener('click', () => {
        mainImage.src = thumb.src;
        thumbnails.forEach(t => t.classList.remove('border-indigo-500'));
        thumb.classList.add('border-indigo-500');
      });
    });
  }

  // Option selectors (color and size)
  document.querySelectorAll('.option-selector').forEach(selector => {
    selector.addEventListener('click', () => {
      document.querySelectorAll('.option-selector').forEach(el => el.classList.remove('selected', 'border-primary-500'));
      selector.classList.add('selected', 'border-primary-500');
    });
  });

  // Quantity selector (if you have + / - buttons with class 'quantity-btn')
  document.querySelectorAll('.quantity-btn').forEach(button => {
    button.addEventListener('click', () => {
      let value = parseInt(quantityInput.value, 10) || 1;
      const max = parseInt(quantityInput.max, 10);
      const min = parseInt(quantityInput.min, 10);

      if (button.textContent.includes('+')) {
        if (value < max) quantityInput.value = value + 1;
      } else {
        if (value > min) quantityInput.value = value - 1;
      }
    });
  });
});
