<?php
// zaiko/process_po_receipt.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => 'Invalid request method.']));
}

// --- FIX: Read the exact field names from your form ---
$order_id = filter_input(INPUT_POST, 'po_id', FILTER_VALIDATE_INT);
$sales_invoice_no = trim($_POST['sales_invoice_no'] ?? '');
$notes = trim($_POST['receiving_notes'] ?? '');
$quantities_received = $_POST['received_qty'] ?? [];
$item_ids = $_POST['item_id'] ?? [];


if (!$order_id || empty($item_ids)) {
    exit(json_encode(['success' => false, 'message' => 'Invalid data submitted.']));
}

$conn->begin_transaction();

try {
    for ($i = 0; $i < count($item_ids); $i++) {
        $item_id = (int)$item_ids[$i];
        $quantity_to_receive = (float)($quantities_received[$i] ?? 0);

        if ($quantity_to_receive > 0) {
            // Update quantity received for the specific line item
            // FIX: Uses item_id and order_id to find the correct line
            $update_poi_sql = "UPDATE purchase_order_items SET quantity_received = quantity_received + ? WHERE order_id = ? AND item_id = ?";
            $stmt_poi = $conn->prepare($update_poi_sql);
            $stmt_poi->bind_param('dii', $quantity_to_receive, $order_id, $item_id);
            $stmt_poi->execute();
            $stmt_poi->close();

            // Update the main inventory stock
            $update_items_sql = "UPDATE items SET quantity = quantity + ? WHERE id = ?";
            $stmt_items = $conn->prepare($update_items_sql);
            $stmt_items->bind_param('di', $quantity_to_receive, $item_id);
            $stmt_items->execute();
            $stmt_items->close();
        }
    }

    // Recalculate totals to determine the new status
    $total_ordered = 0;
    $total_received = 0;
    $check_stmt = $conn->prepare("SELECT quantity, quantity_received FROM purchase_order_items WHERE order_id = ?");
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $total_ordered += (float)$row['quantity'];
        $total_received += (float)$row['quantity_received'];
    }
    $check_stmt->close();

    // Determine the new status
    $new_status = 'ordered'; 
    if ($total_received > 0 && $total_received < $total_ordered) {
        $new_status = 'partially_received';
    } elseif ($total_received >= $total_ordered) {
        $new_status = 'fully_received';
    }

    // Update the main purchase order status and invoice number
    // FIX: Also updates notes correctly
    $note_to_append = "\nReceived on " . date('Y-m-d') . ". Invoice: $sales_invoice_no. Notes: $notes";
    $update_po_sql = "UPDATE purchase_orders SET status = ?, sales_invoice_no = ?, po_notes = CONCAT(COALESCE(po_notes, ''), ?) WHERE id = ?";
    $stmt_po = $conn->prepare($update_po_sql);
    $stmt_po->bind_param('sssi', $new_status, $sales_invoice_no, $note_to_append, $order_id);
    $stmt_po->execute();
    $stmt_po->close();

    $conn->commit();
    $_SESSION['success'] = "Successfully received items for PO #{$order_id}.";
    echo json_encode(['success' => true, 'message' => $_SESSION['success']]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Receive PO Error for PO #{$order_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error processing receipt. Check server logs.']);
}

exit();