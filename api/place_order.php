<?php
session_start();
require '../Admin/includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in as a user.']);
    exit();
}

// Get JSON input data
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order data.']);
    exit();
}

$userId = $_SESSION['user_id'];
$items = $data['items'];

try {
    $pdo->beginTransaction();

    // Calculate total amount
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$userId, $total]);
    $orderId = $pdo->lastInsertId();

    // Insert order items and update stock
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

    foreach ($items as $item) {
        // Check stock availability
        $stmtStock->execute([$item['quantity'], $item['id'], $item['quantity']]);
        if ($stmtStock->rowCount() === 0) {
            throw new Exception("Insufficient stock for product ID {$item['id']}");
        }

        $stmtItem->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'order_id' => $orderId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => 'Order failed: ' . $e->getMessage()]);
}
