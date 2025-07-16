<?php
require_once '../Admin/includes/db.php';

$country = $_GET['country'] ?? '';
$state = $_GET['state'] ?? '';

$stmt = $pdo->prepare("SELECT shipping_fee FROM shipping_zones WHERE country=? AND state=? AND active=1 LIMIT 1");
$stmt->execute([$country, $state]);
$fee = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['shipping_fee' => $fee !== false ? floatval($fee) : 0]);
