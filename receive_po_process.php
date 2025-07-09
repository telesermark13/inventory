<?php
session_start();
require_once '../includes/db_connect.php'; // Adjust path if needed
require_once '../includes/purchase_functions.php'; // For update_po_status_after_receiving

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = $_POST['po_id'];
    $sales_invoice_no = $_POST['sales_invoice_no'];
    $notes = $_POST['notes'];
    $received_by_user_id = $_SESSION['user_id']; // Make sure user_id is in the session

    // === BEGIN TRANSACTION ===
    $conn->begin_transaction();

    try {
        // Step 1: Get all items associated with this purchase order
        $po_items_sql = "SELECT id, item_id, quantity FROM purchase_order_items WHERE order_id = ?";
        $stmt_po_items = $conn->prepare($po_items_sql);
        $stmt_po_items->bind_param("i", $po_id);
        $stmt_po_items->execute();
        $po_items_result = $stmt_po_items->get_result();

        if ($po_items_result->num_rows === 0) {
            throw new Exception("CRITICAL: No items found for this Purchase Order (ID: $po_id).");
        }

        while ($item = $po_items_result->fetch_assoc()) {
            $item_id = $item['item_id'];
            $quantity_received = $item['quantity']; // Assuming full receipt

            // Step 2: UPDATE THE INVENTORY in the 'items' table (This was the missing step)
            $update_inventory_sql = "UPDATE items SET quantity = quantity + ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_inventory_sql);
            $stmt_update->bind_param("ii", $quantity_received, $item_id);
            $stmt_update->execute();
            if ($stmt_update->affected_rows === 0) {
                // This could mean the item_id doesn't exist in the 'items' table, which is a data integrity issue.
                // For now, we throw an error. You could also choose to INSERT it if it doesn't exist.
                throw new Exception("Failed to update inventory for item ID: $item_id. Item not found in inventory.");
            }
            $stmt_update->close();

            // Step 3: LOG THE MOVEMENT in 'inventory_movements' for an audit trail
            $log_movement_sql = "INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, reference_id, user_id) VALUES (?, 'in', ?, 'purchase_order', ?, ?)";
            $stmt_log = $conn->prepare($log_movement_sql);
            $stmt_log->bind_param("iiii", $item_id, $quantity_received, $po_id, $received_by_user_id);
            $stmt_log->execute();
            $stmt_log->close();

            // Step 4: Update the 'quantity_received' in the 'purchase_order_items' table
            $update_po_item_sql = "UPDATE purchase_order_items SET quantity_received = quantity_received + ? WHERE id = ?";
            $stmt_po_item_update = $conn->prepare($update_po_item_sql);
            $stmt_po_item_update->bind_param("ii", $quantity_received, $item['id']);
            $stmt_po_item_update->execute();
            $stmt_po_item_update->close();
        }
        $stmt_po_items->close();

        // Step 5: Update the main PO status
        update_po_status_after_receiving($conn, $po_id);

        // Step 6: Log notes and Sales Invoice
        $full_notes = "\nReceived on " . date('Y-m-d') . ". Notes: " . $notes;
        $update_notes_sql = "UPDATE purchase_orders SET notes = CONCAT(IFNULL(notes,''), ?), sales_invoice_no = ? WHERE id = ?";
        $stmt_notes = $conn->prepare($update_notes_sql);
        $stmt_notes->bind_param("ssi", $full_notes, $sales_invoice_no, $po_id);
        $stmt_notes->execute();
        $stmt_notes->close();
        
        // If all steps succeeded, commit the transaction
        $conn->commit();
        $_SESSION['success'] = 'PO received and inventory updated successfully!';

    } catch (Exception $e) {
        // If any step failed, roll back all database changes
        $conn->rollback();
        $_SESSION['error'] = 'Transaction Failed: ' . $e->getMessage();
    }

    header('Location: ../purchase_orders.php'); // Redirect back to the PO list
    exit();
}
?>