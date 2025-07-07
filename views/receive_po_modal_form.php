<?php
// zaiko/views/receive_po_modal_form.php
// 🔧 Enable error reporting to see what's wrong (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$po_id = isset($_GET['po_id']) ? intval($_GET['po_id']) : 0;
if ($po_id <= 0) {
    echo '<div class="alert alert-danger">Invalid PO ID.</div>';
    exit;
}

// ✅ Fetch PO header from purchase_orders table
$stmt = $conn->prepare("SELECT po.*, s.name AS supplier_name FROM purchase_orders po LEFT JOIN suppliers s ON s.id = po.supplier_id WHERE po.id = ?");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_header = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$po_header) {
    echo '<div class="alert alert-danger">Purchase Order not found.</div>';
    exit;
}

// ✅ Fetch PO items (fallback from master_items if needed)
$stmt = $conn->prepare("
    SELECT poi.*, 
           COALESCE(i.name, mi.name) AS item_name,
           COALESCE(i.unit, mi.unit) AS unit,
           COALESCE(i.sku, mi.sku) AS sku,
           COALESCE(i.serial_number, mi.serial_number) AS serial_number
    FROM purchase_order_items poi
    LEFT JOIN items i ON i.id = poi.item_id
    LEFT JOIN master_items mi ON mi.id = poi.master_item_id
    WHERE poi.order_id = ?
");
$stmt->bind_param("i", $po_id);
$stmt->execute();
$po_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
?>

<form id="receive-po-form" action="../controllers/receive_po_process.php" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="po_id" value="<?= $po_id ?>">

    <div class="mb-3">
        <label for="sales_invoice_no" class="form-label">Sales Invoice #</label>
        <input type="text" name="sales_invoice_no" id="sales_invoice_no"
               class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>"
               placeholder="Sales Invoice #" required>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">Supplier</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($po_header['supplier_name']) ?>" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Order Date</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($po_header['order_date']) ?>" readonly>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <input type="text" class="form-control" value="<?= ucfirst(str_replace('_', ' ', $po_header['status'])) ?>" readonly>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th>Serial #</th>
                    <th>Unit</th>
                    <th>Ordered</th>
                    <th>Received</th>
                    <th>Outstanding</th>
                    <th>Receive Now</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($po_items as $item):
                    $ordered = (float)$item['quantity'];
                    $received = (float)$item['quantity_received'];
                    $outstanding = $ordered - $received;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= htmlspecialchars($item['sku']) ?></td>
                    <td><?= htmlspecialchars($item['serial_number'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td><?= number_format($ordered, 2) ?></td>
                    <td><?= number_format($received, 2) ?></td>
                    <td><?= number_format($outstanding, 2) ?></td>
                    <td>
                        <input type="number" name="received_qty[]" class="form-control form-control-sm text-end"
                               value="0" min="0" max="<?= $outstanding ?>"
                               step="any" <?= $outstanding <= 0 ? 'readonly' : '' ?>>
                        <input type="hidden" name="item_id[]" value="<?= $item['item_id'] ?>">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Received By</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly>
            <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Date Received</label>
            <input type="date" class="form-control" name="received_date" value="<?= date('Y-m-d') ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Receiving Notes</label>
        <textarea class="form-control" name="receiving_notes" rows="2"></textarea>
    </div>

    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Process & Update Stock</button>
    </div>
</form>

<script>
document.querySelector('.needs-validation').addEventListener('submit', function(event){
    if (!this.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>
