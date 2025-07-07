<?php
// /zaiko/delivery_receipt.php
session_start();
// This path will now work correctly because this file is in the main project directory
require_once __DIR__ . '/includes/db.php'; 
require_once __DIR__ . '/includes/auth.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
if ($po_id <= 0) {
    http_response_code(400);
    die('<div class="alert alert-danger">Invalid Purchase Order ID.</div>');
}

// Fetch PO Header
$stmt_header = $conn->prepare("SELECT po.*, s.name AS supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = ?");
$stmt_header->bind_param("i", $po_id);
$stmt_header->execute();
$po_header = $stmt_header->get_result()->fetch_assoc();
$stmt_header->close();

if (!$po_header) {
    http_response_code(404);
    die('<div class="alert alert-danger">Purchase Order not found.</div>');
}

// Fetch PO Items to display in the form
$stmt_items = $conn->prepare("
    SELECT poi.*, mi.sku, mi.unit AS master_unit
    FROM purchase_order_items poi
    LEFT JOIN master_items mi ON mi.id = poi.master_item_id
    WHERE poi.order_id = ? ORDER BY poi.id
");
$stmt_items->bind_param("i", $po_id);
$stmt_items->execute();
$po_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<form id="receive-po-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="po_id" value="<?= htmlspecialchars($po_header['id']) ?>">

    <div class="row mb-3">
        <div class="col-md-4">
            <label for="sales_invoice_no" class="form-label">Sales Invoice # <span class="text-danger">*</span></label>
            <input type="text" name="sales_invoice_no" id="sales_invoice_no" class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>" required>
        </div>
        <div class="col-md-4"><p class="mb-1"><strong>Supplier:</strong><br><?= htmlspecialchars($po_header['supplier_name']) ?></p></div>
        <div class="col-md-4"><p class="mb-1"><strong>Status:</strong><br><span class="badge bg-info"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $po_header['status']))) ?></span></p></div>
    </div>

    <h5 class="mt-4 mb-3">Items to Receive</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th style="width: 18%;">Serial #</th>
                    <th>Unit</th>
                    <th class="text-end">Outstanding</th>
                    <th style="width: 15%;" class="text-end">Receiving Now <span class="text-danger">*</span></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $has_outstanding_items = false;
                foreach ($po_items as $index => $item) {
                    $outstanding_qty = (float)$item['quantity'] - (float)$item['quantity_received'];
                    if ($outstanding_qty > 0.001) {
                        $has_outstanding_items = true;
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($item['description']);
                        echo '<input type="hidden" name="items[' . $index . '][po_item_id]" value="' . $item['id'] . '">';
                        echo '<input type="hidden" name="items[' . $index . '][master_item_id]" value="' . $item['master_item_id'] . '">';
                        echo '</td>';
                        echo '<td>' . htmlspecialchars($item['sku'] ?? 'N/A') . '</td>';
                        echo '<td><input type="text" name="items[' . $index . '][serial_number]" class="form-control form-control-sm" placeholder="Enter Serial #"></td>';
                        echo '<td>' . htmlspecialchars($item['unit'] ?? $item['master_unit']) . '</td>';
                        echo '<td class="text-end fw-bold">' . number_format($outstanding_qty, 2) . '</td>';
                        echo '<td><input type="number" class="form-control form-control-sm text-end" name="items[' . $index . '][qty_receiving_now]" min="0" max="' . $outstanding_qty . '" step="any" value="0" required></td>';
                        echo '</tr>';
                    }
                }
                if (!$has_outstanding_items) {
                    echo '<tr><td colspan="6" class="text-center alert alert-info">All items for this PO have been fully received.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <label class="form-label">Receiving Notes (Optional)</label>
            <textarea class="form-control" name="po_notes" rows="1"></textarea>
        </div>
        <div class="col-md-6">
             <label for="received_by" class="form-label">Received By <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="received_by" name="received_by" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Current User') ?>" readonly>
        </div>
    </div>
    <hr>
    <div id="receive-po-result" class="my-3"></div>
    <?php if ($has_outstanding_items): ?>
    <div class="mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Process Receipt & Update Stock</button>
    </div>
    <?php endif; ?>
</form>