<?php
// zaiko/views/materials_request_form.php
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
    // MODIFIED: Ensure 'category' is selected from the master_items table
    $items_result = $conn->query("SELECT id, name, sku, description, unit, unit_price, category FROM master_items ORDER BY name ASC");
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
    . "data-desc='" . htmlspecialchars($item['description'] ?? '') . "' "
    . "data-unit='" . htmlspecialchars($item['unit']) . "' "
    . "data-unit_price='" . htmlspecialchars($item['unit_price']) . "' "
    // MODIFIED: Added data-category attribute
    . "data-category='" . htmlspecialchars($item['category'] ?? '') . "'>"
    . htmlspecialchars($item['name']) . "</option>";
}
?>

<style>
    #requestItemsTable {
        table-layout: fixed;
        width: 100%;
    }
    .grip {
        height: 100%;
        width: 5px;
        background-color: #f0f0f0;
        cursor: col-resize;
    }
</style>

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
                            <th style="width: 20%;">Item Name</th>
                            <th style="width: 10%;">SKU</th>
                            <th style="width: 25%;">Description</th>
                            <th style="width: 5%;">Unit</th>
                            <th style="width: 5%;">Qty</th>
                            <th style="width: 8%;">Unit Price</th>
                            <th style="width: 8%;">VAT ex</th>
                            <th style="width: 5%;">Taxable</th>
                            <th style="width: 8%;">Total</th>
                            <th style="width: 6%;">Action</th>
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
                                <input type="hidden" class="category-input" name="items[0][category]">
                            </td>
                            <td><input type="text" class="form-control sku-input" name="items[0][sku]" readonly></td>
                            <td><input type="text" class="form-control desc-input" name="items[0][description]" readonly></td>
                            <td><input type="text" class="form-control unit-input" name="items[0][unit]" readonly></td>
                            <td><input type="number" class="form-control qty-input" name="items[0][qty]" min="1" step="any" value="1"></td>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/colresizable@1.6.0/colResizable-1.6.min.js"></script>

<script>
$(document).ready(function() {

    function initResizableTable() {
        // First, destroy any existing instance to avoid conflicts
        $("#requestItemsTable").colResizable({
            disable: true
        });
        // Re-initialize the plugin
        $("#requestItemsTable").colResizable({
            liveDrag: true,
            gripInnerHtml: "<div class='grip'></div>",
            draggingClass: "bg-info",
            minWidth: 30
        });
    }

    function calculateTotals() {
        const taxRateValue = parseFloat($('#tax_rate').val()) || 0;
        const taxMultiplier = 1 + (taxRateValue / 100);

        $('#requestItemsTable tbody tr').each(function() {
            const row = $(this);
            const qty = parseFloat(row.find('.qty-input').val()) || 0;
            const inputPrice = parseFloat(row.find('.price-input').val()) || 0;
            const isTaxable = row.find('.taxable-input').is(':checked');

            let price_nontaxed = 0;
            let price_taxed = 0;

            if (isTaxable) {
                price_nontaxed = inputPrice;
                price_taxed = inputPrice * taxMultiplier;
            } else {
                price_taxed = inputPrice;
                price_nontaxed = inputPrice / taxMultiplier;
            }
            
            const lineTotal = qty * price_taxed;

            row.find('.non-taxed-price-input').val(price_nontaxed > 0 ? price_nontaxed.toFixed(2) : '');
            row.find('.total-input').val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
        });
    }

    // Use event delegation for all events inside the table body
    $('#requestItemsTable tbody').on('change', '.item-name-select', function() {
        const selected = $(this).find('option:selected');
        const row = $(this).closest('tr');
        row.find('.sku-input').val(selected.data('sku') || '');
        row.find('.desc-input').val(selected.data('desc') || '');
        row.find('.unit-input').val(selected.data('unit') || '');
        row.find('.price-input').val(selected.data('unit_price') || '');
        row.find('.name-input').val(selected.data('name') || '');
        // MODIFIED: Populate the hidden category input
        row.find('.category-input').val(selected.data('category') || '');
        calculateTotals();
    });

    $('#requestItemsTable tbody').on('input', '.qty-input, .price-input', function() {
        calculateTotals();
    });

    $('#requestItemsTable tbody').on('change', '.taxable-input', function() {
        calculateTotals();
    });

    $('#requestItemsTable tbody').on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Add new row
    $('#addMoreRow').on('click', function() {
        const tbody = $('#requestItemsTable tbody');
        const rowCount = tbody.find('tr').length;
        const jsOptions = `<?= addslashes($js_item_options) ?>`;
        
        const newRowHtml = `
            <tr>
                <td>
                    <select class="form-select item-name-select" name="items[${rowCount}][item_id]" required>
                        <option value="">Select item</option>${jsOptions}
                    </select>
                    <input type="hidden" class="name-input" name="items[${rowCount}][name]">
                    <input type="hidden" class="category-input" name="items[${rowCount}][category]">
                </td>
                <td><input type="text" class="form-control sku-input" name="items[${rowCount}][sku]" readonly></td>
                <td><input type="text" class="form-control desc-input" name="items[${rowCount}][description]" readonly></td>
                <td><input type="text" class="form-control unit-input" name="items[${rowCount}][unit]" readonly></td>
                <td><input type="number" class="form-control qty-input" name="items[${rowCount}][qty]" min="1" step="any" value="1"></td>
                <td><input type="number" class="form-control price-input" name="items[${rowCount}][price]" min="0" step="any"></td>
                <td><input type="number" class="form-control non-taxed-price-input" readonly></td>
                <td class="text-center"><input type="checkbox" class="form-check-input taxable-input" name="items[${rowCount}][taxable]" value="1" checked></td>
                <td><input type="text" class="form-control total-input" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row">&times;</button></td>
            </tr>`;
        
        tbody.append(newRowHtml);
        // Refresh the resizable columns after adding a row
        initResizableTable();
    });

    // Event listener for the main tax rate input
    $('#tax_rate').on('input', calculateTotals);
    
    // Initial setup
    $('.remove-row').show(); // Show remove button on the first row
    initResizableTable();
    calculateTotals();

    // Bootstrap form validation
    $('.needs-validation').on('submit', function(event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>