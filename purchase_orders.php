<?php
// zaiko/purchase_orders.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$page_title = "Purchase Orders";

// Fetch all purchase orders with supplier and creator names
$sql = "SELECT po.*, s.name as supplier_name, u.username as created_by_name
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        LEFT JOIN users u ON po.created_by = u.id
        ORDER BY po.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Set the view file and pass the fetched orders to it
$view = __DIR__ . '/views/purchase_orders_view.php';
$form_data = ['orders' => $orders];

// Include the main layout which will in turn include the view
include __DIR__ . '/templates/layout.php';
?>