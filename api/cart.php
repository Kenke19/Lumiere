<?php
session_start();
header('Content-Type: application/json');
require_once '../Admin/includes/db.php';

$response = ['success' => false, 'message' => 'Initial state'];
$cartToken = null;
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // 1. Determine the cart token
    if (isset($_COOKIE['cart_token'])) {
        $cartToken = $_COOKIE['cart_token'];
    } elseif ($userId) {
        // Get user's cart token from database if exists
        $stmt = $pdo->prepare("SELECT cart_token FROM carts WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        if ($cart) {
            $cartToken = $cart['cart_token'];
            setcookie('cart_token', $cartToken, time() + 60*60*24*30, '/', '', true, true);
        }
    }

    // 2. If no cart token exists, create new cart
    if (!$cartToken) {
        $cartToken = bin2hex(random_bytes(32));
        setcookie('cart_token', $cartToken, time() + 60*60*24*30, '/', '', true, true);
        
        // Insert new cart record
        $stmt = $pdo->prepare("INSERT INTO carts (cart_token, user_id) VALUES (?, ?)");
        $stmt->execute([$cartToken, $userId]);
        $cartId = $pdo->lastInsertId();
    } else {
        // 3. Get existing cart ID
        $stmt = $pdo->prepare("SELECT id FROM carts WHERE cart_token = ? LIMIT 1");
        $stmt->execute([$cartToken]);
        $cart = $stmt->fetch();
        
        if (!$cart) {
            // Create cart if it doesnt exist
            $stmt = $pdo->prepare("INSERT INTO carts (cart_token, user_id) VALUES (?, ?)");
            $stmt->execute([$cartToken, $userId]);
            $cartId = $pdo->lastInsertId();
        } else {
            $cartId = $cart['id'];
            
            // 4. If user is logged in, ensure cart is linked to user
            if ($userId) {
                $stmt = $pdo->prepare("UPDATE carts SET user_id = ? WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
                $stmt->execute([$userId, $cartId, $userId]);
            }
        }
    }

    // 5. Handle different HTTP methods
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($method) {
        case 'POST':
            // Add item to cart
            $productId = intval($input['productId'] ?? 0);
            $quantity = intval($input['quantity'] ?? 1);
            
            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product or quantity');
            }
            // Fetch product stock to check availability
            $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtStock->execute([$productId]);
            $productStock = $stmtStock->fetchColumn();

            if ($productStock === false) {
                throw new Exception('Product not found');
            }
            // Check existing item in cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
            $stmt->execute([$cartId, $productId]);
            $item = $stmt->fetch();

            $currentQty = $item['quantity'] ?? 0;
            $newQty = $currentQty + $quantity;

            if ($newQty > $productStock) {
                throw new Exception("Cannot add $quantity items. Only $productStock in stock, you already have $currentQty.");
            }

            // Add/update cart item
            if ($item) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                $stmt->execute([$newQty, $item['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$cartId, $productId, $quantity]);
            }

            $response = ['success' => true];
            break;

        case 'PUT':
            // Update item quantity
            $productId = intval($input['productId'] ?? 0);
            $quantity = intval($input['quantity'] ?? 0);
            
            if ($productId <= 0 || $quantity < 0) {
                throw new Exception('Invalid product or quantity');
            }
            // Fetch product stock to check availability
            $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtStock->execute([$productId]);
            $productStock = $stmtStock->fetchColumn();

            if ($productStock === false) {
                throw new Exception('Product not found');
            }

            if ($quantity > $productStock) {
                throw new Exception("Cannot update quantity. Only $productStock items in stock.");
            }

            if ($quantity === 0) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $stmt->execute([$cartId, $productId]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $cartId, $productId]);
            }

            $response = ['success' => true];
            break;

        case 'GET':
            // Get cart contents
            $stmt = $pdo->prepare("
                SELECT ci.product_id, ci.quantity, p.name, p.price, p.image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = ?
            ");
            $stmt->execute([$cartId]);
            $items = $stmt->fetchAll();

            $totalQty = array_sum(array_column($items, 'quantity'));
            
            $response = [
                'success' => true,
                'cartCount' => $totalQty,
                'cartItems' => $items
            ];
            break;

        case 'DELETE':
            // Clear cart
            if (isset($_GET['productId'])) {
                $productId = intval($_GET['productId']);
                if ($productId > 0) {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
                    $stmt->execute([$cartId, $productId]);
                    $response = ['success' => true];
                    break;
                }
            }

            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cartId]);
            $response = ['success' => true];
            break;

        default:
            throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);