<?php
// /zaiko/ajax_get_po_details.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$po_id = filter_input(INPUT_GET, 'po_id', FILTER_VALIDATE_INT);

if (!$po_id) {
    echo '<p class="text-danger">Invalid Purchase Order ID.</p>';
    exit;
}

// Fetch PO Details
$po_sql = "
    SELECT po.*, s.name as supplier_name, u.username as created_by_username
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    LEFT JOIN users u ON po.created_by = u.id
    WHERE po.id = ?
";
$stmt = $conn->prepare($po_sql);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_res = $stmt->get_result();response_code: 
$po = $po_res->fetch_assoc();
$stmt->close();

if (!$po) {
    echo '<p class="text-danger">Purchase Order not found.</p>';
    exit;
}

// Fetch PO Items
$items_sql = "
    SELECT poi.*, mi.name as master_item_name
    FROM purchase_order_items poi
    JOIN master_items mi ON poi.master_item_id = mi.id
    WHERE poi.order_id = ?
";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $po_id);
$items_stmt->execute();
$items_res = $items_stmt->get_result();

$grand_total = 0;
?>

<h4>PO-000<?php echo htmlspecialchars($po['id']); ?> Details</h4>
<div class="row">
    <div class="col-md-6">
        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($po['supplier_name']); ?></p>
        <p><strong>Order Date:</strong> <?php echo date("F d, Y", strtotime($po['order_date'])); ?></p>
        <p><strong>Status:</strong> <span class="badge bg-warning"><?php echo htmlspecialchars(ucfirst($po['status'])); ?></span></p>
    </div>
    <div class="col-md-6">
        <p><strong>Notes/Description:</strong> <?php echo nl2br(htmlspecialchars($po['po_notes'] ?? 'No notes provided.')); ?></p>
        <p><strong>Invoice #:</strong> <?php echo htmlspecialchars($po['sales_invoice_no'] ?? 'N/A'); ?></p>
        <p><strong>Material Request:</strong> MR-<?php echo htmlspecialchars($po['request_id']); ?></p>
        <p><strong>Created By:</strong> <?php echo htmlspecialchars($po['created_by_username']); ?></p>
    </div>
</div>

<h5 class="mt-4">Items on this Purchase Order:</h5>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>SKU</th>
                <th>Unit</th>
                <th class="text-end">Qty Ordered</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">Qty Received</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $items_res->fetch_assoc()): ?>
                <?php 
                    $subtotal = $item['quantity'] * $item['unit_price'];
                    $grand_total += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['master_item_name']); ?></td> 
                    
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td class="text-end"><?php echo number_format($item['quantity'], 2); ?></td>
                    <td class="text-end">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-end">₱<?php echo number_format($subtotal, 2); ?></td>
                    <td class="text-end"><?php echo number_format($item['quantity_received'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-end"><strong>Estimated Grand Total Ordered:</strong></td>
                <td class="text-end"><strong>₱<?php echo number_format($grand_total, 2); ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php
$items_stmt->close();
$conn->close();
?>