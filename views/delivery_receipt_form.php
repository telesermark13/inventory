<?php
// zaiko/views/delivery_receipt_form.php
// Extract variables from the $form_data array
$is_edit = $form_data['is_edit'];
$delivery_number = $form_data['delivery_number'];
$existing_data = $form_data['existing_data'];
$existing_items_details = $form_data['existing_items_details'];
$all_inventory_items = $form_data['all_inventory_items'];
$approved_purchase_orders = $form_data['approved_purchase_orders'];
?>

<style>
    .table-container-wrapper {
        overflow-x: auto;
        width: 100%;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
    }
    .table-container-wrapper table {
        /* Increased min-width to prevent cramping */
        min-width: 1800px;
    }
</style>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><?= $is_edit ? 'Edit Delivery Receipt' : 'Create New Delivery Receipt' ?></h4>
        <span class="badge bg-light text-dark fs-6">DR-<?= htmlspecialchars($delivery_number) ?></span>
    </div>
    <div class="card-body p-4">

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
             <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="mb-4 p-3 bg-light border rounded-3">
            <label for="po_selector" class="form-label fw-bold">🚀 Load from Received Purchase Order</label>
            <select class="form-select" id="po_selector">
                <option value="">-- Select a PO to auto-fill items --</option>
                <?php foreach ($approved_purchase_orders as $po): ?>
                    <option value="<?= htmlspecialchars($po['id']) ?>">
                        <?php
                            $po_display_number = 'PO-' . str_pad($po['id'], 5, '0', STR_PAD_LEFT);
                            echo htmlspecialchars($po_display_number . ' (' . $po['supplier_name'] . ')');
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <form method="POST" action="delivery_receipt.php<?= $is_edit ? '?edit=' . $delivery_number : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="is_edit" value="<?= $is_edit ? '1' : '0' ?>">
            <input type="hidden" name="delivery_number_hidden" value="<?= htmlspecialchars($delivery_number) ?>">

            <div class="row g-3 mb-3">
                 <div class="col-md-4"><label class="form-label">Client / Supplier <span class="text-danger">*</span></label><input type="text" name="client" class="form-control" value="<?= htmlspecialchars($existing_data['client'] ?? '') ?>" required></div>
                 <div class="col-md-4"><label class="form-label">Project <span class="text-danger">*</span></label><input type="text" name="project" class="form-control" value="<?= htmlspecialchars($existing_data['project'] ?? '') ?>" required></div>
                 <div class="col-md-4"><label class="form-label">Location <span class="text-danger">*</span></label><input type="text" name="location" class="form-control" value="<?= htmlspecialchars($existing_data['location'] ?? '') ?>" required></div>
             </div>
             <div class="row g-3 mb-4">
                  <div class="col-md-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="date" value="<?= htmlspecialchars($existing_data ? date('Y-m-d', strtotime($existing_data['date'])) : date('Y-m-d')) ?>" required></div>
                  <div class="col-md-4"><label class="form-label">Received By</label><input type="text" name="received_by" class="form-control" value="<?= htmlspecialchars($existing_data['received_by'] ?? '') ?>"></div>
             </div>

            <h5 class="mb-3">Delivery Items</h5>
            <div class="table-container-wrapper">
                <table class="table table-bordered" id="delivery_items_table">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 250px;">Item</th>
                            <th style="min-width: 150px;">SKU</th>
                            <th style="min-width: 150px;">Serial Number</th>
                            <th style="min-width: 80px;">Unit</th>
                            <th style="min-width: 100px;">Qty Left</th>
                            <th style="min-width: 100px;">Qty Ordered</th>
                            <th style="min-width: 100px;">Qty Delivered</th>
                            <th style="min-width: 110px;">Outstanding</th>
                            <th style="min-width: 300px;">Description</th>
                            <th style="min-width: 120px;">Unit Price</th>
                            <th style="min-width: 120px;">Taxed Price</th>
                            <th style="min-width: 120px;">Non-Taxed Price</th>
                            <th style="min-width: 120px;">Total (Non-Taxed)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="delivery_items_tbody"></tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-success" id="add_delivery_item_btn"><i class="bi bi-plus-circle"></i> Add Item Line</button>
                <div>
                    <a href="delivery_history.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Receipt</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('delivery_items_tbody');
    const addItemBtn = document.getElementById('add_delivery_item_btn');
    const poSelector = document.getElementById('po_selector');
    let itemRowIndex = 0;

    const inventoryItems = <?= json_encode($all_inventory_items) ?>;
    const existingItems = <?= json_encode($existing_items_details) ?>;

    function createItemOptionsHtml() {
        let options = '<option value="">-- Select Inventory Item --</option>';
        inventoryItems.forEach(item => {
            const val = `${item.type}_${item.id}`;
            const stock = item.quantity || 0;
            options += `<option value="${val}" data-sku="${item.sku || ''}" data-serial_number="${item.serial_number || ''}" data-unit="${item.unit || ''}" data-stock="${stock}" data-description="${item.description || ''}" data-unit_price="${item.unit_price || 0}" data-price_taxed="${item.price_taxed || 0}" data-price_nontaxed="${item.price_nontaxed || 0}">
                ${item.name} (Stock: ${stock})
            </option>`;
        });
        options += '<option value="custom_item">-- Custom / Non-Stock Item --</option>';
        return options;
    }
    const itemOptionsHtmlContent = createItemOptionsHtml();

    function createDeliveryRow(data = {}) {
        const idx = itemRowIndex++;
        const newRow = document.createElement('tr');
        // THIS IS THE FIX for column order
        newRow.innerHTML = `
            <td><select class="form-select master-item-select" name="line_items[${idx}][master_item_full_id]">${itemOptionsHtmlContent}</select></td>
            <td><input type="text" class="form-control item-serial" name="line_items[${idx}][serial_number]"></td>
            <td><input type="text" class="form-control item-sku" name="line_items[${idx}][sku]" readonly></td>
            <td><input type="text" class="form-control item-unit" name="line_items[${idx}][unit]" readonly></td>
            <td><input type="text" class="form-control item-qty-left" readonly></td>
            <td><input type="number" class="form-control item-ordered" name="line_items[${idx}][ordered]" value="1" step="any" required></td>
            <td><input type="number" class="form-control item-delivered" name="line_items[${idx}][delivered]" value="0" step="any" required></td>
            <td><input type="text" class="form-control item-outstanding" readonly></td>
            <td><input type="text" class="form-control item-description" name="line_items[${idx}][description]" required></td>
            <td><input type="number" class="form-control item-unit-price" name="line_items[${idx}][unit_price]" readonly></td>
            <td><input type="number" class="form-control" name="line_items[${idx}][price_taxed]" readonly></td>
            <td><input type="number" class="form-control item-price-nontaxed" name="line_items[${idx}][price_nontaxed]" readonly></td>
            <td><input type="text" class="form-control item-total-nontaxed" readonly></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger remove-delivery-item"><i class="bi bi-trash"></i></button></td>
        `;
        tableBody.appendChild(newRow);

        const masterSelect = newRow.querySelector('.master-item-select');
        masterSelect.value = data.master_item_full_id || '';

        // Manually populate all fields from the data object to ensure correctness
        newRow.querySelector('.item-sku').value = data.sku || '';
        newRow.querySelector('.item-serial').value = data.serial_number || '';
        newRow.querySelector('.item-unit').value = data.unit || '';
        newRow.querySelector('.item-qty-left').value = data.stock_qty || '';
        newRow.querySelector('.item-ordered').value = data.ordered || 1;
        newRow.querySelector('.item-delivered').value = data.delivered || data.ordered || 0;
        newRow.querySelector('.item-description').value = data.description || '';
        newRow.querySelector('.item-unit-price').value = data.unit_price || 0;
        newRow.querySelector('input[name*="[price_taxed]"]').value = data.price_taxed || 0;
        newRow.querySelector('.item-price-nontaxed').value = data.price_nontaxed || 0;
        
        attachDeliveryRowEventListeners(newRow);
    }

    function attachDeliveryRowEventListeners(row) {
        // ... (This function remains the same, it handles manual changes) ...
        calculateLineTotals(row);
    }

    function calculateLineTotals(row) {
        const ordered = parseFloat(row.querySelector('.item-ordered').value) || 0;
        const delivered = parseFloat(row.querySelector('.item-delivered').value) || 0;
        const price = parseFloat(row.querySelector('.item-price-nontaxed').value) || 0;
        row.querySelector('.item-outstanding').value = (ordered - delivered).toFixed(2);
        row.querySelector('.item-total-nontaxed').value = (delivered * price).toFixed(2);
    }

    poSelector.addEventListener('change', function () {
        const poId = this.value;
        if (!poId) return;

        fetch(`delivery_receipt.php?action=fetch_po_items&po_id=${poId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.po) {
                    alert('Purchase Order not found or not in a receivable state.');
                    return;
                }
                document.querySelector('input[name="client"]').value = data.po.supplier_name || '';
                
                tableBody.innerHTML = '';
                itemRowIndex = 0;

                data.items.forEach(item => {
                    // THIS IS THE FIX: Using the correct aliased names from the server
                    createDeliveryRow({
                        master_item_full_id: `current_${item.item_id}`,
                        sku: item.item_sku,
                        serial_number: item.item_serial_number,
                        unit: item.po_item_unit,
                        stock_qty: item.stock_qty,
                        ordered: item.quantity,
                        delivered: item.quantity,
                        description: item.po_item_description,
                        unit_price: item.unit_price,
                        price_taxed: item.price_taxed,
                        price_nontaxed: item.price_nontaxed
                    });
                });
            })
            .catch(err => console.error('Error loading PO:', err));
    });

    // Initial State
    if (existingItems.length > 0) {
        existingItems.forEach(item => createDeliveryRow(item));
    } else {
        createDeliveryRow();
    }
    
    addItemBtn.addEventListener('click', () => createDeliveryRow());
});
</script>