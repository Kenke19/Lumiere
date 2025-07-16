<?php
require '../Admin/includes/db.php';
require '../Admin/includes/auth.php';
requireAdmin();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

$period = $_GET['period'] ?? 'daily';

// Prepare date filters based on period for orders table
switch ($period) {
    case 'weekly':
        $orderDateInterval = '8 WEEK';
        $orderGroupBy = "YEAR(order_date), WEEK(order_date, 1)";
        $orderDateFormat = '%x-%v'; // ISO year-week format
        break;
    case 'monthly':
        $orderDateInterval = '6 MONTH';
        $orderGroupBy = "DATE_FORMAT(order_date, '%Y-%m')";
        $orderDateFormat = '%Y-%m';
        break;
    case 'daily':
    default:
        $orderDateInterval = '14 DAY';
        $orderGroupBy = "DATE(order_date)";
        $orderDateFormat = '%Y-%m-%d';
        break;
}

// Prepare date filters based on period for users table
switch ($period) {
    case 'weekly':
        $userDateInterval = '8 WEEK';
        $userGroupBy = "YEAR(created_at), WEEK(created_at, 1)";
        $userDateFormat = '%x-%v'; // ISO year-week format
        break;
    case 'monthly':
        $userDateInterval = '6 MONTH';
        $userGroupBy = "DATE_FORMAT(created_at, '%Y-%m')";
        $userDateFormat = '%Y-%m';
        break;
    case 'daily':
    default:
        $userDateInterval = '14 DAY';
        $userGroupBy = "DATE(created_at)";
        $userDateFormat = '%Y-%m-%d';
        break;
}

// Sales over time query
$salesStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(order_date, '$orderDateFormat') AS period_label, 
        SUM(total) AS sales
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL $orderDateInterval)
    GROUP BY $orderGroupBy
    ORDER BY period_label ASC
");
$salesStmt->execute();
$salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

// Customer growth over time query
$customerStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '$userDateFormat') AS period_label, 
        COUNT(id) AS new_customers
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $userDateInterval)
    GROUP BY $userGroupBy
    ORDER BY period_label ASC
");
$customerStmt->execute();
$customerData = $customerStmt->fetchAll(PDO::FETCH_ASSOC);

// Orders by category (donut chart)
$categoryStmt = $pdo->prepare("
    SELECT c.name AS category_name, COUNT(oi.id) AS order_count
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    GROUP BY c.name
    ORDER BY order_count DESC
");
$categoryStmt->execute();
$categoryData = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// Top products by quantity sold (bar chart)
$productStmt = $pdo->prepare("
    SELECT p.name AS product_name, SUM(oi.quantity) AS total_quantity_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.name
    ORDER BY total_quantity_sold DESC
    LIMIT 5
");
$productStmt->execute();
$productData = $productStmt->fetchAll(PDO::FETCH_ASSOC);

// Output JSON response
echo json_encode([
    'salesOverTime' => $salesData,
    'customerGrowth' => $customerData,
    'ordersByCategory' => $categoryData,
    'topProducts' => $productData,
]);
