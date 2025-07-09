<?php
// zaiko/process_po_receipt.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

// CSRF check
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    exit(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']));
}

$po_id = filter_input(INPUT_POST, 'po_id', FILTER_VALIDATE_INT);
$sales_invoice_no = trim($_POST['sales_invoice_no'] ?? '');
$receiving_notes = trim($_POST['po_notes'] ?? ''); // From your form
$submitted_items = $_POST['items'] ?? [];

if (!$po_id || empty($submitted_items) || $sales_invoice_no === '') {
    exit(json_encode(['success' => false, 'message' => 'Missing PO ID, items, or Sales Invoice Number.']));
}

$conn->begin_transaction();
try {
    foreach ($submitted_items as $item_data) {
        $po_item_id = filter_var($item_data['po_item_id'], FILTER_VALIDATE_INT);
        $master_item_id = filter_var($item_data['master_item_id'], FILTER_VALIDATE_INT);
        $qty_receiving_now = filter_var($item_data['qty_receiving_now'], FILTER_VALIDATE_FLOAT);
        
        if ($qty_receiving_now > 0) {
            // 1. Update the quantity received on the specific PO line item
            $stmt_poi = $conn->prepare("UPDATE purchase_order_items SET quantity_received = quantity_received + ? WHERE id = ? AND order_id = ?");
            $stmt_poi->bind_param('dii', $qty_receiving_now, $po_item_id, $po_id);
            $stmt_poi->execute();
            $stmt_poi->close();

            // 2. Update the main inventory stock in the `items` table
            // IMPORTANT: This assumes a record in `items` exists for this `master_item_id`. 
            // Your `materials_request_approve.php` script should ensure this.
            $stmt_items = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE master_item_id = ?");
            $stmt_items->bind_param('di', $qty_receiving_now, $master_item_id);
            $stmt_items->execute();
            $stmt_items->close();
            
            // 3. (Recommended) Log this transaction in inventory_movements
            // You would add this logic here if you have the inventory_movements table set up.
        }
    }

    // 4. Recalculate totals to determine the new overall PO status
    $stmt_check = $conn->prepare("SELECT SUM(quantity) as total_ordered, SUM(quantity_received) as total_received FROM purchase_order_items WHERE order_id = ?");
    $stmt_check->bind_param('i', $po_id);
    $stmt_check->execute();
    $totals = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $total_ordered = (float)($totals['total_ordered'] ?? 0);
    $total_received = (float)($totals['total_received'] ?? 0);
    
    $new_status = 'ordered'; // Default status
    if ($total_received > 0.001 && $total_received < $total_ordered) {
        $new_status = 'partially_received';
    } elseif ($total_received >= $total_ordered) {
        $new_status = 'fully_received';
    }

    // 5. Update the main purchase order status and invoice number
    $note_to_append = "\nReceived on " . date('Y-m-d') . ". Invoice: " . $sales_invoice_no;
    $stmt_po = $conn->prepare("UPDATE purchase_orders SET status = ?, sales_invoice_no = ?, po_notes = CONCAT(COALESCE(po_notes, ''), ?) WHERE id = ?");
    $stmt_po->bind_param('sssi', $new_status, $sales_invoice_no, $note_to_append, $po_id);
    $stmt_po->execute();
    $stmt_po->close();

    $conn->commit();
    $_SESSION['success'] = "Successfully received items for PO #{$po_id}.";
    echo json_encode(['success' => true, 'message' => $_SESSION['success']]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Receive PO Error for PO #{$po_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please check server logs.']);
}

exit();