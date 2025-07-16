<?php
header('Content-Type: application/json');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../Admin/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); 
    echo json_encode(['error' => 'Method not allowed. Only GET requests are supported.']);
    exit();
}

try {
    $searchQuery = $_GET['search'] ?? '';
    $searchQuery = trim($searchQuery);

    if ($searchQuery !== '') {
        // Search products by name, description, or category name
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, p.category_id, p.image, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0 AND (
                p.name LIKE :search OR 
                p.description LIKE :search OR 
                c.name LIKE :search
            )
            ORDER BY p.created_at DESC
        ");
        $likeSearch = '%' . $searchQuery . '%';
        $stmt->bindValue(':search', $likeSearch, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        //return all products
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.price, p.category_id, p.image, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock > 0
            ORDER BY p.created_at DESC
        ");
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productIds = array_column($products, 'id');
    if ($productIds) {
        // Fetch product images for the found products
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmtImages = $pdo->prepare("
            SELECT product_id, image_url 
            FROM product_images 
            WHERE product_id IN ($placeholders)
            ORDER BY is_main DESC, id ASC
        ");
        $stmtImages->execute($productIds);
        $imagesData = $stmtImages->fetchAll(PDO::FETCH_ASSOC);
        
        // Add images to each product
        $imagesByProduct = [];
        foreach ($imagesData as $img) {
            $imagesByProduct[$img['product_id']][] = $img['image_url'];
        }
        foreach ($products as &$product) {
            $pid = $product['id'];
            $product['images'] = $imagesByProduct[$pid] ?? [];
        }
        unset($product); // Break reference
    }
    echo json_encode($products);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500); 
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
