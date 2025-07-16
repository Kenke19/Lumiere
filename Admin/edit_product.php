<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Fetch product data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Fetch product images
$stmtImages = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
$stmtImages->execute([$id]);
$productImages = $stmtImages->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = trim($_POST['description']);

    $error = '';

    if (!$name || !$category_id || !is_numeric($price) || !is_numeric($stock)) {
        $error = 'Please fill all required fields correctly.';
    } else {
        // Update product basic info
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $category_id, $price, $stock, $description, $id]);

        // Handle uploaded images
        if (isset($_FILES['images'])) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/E-shop/uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $files = $_FILES['images'];
            $fileCount = min(count($files['name']), 3);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $originalName = basename($files['name'][$i]);
                    $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                    $targetPath = $uploadDir . $safeName;

                    if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                        $imagePath = 'uploads/products/' . $safeName;

                        $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, 0)");
                        $stmtImg->execute([$id, $imagePath]);
                    }
                }
            }
        }
    }

    if ($error) {
        echo json_encode(['error' => $error]);
    } else {
        echo json_encode(['success' => 'Product updated successfully.']);
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Product - Lumière Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    /* Optional: preview images container style */
    #imagePreview img { max-width: 80px; max-height: 80px; border-radius: 8px; border: 1px solid #ccc; margin-right: 0.5rem; }
  </style>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen flex flex-col">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 md:pl-64">

      <main class="p-6 max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Edit Product</h1>
        <a href="products.php" class="inline-block mb-6 text-blue-600 hover:underline">
          <i class="fas fa-arrow-left mr-2"></i>Back to Products
        </a>

        <form id="editProductForm" method="POST" enctype="multipart/form-data" class="space-y-6">
          <div>
            <label class="block font-medium mb-1" for="name">Product Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" required class="w-full p-3 border rounded" value="<?= htmlspecialchars($product['name']) ?>" />
          </div>

          <div>
            <label class="block font-medium mb-1" for="category_id">Category <span class="text-red-500">*</span></label>
            <select name="category_id" id="category_id" required class="w-full p-3 border rounded">
              <option value="">Select category</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $product['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block font-medium mb-1" for="price">Price ($) <span class="text-red-500">*</span></label>
            <input type="number" name="price" id="price" step="0.01" min="0" required class="w-full p-3 border rounded" value="<?= htmlspecialchars($product['price']) ?>" />
          </div>

          <div>
            <label class="block font-medium mb-1" for="stock">Stock Quantity <span class="text-red-500">*</span></label>
            <input type="number" name="stock" id="stock" min="0" required class="w-full p-3 border rounded" value="<?= htmlspecialchars($product['stock']) ?>" />
          </div>

          <div>
            <label class="block font-medium mb-1" for="description">Description</label>
            <textarea name="description" id="description" rows="5" class="w-full p-3 border rounded"><?= htmlspecialchars($product['description']) ?></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Current Product Images</label>
            <?php if ($productImages): ?>
              <div class="flex flex-wrap gap-4 mb-4">
                <?php foreach ($productImages as $img): ?>
                  <img src="/E-shop/<?= htmlspecialchars($img['image_url']) ?>" alt="Product Image" class="w-32 h-32 object-cover rounded" />
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p>No images uploaded yet.</p>
            <?php endif; ?>
          </div>

          <div>
            <label class="block font-medium mb-1" for="images">Add More Images (Max 3)</label>
            <input type="file" name="images[]" id="images" accept="image/*" multiple class="border p-2 w-full rounded" />
            <small class="text-gray-500">Allowed formats: JPG, PNG, GIF. Max size 10MB each.</small>
            <div id="imagePreview" class="flex mt-2 gap-2"></div>
          </div>
              
        <div id="messageContainer"></div>
          <button type="submit" class="bg-blue-600 text-white py-3 px-6 rounded hover:bg-blue-700 font-semibold">
            <i class="fas fa-save mr-2"></i> Update Product
          </button>
        </form>
      </main>
    </div>
  </div>

<script>
const input = document.getElementById('images');
const previewContainer = document.getElementById('imagePreview');
let selectedFiles = [];

function updatePreview() {
  previewContainer.innerHTML = '';
  selectedFiles.slice(0, 3).forEach(file => {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = file.name;
      img.className = 'w-20 h-20 object-cover rounded border mr-2';
      previewContainer.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

input.addEventListener('change', () => {
  // Append new files (max total 3)
  for (const file of input.files) {
    if (selectedFiles.length < 3) {
      selectedFiles.push(file);
    }
  }
  updatePreview();

  // Reset input so same file can be reselected if needed
  input.value = '';
});
previewContainer.addEventListener('click', event => {
  if (event.target.tagName === 'IMG') {
    const index = Array.from(previewContainer.children).indexOf(event.target);
    if (index > -1) {
      selectedFiles.splice(index, 1); // remove clicked file
      updatePreview();
    }
  }
});
const form = document.getElementById('editProductForm');
const messageContainer = document.getElementById('messageContainer');

form.addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.delete('images[]');
  selectedFiles.forEach(file => formData.append('images[]', file));

  fetch(this.action || '', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message
        messageContainer.innerHTML = `
          <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
            ${data.success}
          </div>
        `;
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else if (data.error) {
        // Show error message
        messageContainer.innerHTML = `
          <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            ${data.error}
          </div>
        `;
      }
    })
    .catch(() => {
      messageContainer.innerHTML = `
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
          Network error, please try again.
        </div>
      `;
    });
});

</script>

</body>
</html>
