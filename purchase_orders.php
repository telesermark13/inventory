<?php
// zaiko/purchase_orders.php 

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php'; // For get_status_badge()

// Set the CSRF token once for the page load
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Purchase Orders";

// Query to fetch the purchase orders and calculate the total for each.
$query = "
    SELECT
        po.id,
        po.status,
        po.request_id,
        s.name as supplier_name,
        u.username as created_by_username,
        po.sales_invoice_no,
        SUM(poi.quantity * poi.unit_price) as total_amount
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN users u ON po.created_by = u.id
    LEFT JOIN purchase_order_items poi ON po.id = poi.order_id
    GROUP BY po.id
    ORDER BY po.id DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$purchase_orders = $stmt->get_result();
$stmt->close();

// Set the path to the view file that will display the data.
// MODIFIED: Corrected the filename to point to your existing view file.
$view = 'views/purchase_orders_view.php'; 

// Load the main layout, which will in turn include the view file.
include __DIR__ . '/templates/layout.php';
?>