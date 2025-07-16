<?php
require 'includes/db.php';
require 'includes/auth.php';
requireAdmin();

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php');
    exit();
}

// Archive product
if (isset($_GET['archive'])) {
    $id = (int)$_GET['archive'];
    $stmt = $pdo->prepare("UPDATE products SET archived = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php');
    exit();
}

// Unarchive product
if (isset($_GET['unarchive'])) {
    $id = (int)$_GET['unarchive'];
    $stmt = $pdo->prepare("UPDATE products SET archived = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: products.php');
    exit();
}

// Fetch all products with category name
$stmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.image,
        p.status,
        p.archived,
        p.created_at,
        c.name AS category_name
    FROM
        products p
    LEFT JOIN
        categories c ON p.category_id = c.id
    ORDER BY
        p.created_at DESC
");
$stmtCount = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = $stmtCount->fetchColumn();
$products = $stmt->fetchAll();
// Collect product IDs
$productIds = array_column($products, 'id');

$imagesByProduct = [];
if ($productIds) {
    // Prepare placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // Query images, ordering main images first
    $stmtImages = $pdo->prepare("
        SELECT product_id, image_url 
        FROM product_images 
        WHERE product_id IN ($placeholders)
        ORDER BY is_main DESC, id ASC
    ");
    $stmtImages->execute($productIds);
    $images = $stmtImages->fetchAll();

    // Map images per product
    foreach ($images as $img) {
        $imagesByProduct[$img['product_id']][] = $img['image_url'];
    }
}

// Attach first image url or fallback placeholder to each product
foreach ($products as &$product) {
    $pid = $product['id'];
    if (!empty($imagesByProduct[$pid])) {
        $product['display_image'] = $imagesByProduct[$pid][0];
    } else {
        $product['display_image'] = 'uploads/products/placeholder.jpg'; // or your placeholder path
    }
}
unset($product); // break the reference

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Products Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #e2e8f0 transparent;
        }
        .scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 6px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-menu {
            animation: fadeIn 0.2s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:pl-72 overflow-auto">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Products</h2>
                    <div class="text-gray-600 font-semibold bg-gray-100 px-4 py-2 mt-4 rounded">
                        Total Products: <?= htmlspecialchars($totalProducts) ?>
                    </div>
                </div>

                <!-- Filter Buttons and Add Product -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="flex flex-wrap items-center gap-3" id="filter-buttons">
                        
                    <button class="px-3 py-1.5 rounded-lg bg-phoenix-primary text-blue text-grey-700 font-medium hover:bg-gray-50 filter-btn" data-filter="all">
                            <i class="fas fa-filter text-xs mr-1"></i> All products
                        </button>
                        <button class="px-3 py-1.5 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 filter-btn" data-filter="archived">
                            <i class="fas fa-archive text-xs mr-1"></i> Archived
                        </button>
                    </div>
                    <div>
                        <a href="add_product.php" class="px-4 py-2 rounded-lg bg-phoenix-primary text-blue text-sm font-medium hover:bg-blue-600 transition">
                            <i class="fas fa-plus mr-2"></i> Add Product
                        </a>
                    </div>
                </div>

                <!-- Search and Export -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <div class="relative flex-grow max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="search-products" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-phoenix-primary focus:border-phoenix-primary" placeholder="Search products...">
                    </div>
                    <div class="ml-auto">
                        <button class="text-gray-700 hover:text-gray-900 text-sm font-medium" id="export-btn">
                            <i class="fas fa-file-export text-xs mr-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto scrollbar">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all" class="h-4 w-4 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary">
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Product Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Price</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Stock</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="products-table-body">
                                <?php foreach ($products as $p): ?>
                                <tr class="hover:bg-gray-50 product-row" data-archived="<?= htmlspecialchars($p['archived']) ?>" data-search="<?= htmlspecialchars($p['name'] . ' ' . $p['category_name']) ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="product-checkbox h-4 w-4 text-phoenix-primary border-gray-300 rounded focus:ring-phoenix-primary">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 flex items-center gap-3">
                                        <img src="/E-shop/<?= htmlspecialchars($p['display_image'])?>" alt="Product Image" class="w-10 h-10 object-cover rounded-md" />
                                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="hover:underline"><?= htmlspecialchars($p['name']) ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?= number_format($p['price'], 2) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($p['stock']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="edit_product.php?id=<?= $p['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if ($p['archived']): ?>
                                            <a href="products.php?unarchive=<?= $p['id'] ?>" class="text-green-600 hover:text-green-900 mr-2" title="Unarchive">
                                                <i class="fas fa-box-open"></i> Unarchive
                                            </a>
                                        <?php else: ?>
                                            <a href="products.php?archive=<?= $p['id'] ?>" class="text-gray-600 hover:text-gray-900 mr-2" title="Archive" >
                                                <i class="fas fa-archive"></i> Archive
                                            </a>
                                        <?php endif; ?>
                                        <a href="products.php?delete=<?= $p['id'] ?>" class="text-red-600 hover:text-red-900" title="Delete" ><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                <tr><td colspan="6" class="text-center py-4">No products found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = btn.getAttribute('data-filter');
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('bg-phoenix-primary', 'text-blue-600'));
            btn.classList.add('bg-phoenix-primary', 'text-grey');
            document.querySelectorAll('.product-row').forEach(row => {
                if (filter === 'all' || (filter === 'archived' && row.getAttribute('data-archived') === '1')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Search products
    const searchInput = document.getElementById('search-products');
    searchInput.addEventListener('input', function() {
        const val = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('.product-row').forEach(row => {
            const text = row.getAttribute('data-search').toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    });

    // Select all checkboxes
    const selectAll = document.getElementById('select-all');
    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = selectAll.checked);
    });

    // Export CSV (basic)
    document.getElementById('export-btn').addEventListener('click', function(e) {
        e.preventDefault();
        let csv = '';
        document.querySelectorAll('table thead tr th').forEach(th => {
            if (th.innerText.trim() !== '') csv += `"${th.innerText.trim()}",`;
        });
        csv = csv.slice(0, -1) + '\n';
        document.querySelectorAll('table tbody tr').forEach(tr => {
            if (tr.style.display === 'none') return; 
            tr.querySelectorAll('td').forEach((td, i) => {
                if (i === 1) {
                    const a = td.querySelector('a');
                    csv += `"${a ? a.innerText.trim() : td.innerText.trim()}",`;
                } else if (i !== 0 && i !== tr.children.length - 1) {
                    csv += `"${td.innerText.trim()}",`;
                }
            });
            csv = csv.slice(0, -1) + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'products.csv';
        a.click();
        URL.revokeObjectURL(url);
    });
    </script>
</body>
</html>
