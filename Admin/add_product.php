<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $name = trim($_POST['name']);
    $category_id = trim($_POST['category_id']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = trim($_POST['description']);

    if (!$name || !$category_id || !is_numeric($price) || !is_numeric($stock)) {
        $error = 'Please fill all required fields correctly.';
    } else {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ? AND category_id = ?");
        $stmtCheck->execute([$name, $category_id]);
        $count = $stmtCheck->fetchColumn();
        if ($count > 0) {
            $error = 'Product with this name already exists in the selected category.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $price, $stock, $description]);
            $productId = $pdo->lastInsertId();

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/E-shop/uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $uploadedImages = 0;
            if (isset($_FILES['images'])) {
                $files = $_FILES['images'];
                $fileCount = min(count($files['name']), 3);

                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $originalName = basename($files['name'][$i]);
                        $safeName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                        $targetPath = $uploadDir . $safeName;

                        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                            $imagePath = 'uploads/products/' . $safeName;
                            $isMain = ($uploadedImages === 0) ? 1 : 0;
                            $stmtImg = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, ?)");
                            $stmtImg->execute([$productId, $imagePath, $isMain]);
                            $uploadedImages++;
                        }
                    }
                }
            }

            if ($uploadedImages === 0) {
                $placeholder = 'uploads/products/placeholder.jpg';
                $stmtUpdate = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
                $stmtUpdate->execute([$placeholder, $productId]);
            }

            $success = 'Product and images added successfully.';
        }
    }

    if ($error) {
        echo json_encode(['error' => $error]);
    } else {
        echo json_encode(['success' => $success]);
    }
    exit;  
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lumiere | Add Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .form-input {
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
        }
        .form-input:focus {
            outline: none;
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.5);
        }
        .file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 md:pl-64">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Product Information</h2>
                    <div class="flex space-x-3">
                        <button onclick="window.location.href='products.php'" type="button" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Discard
                        </button>
                        <button form="addProductForm" type="submit" class="px-4 py-2 bg-blue-600 rounded-md text-sm font-medium text-white hover:bg-blue-700">
                            Save Product
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="p-6 overflow-auto">
                <div id="messageContainer"></div>

                <form id="addProductForm" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Basic Information Card -->
                            <div class="card p-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                                        <input type="text" name="name" id="name" class="form-input" placeholder="Enter product name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
                                    </div>
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                        <textarea name="description" id="description" rows="4" class="form-input" placeholder="Enter product description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Card -->
                            <div class="card p-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Pricing</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                                        <input type="number" step="0.01" min="0" name="price" id="price" class="form-input" placeholder="0.00" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" />
                                    </div>
                                    <div>
                                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                                        <input type="number" min="0" name="stock" id="stock" class="form-input" placeholder="0" required value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>" />
                                    </div>
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                        <select name="category_id" id="category_id" class="form-input" required>
                                            <option value="">Select category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Image Upload Card -->
                            <div class="card p-6">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Product Images</h3>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center relative cursor-pointer">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                    <label for="images" class="absolute inset-0 flex flex-col items-center justify-center text-blue-600 font-medium text-sm hover:text-blue-500">
                                        <span>Click to upload or drag and drop</span>
                                        <input type="file" name="images[]" id="images" accept="image/*" multiple class="file-input" />
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">You can upload up to 3 images. PNG, JPG, GIF up to 10MB.</p>
                            </div>
                            <div id="imagePreview" class="mt-4 flex space-x-4"></div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    <script>
const input = document.getElementById('images');
const previewContainer = document.getElementById('imagePreview');
const messageContainer = document.getElementById('messageContainer');
let selectedFiles = [];

function updatePreview() {
  previewContainer.innerHTML = '';
  const maxFiles = 3;
  for (let i = 0; i < Math.min(selectedFiles.length, maxFiles); i++) {
    const file = selectedFiles[i];
    if (!file.type.startsWith('image/')) continue;

    const reader = new FileReader();
    reader.onload = (e) => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = file.name;
      img.className = 'h-20 w-20 object-cover rounded-md border border-gray-300 shadow-sm cursor-pointer';
      previewContainer.appendChild(img);
    };
    reader.readAsDataURL(file);
  }
}

input.addEventListener('change', () => {
  for (const file of input.files) {
    if (selectedFiles.length < 3) {
      selectedFiles.push(file);
    }
  }
  input.value = ''; // reset to allow re-selection
  updatePreview();
});

previewContainer.addEventListener('click', event => {
  if (event.target.tagName === 'IMG') {
    const index = Array.from(previewContainer.children).indexOf(event.target);
    if (index > -1) {
      selectedFiles.splice(index, 1);
      updatePreview();
    }
  }
});

// Override form submission to send all selectedFiles (not just native input.files)
document.getElementById('addProductForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  // Replace files in formData with selectedFiles
  formData.delete('images[]'); // remove old empty input files if any
  selectedFiles.forEach(file => formData.append('images[]', file));

  fetch(this.action || '', {
    method: 'POST',
    body: formData,
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
        // Redirect after a short delay
        setTimeout(() => {
          window.location.href = 'products.php';
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
