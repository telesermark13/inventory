<?php
// zaiko/process_po_receipt.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: purchase_orders.php");
    exit;
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$received_by = $_SESSION['user_id'];
$date_received = $_POST['date_received'];
$notes = $_POST['notes'];
$sales_invoice_no = $_POST['sales_invoice_no'];
$items_received = $_POST['quantity_received'] ?? [];
$item_ids = $_POST['item_id'] ?? [];
$serial_numbers = $_POST['serial_numbers'] ?? [];

if (!$order_id) {
    $_SESSION['error'] = "Invalid Purchase Order ID.";
    header('Location: purchase_orders.php');
    exit;
}

$conn->begin_transaction();

try {
    // Loop through each item from the form
    for ($i = 0; $i < count($item_ids); $i++) {
        $item_id = (int)$item_ids[$i];
        $quantity_to_receive = (float)$items_received[$i];

        if ($quantity_to_receive > 0) {
            // 1. Update the quantity_received in purchase_order_items
            $update_poi_sql = "UPDATE purchase_order_items SET quantity_received = quantity_received + ? WHERE order_id = ? AND item_id = ?";
            $stmt_poi = $conn->prepare($update_poi_sql);
            $stmt_poi->bind_param('dii', $quantity_to_receive, $order_id, $item_id);
            $stmt_poi->execute();
            $stmt_poi->close();

            // 2. Update the main inventory quantity in the items table
            $update_items_sql = "UPDATE items SET quantity = quantity + ? WHERE id = ?";
            $stmt_items = $conn->prepare($update_items_sql);
            $stmt_items->bind_param('di', $quantity_to_receive, $item_id);
            $stmt_items->execute();
            $stmt_items->close();

            // 3. Log the inventory movement
            $movement_type = 'in';
            $reference_type = 'delivery';
            $insert_log_sql = "INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, reference_id, user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_log = $conn->prepare($insert_log_sql);
            $stmt_log->bind_param('isdsii', $item_id, $movement_type, $quantity_to_receive, $reference_type, $order_id, $received_by);
            $stmt_log->execute();
            $stmt_log->close();
        }
    }

    // --- Status Calculation Logic ---
    // Recalculate the total ordered vs. total received for the entire PO
    $total_ordered = 0;
    $total_received = 0;
    $check_sql = "SELECT quantity, quantity_received FROM purchase_order_items WHERE order_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $order_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $total_ordered += $row['quantity'];
        $total_received += $row['quantity_received'];
    }
    $check_stmt->close();

    // Determine the new status based on the totals
    if (floatval($total_received) >= floatval($total_ordered)) {
        $new_status = 'fully_received';
    } elseif (floatval($total_received) > 0) {
        $new_status = 'partially_received';
    } else {
        // Fallback to the original status if nothing was received
        $status_sql = "SELECT status FROM purchase_orders WHERE id = ?";
        $status_stmt = $conn->prepare($status_sql);
        $status_stmt->bind_param('i', $order_id);
        $status_stmt->execute();
        $new_status = $status_stmt->get_result()->fetch_assoc()['status'];
        $status_stmt->close();
    }
    
    // 4. Update the main purchase_orders table status and notes
    $update_po_sql = "UPDATE purchase_orders SET status = ?, sales_invoice_no = ?, po_notes = CONCAT(COALESCE(po_notes, ''), ?) WHERE id = ?";
    $stmt_po = $conn->prepare($update_po_sql);
    $note_to_append = "\nReceived on $date_received by user ID $received_by. Invoice: $sales_invoice_no. Notes: $notes";
    $stmt_po->bind_param('sssi', $new_status, $sales_invoice_no, $note_to_append, $order_id);
    $stmt_po->execute();
    $stmt_po->close();

    $conn->commit();
    $_SESSION['success'] = "Successfully received items for PO #{$order_id}.";

} catch (Exception $e) {
    $conn->rollback();
    error_log("Receive PO Error for PO #{$order_id}: " . $e->getMessage());
    $_SESSION['error'] = "Error processing receipt: " . $e->getMessage();
}

header("Location: purchase_orders.php");
exit();
?>