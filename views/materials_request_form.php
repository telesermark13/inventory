<?php
global $conn;

// Fetch suppliers for dropdown
$suppliers_list = [];
if ($conn) {
    $suppliers_result = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
    if ($suppliers_result) {
        while ($supplier = $suppliers_result->fetch_assoc()) {
            $suppliers_list[] = $supplier;
        }
    }
}

// Fetch master items for dropdown
$master_items = [];
if ($conn) {
    // Ensure you select all necessary columns, including sku and serial_number
    $items_result = $conn->query("SELECT id, name, sku, serial_number, description, unit, category, unit_price FROM master_items ORDER BY name ASC");
    if ($items_result) {
        while ($item = $items_result->fetch_assoc()) {
            $master_items[] = $item;
        }
    }
}

// For JS template for new rows
$js_item_options = "";
foreach ($master_items as $item) {
    $js_item_options .= "<option value='" . htmlspecialchars($item['id']) . "' "
    . "data-name='" . htmlspecialchars($item['name']) . "' "
    . "data-sku='" . htmlspecialchars($item['sku'] ?? '') . "' "
    . "data-serial_number='" . htmlspecialchars($item['serial_number'] ?? '') . "' " // Added data-serial_number
    . "data-desc='" . htmlspecialchars($item['description']) . "' "
    . "data-unit='" . htmlspecialchars($item['unit']) . "' "
    . "data-category='" . htmlspecialchars($item['category']) . "' "
    . "data-unit_price='" . htmlspecialchars($item['unit_price']) . "'>"
    . htmlspecialchars($item['name']) . "</option>";
}
?>

<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">Materials Request Form</h3>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form method="POST" action="materials_request_save.php" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="supplier_id" class="form-label">Supplier (Optional)</label>
                    <select class="form-select" id="supplier_id" name="supplier_id">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers_list as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="request_date" class="form-label">Request Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="request_date" name="request_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="tax_rate" class="form-label">Tax Rate <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="12" step="0.01" min="0" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Items Requested</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="requestItemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>SKU</th>
                            <th>Serial Number</th>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Calc. Non-Taxed Unit Price</th>
                            <th>Taxable</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select class="form-select item-name-select" name="items[0][item_id]" required>
                                    <option value="">Select item</option>
                                     <?php echo $js_item_options; ?>
                                </select>
                                <input type="hidden" class="name-input" name="items[0][name]">
                            </td>
                            <td><input type="text" class="form-control sku-input" name="items[0][sku]" readonly></td>
                            <td><input type="text" class="form-control serial-number-input" name="items[0][serial_number]"></td>
                            <td><input type="text" class="form-control desc-input" name="items[0][description]" readonly></td>
                            <td><input type="text" class="form-control unit-input" name="items[0][unit]" readonly></td>
                            <td><input type="text" class="form-control category-input" name="items[0][category]" readonly></td>
                            <td><input type="number" class="form-control qty-input" name="items[0][qty]" min="0" step="any"></td>
                            <td><input type="number" class="form-control price-input" name="items[0][price]" min="0" step="any"></td>
                            <td><input type="number" class="form-control non-taxed-price-input" readonly></td>
                            <td class="text-center"><input type="checkbox" class="form-check-input taxable-input" name="items[0][taxable]" value="1" checked></td>
                            <td><input type="text" class="form-control total-input" readonly></td>
                            <td><button type="button" class="btn btn-danger btn-sm remove-row" style="display:none;">&times;</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-success" id="addMoreRow"><i class="bi bi-plus-circle"></i> Add Row</button>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="reset" class="btn btn-secondary me-2">Reset</button>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function addAutofillListeners(row) {
        row.querySelector('.item-name-select').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            // Updated to populate both SKU and Serial Number
            row.querySelector('.sku-input').value = selected.getAttribute('data-sku') || '';
            row.querySelector('.serial-number-input').value = selected.getAttribute('data-serial_number') || '';
            row.querySelector('.desc-input').value = selected.getAttribute('data-desc') || '';
            row.querySelector('.unit-input').value = selected.getAttribute('data-unit') || '';
            row.querySelector('.price-input').value = selected.getAttribute('data-unit_price') || '';
            row.querySelector('.category-input').value = selected.getAttribute('data-category') || '';
            row.querySelector('.name-input').value = selected.getAttribute('data-name') || '';
            calculateTotals();
        });

        const removeBtn = row.querySelector('.remove-row');
        if (removeBtn) {
            removeBtn.style.display = '';
            removeBtn.addEventListener('click', function() {
                row.remove();
                calculateTotals();
            });
        }
        
        row.querySelector('.qty-input').addEventListener('input', calculateTotals);
        row.querySelector('.price-input').addEventListener('input', calculateTotals);
        row.querySelector('.taxable-input').addEventListener('change', calculateTotals);
    }

    const firstRow = document.querySelector('#requestItemsTable tbody tr');
    if (firstRow) addAutofillListeners(firstRow);

    document.getElementById('addMoreRow').addEventListener('click', function() {
        const tbody = document.querySelector('#requestItemsTable tbody');
        const rowCount = tbody.querySelectorAll('tr').length;
        const jsOptions = `<?= addslashes($js_item_options) ?>`;

        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select class="form-select item-name-select" name="items[${rowCount}][item_id]" required>
                    <option value="">Select item</option>${jsOptions}
                </select>
                <input type="hidden" class="name-input" name="items[${rowCount}][name]">
            </td>
            <td><input type="text" class="form-control sku-input" name="items[${rowCount}][sku]" readonly></td>
            <td><input type="text" class="form-control serial-number-input" name="items[${rowCount}][serial_number]"></td>
            <td><input type="text" class="form-control desc-input" name="items[${rowCount}][description]" readonly></td>
            <td><input type="text" class="form-control unit-input" name="items[${rowCount}][unit]" readonly></td>
            <td><input type="text" class="form-control category-input" name="items[${rowCount}][category]" readonly></td>
            <td><input type="number" class="form-control qty-input" name="items[${rowCount}][qty]" min="0" step="any"></td>
            <td><input type="number" class="form-control price-input" name="items[${rowCount}][price]" min="0" step="any"></td>
            <td><input type="number" class="form-control non-taxed-price-input" readonly></td>
            <td class="text-center"><input type="checkbox" class="form-check-input taxable-input" name="items[${rowCount}][taxable]" value="1" checked></td>
            <td><input type="text" class="form-control total-input" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
        `;
        tbody.appendChild(newRow);
        addAutofillListeners(newRow);
    });

    function calculateTotals() {
        // This function remains the same as before
        let subtotalAll = 0;
        let taxTotalAll = 0;
        let grandTotalAll = 0;
        const taxRate = parseFloat(document.getElementById('tax_rate').value) / 100 || 0;
        const taxRateMultiplier = 1 + taxRate;

        document.querySelectorAll('#requestItemsTable tbody tr').forEach(function(row) {
            const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
            const inputPrice = parseFloat(row.querySelector('.price-input')?.value) || 0;
            const isTaxable = row.querySelector('.taxable-input')?.checked;
            const nonTaxedPriceInput = row.querySelector('.non-taxed-price-input');

            let unitPriceNonTaxed = 0;
            let unitPriceTaxed = 0;
            
            if (isTaxable) {
                unitPriceNonTaxed = inputPrice;
                unitPriceTaxed = inputPrice * taxRateMultiplier;
            } else {
                unitPriceTaxed = inputPrice;
                unitPriceNonTaxed = inputPrice / taxRateMultiplier;
            }

            nonTaxedPriceInput.value = unitPriceNonTaxed > 0 ? unitPriceNonTaxed.toFixed(2) : '';
            const lineSubtotal = qty * unitPriceNonTaxed;
            const lineTotal = qty * unitPriceTaxed;
            row.querySelector('.total-input').value = lineTotal > 0 ? lineTotal.toFixed(2) : '';
        });
    }
    
    document.getElementById('tax_rate').addEventListener('input', calculateTotals);
    calculateTotals();
    
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});
</script>