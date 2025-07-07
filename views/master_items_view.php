<?php
// zaiko/views/master_items_view.php
// Note: $items is already passed from the controller
$columns = [];
if (!empty($items)) {
    $columns = array_keys($items[0]);
}

$category_options = [
    "Structured Cabling", "FDAS", "Network Switches", "Router/Firewall",
    "WAP", "Camera/Surveillance Equipment", "Electrical", "Telephone",
    "PVC Pipe", "EMT Pipe", "PVC Fittings", "Power Tools",
    "Personal Protective Equipment (PPE)", "Access Control", "Tools",
    "Consumables", "Copper Cables", "Computer Set", "Printer",
    "Kiosk", "Data Cabinet and Trays"
];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$path_to_update_script = rtrim($script_name, '/') . '/update_master_item.php';
$updateUrl = $protocol . '://' . $host . $path_to_update_script;
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <h5 class="card-title mb-2 mb-md-0"><i class="bi bi-box-seam"></i> Master Items</h5>
                <div class="row g-2 datatable-custom-filters" style="max-width: 600px;">
                    <div class="col-md-4"><input id="filterSKU" type="text" class="form-control form-control-sm" placeholder="Filter by SKU"></div>
                    <div class="col-md-4"><input id="filterName" type="text" class="form-control form-control-sm" placeholder="Filter by Name"></div>
                    <div class="col-md-4">
                        <select id="filterCategory" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($category_options as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div id="alert-placeholder"></div>
            <div class="table-responsive">
                <table id="masterItemsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <?php foreach ($columns as $col): ?>
                                        <td><?= htmlspecialchars($item[$col]) ?></td>
                                    <?php endforeach; ?>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm edit-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editItemModal"
                                                data-item='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Edit Master Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" id="editItemId" name="id">
                    <div class="row">
                        <div class="col-md-8 mb-3"><label for="editName" class="form-label">Name</label><input type="text" class="form-control" id="editName" name="name" required></div>
                        <div class="col-md-4 mb-3"><label for="editSku" class="form-label">SKU</label><input type="text" class="form-control" id="editSku" name="sku"></div>
                    </div>
                    <div class="mb-3"><label for="editDescription" class="form-label">Description</label><textarea class="form-control" id="editDescription" name="description" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="editCategory" class="form-label">Category</label><select class="form-select" id="editCategory" name="category"><option value="">Select a Category</option><?php foreach ($category_options as $cat):?><option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option><?php endforeach;?></select></div>
                        <div class="col-md-3 mb-3"><label for="editUnit" class="form-label">Unit</label><input type="text" class="form-control" id="editUnit" name="unit"></div>
                        <div class="col-md-3 mb-3"><label for="editQuantity" class="form-label">Quantity</label><input type="number" class="form-control" id="editQuantity" name="quantity" step="any"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="editUnitPrice" class="form-label">Unit Price</label><input type="number" class="form-control" id="editUnitPrice" name="unit_price" step="any"></div>
                        <div class="col-md-4 mb-3"><label for="editPriceTaxed" class="form-label">Price (Taxed)</label><input type="number" class="form-control" id="editPriceTaxed" name="price_taxed" step="any"></div>
                        <div class="col-md-4 mb-3"><label for="editPriceNontaxed" class="form-label">Price (Non-Taxed)</label><input type="number" class="form-control" id="editPriceNontaxed" name="price_nontaxed" step="any"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="editMinStockLevel" class="form-label">Min Stock Level</label><input type="number" class="form-control" id="editMinStockLevel" name="min_stock_level" step="1"></div>
                        <div class="col-md-4 mb-3"><label for="editPrice" class="form-label">Price</label><input type="number" class="form-control" id="editPrice" name="price" step="any"></div>
                        <div class="col-md-4 mb-3"><label for="editStatus" class="form-label">Status</label><select class="form-select" id="editStatus" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" form="editItemForm">Save Changes</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
        var table = $('#masterItemsTable').DataTable({
        destroy: true,      // Keeps this to prevent errors
        responsive: true,   // This is the most important line
        pageLength: 10,
        order: [[0, 'desc']], // Orders by the first column (ID)
        columnDefs: [{ 
            orderable: false, 
            targets: -1     // Makes the last column (Actions) not sortable
        }]
    });

    var columns = <?= json_encode(array_flip(array_map('strtolower', str_replace('_', ' ', $columns ?? [])))) ?>;
    
    $('#filterSKU').on('keyup change', function() {
        if(columns['sku'] !== undefined) {
            table.column(columns['sku']).search(this.value).draw();
        }
    });
    $('#filterName').on('keyup change', function() {
        if(columns['name'] !== undefined) {
            table.column(columns['name']).search(this.value).draw();
        }
    });
    $('#filterCategory').on('change', function() {
        if(columns['category'] !== undefined) {
            table.column(columns['category']).search(this.value).draw();
        }
    });

    $('#editItemModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var item = button.data('item');
        var modal = $(this);
        
        // ** THIS IS THE CORRECTED LOOP **
        // It finds form fields by their 'name' attribute, which is more reliable.
        for (const key in item) {
            modal.find('[name="' + key + '"]').val(item[key]);
        }
    });
    
    $('#editItemForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var submitButton = $(this).closest('.modal-content').find('button[type="submit"]');
        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '<?= $updateUrl ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#editItemModal').modal('hide');
                showAlert('<strong>Success!</strong> ' + response.message, 'success');
                setTimeout(() => { location.reload(); }, 1500);
            },
            error: function(xhr) {
                let errorMsg = 'Could not update item.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                showAlert(`<strong>Error!</strong> ${errorMsg}`, 'danger');
            },
            complete: function() {
                 submitButton.prop('disabled', false).text('Save Changes');
            }
        });
    });

    function showAlert(message, type) {
        $('#alert-placeholder').html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`);
    }
});
</script>