<?php
// /zaiko/delivery_receipt.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$receipt_number = $_POST['receipt_number'];
$delivery_number = $_POST['delivery_number'];
$material_request_id = $_POST['material_request_id'];
$client = $_POST['client'];
$project = $_POST['project'];
$location = $_POST['location'];

$stmt = $conn->prepare("INSERT INTO delivery_receipts (receipt_number, client, project, location, delivery_number) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $receipt_number, $client, $project, $location, $delivery_number);
$stmt->execute();
$receipt_id = $stmt->insert_id;

$result = $conn->query("SELECT item_id, quantity FROM material_request_items WHERE material_request_id = $material_request_id");
while ($row = $result->fetch_assoc()) {
    $item_id = $row['item_id'];
    $qty = $row['quantity'];
    $conn->query("INSERT INTO delivery_items (receipt_id, item_id, quantity_delivered) VALUES ($receipt_id, $item_id, $qty)");
}

echo "Delivery Receipt Created Successfully.";
?>
