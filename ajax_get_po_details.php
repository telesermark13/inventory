<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_GET['po_id']) || !filter_var($_GET['po_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo "<div class='alert alert-danger'>Invalid Purchase Order ID.</div>";
    exit;
}
$po_id = (int)$_GET['po_id'];

// Fetch PO Header
$stmt_po = $conn->prepare(
    "SELECT po.*, s.name as supplier_name, u.username as creator_username, 
            mr.id as material_request_id_display, mr.grand_total_amount, mr.id as request_id
     FROM purchase_orders po
     JOIN suppliers s ON po.supplier_id = s.id
     JOIN users u ON po.created_by = u.id
     LEFT JOIN materials_requests mr ON po.request_id = mr.id
     WHERE po.id = ?"
);
if (!$stmt_po) { http_response_code(500); echo "<div class='alert alert-danger'>Error preparing PO data.</div>"; exit; }
$stmt_po->bind_param("i", $po_id);
$stmt_po->execute();
$po_header_result = $stmt_po->get_result();
$po_header = $po_header_result->fetch_assoc();
$stmt_po->close();

if (!$po_header) {
    http_response_code(404);
    echo "<div class='alert alert-warning'>Purchase Order #{$po_id} not found.</div>";
    exit;
}

// --- MODIFIED ITEM QUERY ---
// This query now joins `master_items` to get the definitive SKU and Serial Number.
$stmt_items = $conn->prepare(
    "SELECT 
        poi.*, 
        i.name as item_name,
        COALESCE(i.sku, mi.sku) AS final_sku, 
        i.unit,
        mi.serial_number,
        COALESCE(mri.total / NULLIF(mri.quantity, 0), poi.unit_price) AS taxed_unit_price,
        COALESCE(mri.total, poi.quantity * poi.unit_price) AS taxed_subtotal
     FROM purchase_order_items poi
     LEFT JOIN items i ON poi.item_id = i.id
     LEFT JOIN master_items mi ON poi.master_item_id = mi.id
     LEFT JOIN materials_request_items mri ON poi.master_item_id = mri.master_item_id AND mri.request_id = ?
     WHERE poi.order_id = ? 
     ORDER BY poi.id ASC"
);
if (!$stmt_items) { http_response_code(500); echo "<div class='alert alert-danger'>Error preparing PO items data.</div>"; exit; }
$stmt_items->bind_param("ii", $po_header['request_id'], $po_id);
$stmt_items->execute();
$po_items_result = $stmt_items->get_result();
$po_items = [];
while($row = $po_items_result->fetch_assoc()) {
    $po_items[] = $row;
}
$stmt_items->close();


function get_po_status_badge_class($status) {
    switch (strtolower($status)) {
        case 'pending': case 'pending_po_approval': return 'warning text-dark';
        case 'approved_to_order': return 'info text-dark';
        case 'ordered': case 'purchased': return 'primary';
        case 'partially_received': return 'info text-dark';
        case 'fully_received': return 'success';
        case 'cancelled': case 'canceled': return 'danger';
        default: return 'secondary';
    }
}
?>

<div class="container-fluid p-3">
    <h4 class="mb-3">PO-<?= str_pad($po_header['id'], 5, '0', STR_PAD_LEFT) ?> Details</h4>
    <hr>
    <div class="row mb-3">
        <div class="col-md-6">
            <p><strong>Supplier:</strong> <?= htmlspecialchars($po_header['supplier_name']) ?></p>
            <p><strong>Order Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($po_header['order_date']))) ?></p>
            <p><strong>Status:</strong><span class="badge bg-<?= get_po_status_badge_class($po_header['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $po_header['status']))) ?></span></p>
            <p><strong>Invoice #:</strong> <?= htmlspecialchars($po_header['sales_invoice_no'] ?? '-') ?></p>
            <?php if (!empty($po_header['material_request_id_display'])): ?><p><strong>Material Request:</strong> MR-<?= htmlspecialchars($po_header['material_request_id_display']) ?></p><?php endif; ?>
            <p><strong>Created By:</strong> <?= htmlspecialchars($po_header['creator_username']) ?></p>
        </div>
        <div class="col-md-6">
            <strong>Notes/Description:</strong>
            <div class="p-2 border rounded bg-light mt-1 mb-2"><?= nl2br(htmlspecialchars($po_header['notes'] ?? 'No notes provided.')) ?></div>
        </div>
    </div>

    <h5 class="mb-2">Items on this Purchase Order:</h5>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-bordered">
             <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th>SKU</th>
                    <th>Serial #</th>
                    <th>Unit</th>
                    <th class="text-end">Qty Ordered</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Qty Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($po_items)): foreach ($po_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= htmlspecialchars($item['final_sku'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td class="text-end"><?= number_format($item['quantity'], 2) ?></td>
                    <td class="text-end">₱<?= number_format($item['taxed_unit_price'], 2) ?></td>
                    <td class="text-end">₱<?= number_format($item['taxed_subtotal'], 2) ?></td>
                    <td class="text-end"><?= number_format($item['quantity_received'], 2) ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" class="text-center">No items found for this purchase order.</td></tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($po_items)): ?>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Estimated Grand Total Ordered:</th>
                    <th class="text-end">₱<?= number_format($po_header['grand_total_amount'] ?? 0, 2) ?></th>
                    <th></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>