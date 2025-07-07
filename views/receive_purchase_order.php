<?php
// zaiko/views/receive_purchase_order.php
global $po_header, $po_items; // From controller
?>
<div class="card-body">
    <form action="process_po_receipt.php" method="POST" class="needs-validation" novalidate id="receive-po-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="po_id" value="<?= $po_header['id'] ?>">

        <div class="mb-3 row">
            <div class="col-md-4">
                <label for="sales_invoice_no" class="form-label">Sales Invoice # <span class="text-danger">*</span></label>
                <input type="text" name="sales_invoice_no" id="sales_invoice_no" class="form-control" value="<?= htmlspecialchars($po_header['sales_invoice_no'] ?? '') ?>" placeholder="Required for receiving" required>
            </div>
             <div class="col-md-4">
                <p class="mb-1"><strong>Supplier:</strong></p>
                <p><?= htmlspecialchars($po_header['supplier_name']) ?></p>
            </div>
             <div class="col-md-4">
                <p class="mb-1"><strong>Status:</strong></p>
                <p><span class="badge bg-primary"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $po_header['status']))) ?></span></p>
            </div>
        </div>

        <h5 class="mt-4 mb-3">Items to Receive</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Serial #</th>
                        <th>Unit</th>
                        <th class="text-end">Ordered</th>
                        <th class="text-end">Rcvd.</th>
                        <th class="text-end">Outstanding</th>
                        <th style="width: 15%;" class="text-end">Qty Receiving Now <span class="text-danger">*</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($po_items as $index => $item):
                        $ordered_qty = (float)$item['quantity'];
                        $received_qty_val = (float)($item['quantity_received'] ?? $item['quantity_received_val'] ?? 0);
                        $outstanding_qty = $ordered_qty - $received_qty_val;
                    ?>
                        <?php if ($outstanding_qty > 0): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($item['description']) ?>
                                    <input type="hidden" name="items[<?= $index ?>][po_item_id]" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="items[<?= $index ?>][master_item_id]" value="<?= $item['master_item_id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($item['sku'] ?? 'N/A') ?></td>
                                <td>
                                    <input type="text" 
                                           name="items[<?= $index ?>][serial_number]" 
                                           class="form-control form-control-sm" 
                                           value="<?= htmlspecialchars($item['serial_number'] ?? '') ?>" 
                                           placeholder="Enter Serial #">
                                </td>
                                <td><?= htmlspecialchars($item['unit']) ?></td>
                                <td class="text-end"><?= number_format($ordered_qty, 2) ?></td>
                                <td class="text-end"><?= number_format($received_qty_val, 2) ?></td>
                                <td class="text-end fw-bold"><?= number_format($outstanding_qty, 2) ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm text-end qty-receiving"
                                           name="items[<?= $index ?>][qty_receiving_now]"
                                           min="0" max="<?= $outstanding_qty ?>" step="any" value="0" required>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if (empty(array_filter($po_items, fn($i) => (((float)($i['quantity'] ?? 0)) - (float)($i['quantity_received'] ?? $i['quantity_received_val'] ?? 0)) > 0))): ?>
                        <tr><td colspan="8" class="text-center alert alert-info">All items for this PO have been fully received.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="row mt-3">
             <div class="col-md-4">
                <label for="received_by_user" class="form-label">Received By (User) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="received_by_user" name="received_by_user_name" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" readonly required>
                <input type="hidden" name="received_by_user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
            </div>
             <div class="col-md-4">
                <label for="received_date" class="form-label">Date Received <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="received_date" name="received_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="receiving_notes" class="form-label">Receiving Notes (Optional)</label>
                <textarea class="form-control" id="receiving_notes" name="receiving_notes" rows="1"></textarea>
            </div>
        </div>

        <hr>
        <div id="receive-po-result" class="my-3"></div>
        
        <?php if (!empty(array_filter($po_items, fn($i) => (((float)($i['quantity'] ?? 0)) - (float)($i['quantity_received'] ?? $i['quantity_received_val'] ?? 0)) > 0))): ?>
            <div class="mt-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Process Receipt & Update Stock</button>
            </div>
        <?php endif; ?>
    </form>
</div>