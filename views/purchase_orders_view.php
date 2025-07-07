<?php ob_start(); // Start capturing output ?>
// zaiko/views/purchase_orders_view.php
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="bi bi-clipboard-check"></i> Purchase Orders</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="purchaseOrdersTable" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Status</th>
                        <th>Supplier</th>
                        <th>Invoice #</th>
                        <th class="text-end">Total (Est.)</th>
                        <th>Created By</th>
                        <th>Material Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><span class="badge bg-<?= get_status_badge_class($order['status']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td>
                            <td><?= htmlspecialchars($order['supplier_name']) ?></td>
                            <td><?= htmlspecialchars($order['sales_invoice_no'] ?? '') ?></td>
                            <td class="text-end">₱<?= number_format($order['grand_total_amount'] ?? 0, 2) ?></td>
                            <td><?= htmlspecialchars($order['created_by_name']) ?></td>
                            <td>MR-<?= $order['request_id'] ?></td>
                            <td>
                                <button class="btn btn-info btn-sm view-po" data-id="<?= $order['id'] ?>">View</button>
                                <?php // **FIX**: Only show Receive button if the order is not completed or cancelled ?>
                                <?php if ($order['status'] !== 'fully_received' && $order['status'] !== 'cancelled'): ?>
                                    <button class="btn btn-success btn-sm receive-po" data-id="<?= $order['id'] ?>">Receive</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Purchase Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewPoModalBody">
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="receivePoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Receive Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="receivePoModalBody">
        </div>
       <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="process-receipt-btn">Process & Update Stock</button>
      </div>
    </div>
  </div>
</div>


<?php
// A helper function to determine badge color based on status
function get_status_badge_class($status) {
    switch ($status) {
        case 'pending':
        case 'partially_received':
            return 'warning';
        case 'fully_received':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<script>
$(document).ready(function() {
    $('#purchaseOrdersTable').DataTable({ order: [[0, 'desc']] });

    // Handle "View" button click
    $('.view-po').on('click', function() {
        var poId = $(this).data('id');
        $('#viewPoModalBody').html('<p>Loading details...</p>');
        $('#viewPoModal').modal('show');
        $('#viewPoModalBody').load('ajax_get_po_details.php?po_id=' + poId);
    });

    // Handle "Receive" button click
    $('.receive-po').on('click', function() {
        var poId = $(this).data('id');
        $('#receivePoModalBody').html('<p>Loading form...</p>');
        $('#receivePoModal').modal('show');
        $('#receivePoModalBody').load('views/receive_po_modal_form.php?po_id=' + poId);
    });

    // **FIX**: Handle form submission for the Receive modal
    $('#process-receipt-btn').on('click', function() {
    var form = $('#receivePoForm');
    var formData = form.serialize();
    var submitButton = $(this); // The button that was clicked

    // Disable the button to prevent double-clicking
    submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

    $.ajax({
        url: 'process_po_receipt.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            // **THE FIX**: First, always hide the modal
            $('#receivePoModal').modal('hide');

            if (response.success) {
                // Then, show the success message on the main page
                $('#alert-placeholder').html(
                    '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    '<strong>Success!</strong> ' + response.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                );
                
                // Finally, reload the page after a short delay to see the changes
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                // Show an error alert if something went wrong on the backend
                $('#alert-placeholder').html(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<strong>Error:</strong> ' + (response.message || 'An unknown error occurred.') +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                );
            }
        },success: function(response) {
            // **THE FIX**: First, always hide the modal
            $('#receivePoModal').modal('hide');

            if (response.success) {
                // Then, show the success message on the main page
                $('#alert-placeholder').html(
                    '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    '<strong>Success!</strong> ' + response.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                );
                
                // Finally, reload the page after a short delay to see the changes
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                // Show an error alert if something went wrong on the backend
                $('#alert-placeholder').html(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<strong>Error:</strong> ' + (response.message || 'An unknown error occurred.') +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>'
                );
            }
        },
        error: function() {
            // Also hide the modal on a total failure
            $('#receivePoModal').modal('hide');
            alert('A critical error occurred. Please check the network tab or server logs.');
        },
        complete: function() {
            // Re-enable the button once the process is complete
            submitButton.prop('disabled', false).text('Process & Update Stock');
        }
    });
});
});
</script>

<?php $page_scripts = ob_get_clean(); ?>