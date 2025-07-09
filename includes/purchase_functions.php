<?php
// zaiko/includes/purchase_functions.php

/**
 * Updates the status of a purchase order based on the quantities of items received.
 *
 * @param mysqli $conn The database connection object.
 * @param int $po_id The ID of the purchase order to update.
 * @return void
 */
function update_po_status_after_receiving($conn, $po_id) {
    // Calculate total ordered and total received quantities
    $sql = "SELECT 
                SUM(quantity) as total_ordered, 
                SUM(quantity_received) as total_received 
            FROM purchase_order_items 
            WHERE order_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_ordered = (float)($result['total_ordered'] ?? 0);
    $total_received = (float)($result['total_received'] ?? 0);

    $new_status = 'pending'; // Default status

    if ($total_received > 0 && $total_received < $total_ordered) {
        $new_status = 'partially_received';
    } elseif ($total_received >= $total_ordered) {
        $new_status = 'fully_received';
    }

    // Update the purchase order's main status
    $update_sql = "UPDATE purchase_orders SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_sql);
    $stmt_update->bind_param("si", $new_status, $po_id);
    $stmt_update->execute();
    $stmt_update->close();
}