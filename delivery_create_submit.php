<?php
session_start();
require_once '../includes/db_connect.php'; // Adjust path if needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $delivery_number = $_POST['delivery_number'];
    $client = $_POST['client'];
    $project = $_POST['project'];
    $location = $_POST['location'];
    $received_by = $_POST['received_by'];
    $date = $_POST['date'];
    $comments = $_POST['comments'];
    $prepared_by = $_SESSION['user_id'];

    // === BEGIN TRANSACTION ===
    $conn->begin_transaction();

    try {
        // PRE-CHECK: First, loop through and verify stock for ALL items before making any changes.
        foreach ($_POST['item_id'] as $key => $item_id) {
            $quantity_to_deliver = (int)$_POST['delivered'][$key];
            if ($quantity_to_deliver <= 0) {
                continue; // Skip items with zero quantity
            }

            $check_sql = "SELECT name, quantity FROM items WHERE id = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("i", $item_id);
            $stmt_check->execute();
            $item_stock = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$item_stock || $item_stock['quantity'] < $quantity_to_deliver) {
                // If stock is insufficient, roll back and stop everything.
                throw new Exception("Not enough stock for item '{$item_stock['name']}'. Available: {$item_stock['quantity']}, Required: {$quantity_to_deliver}");
            }
        }

        // Step 1: Create the Delivery Receipt Header
        $receipt_number = 'DR-' . date('Ymd') . '-' . $delivery_number;
        $dr_header_sql = "INSERT INTO delivery_receipts (receipt_number, delivery_number, client, project, location, received_by, date, prepared_by, comments, is_completed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt_header = $conn->prepare($dr_header_sql);
        $stmt_header->bind_param("sisssssis", $receipt_number, $delivery_number, $client, $project, $location, $received_by, $date, $prepared_by, $comments);
        $stmt_header->execute();
        $dr_id = $stmt_header->insert_id;
        $stmt_header->close();

        // Step 2: Loop through items again to perform the database updates
        foreach ($_POST['item_id'] as $key => $item_id) {
            $quantity_delivered = (int)$_POST['delivered'][$key];
            if ($quantity_delivered <= 0) {
                continue;
            }

            // Step 2a: UPDATE (DECREMENT) INVENTORY in 'items' table
            $update_inventory_sql = "UPDATE items SET quantity = quantity - ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_inventory_sql);
            $stmt_update->bind_param("ii", $quantity_delivered, $item_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Step 2b: LOG THE MOVEMENT in 'inventory_movements'
            $log_movement_sql = "INSERT INTO inventory_movements (item_id, movement_type, quantity, reference_type, reference_id, user_id) VALUES (?, 'out', ?, 'delivery_receipt', ?, ?)";
            $stmt_log = $conn->prepare($log_movement_sql);
            $stmt_log->bind_param("iiii", $item_id, $quantity_delivered, $dr_id, $prepared_by);
            $stmt_log->execute();
            $stmt_log->close();

            // Step 2c: INSERT into 'delivered_items' table
            $di_sql = "INSERT INTO delivered_items (delivery_number, item_id, item_description, serial_number, ordered, delivered, unit, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_di = $conn->prepare($di_sql);
            $stmt_di->bind_param("iisssisd", $delivery_number, $item_id, $_POST['item_description'][$key], $_POST['serial_number'][$key], $_POST['ordered'][$key], $quantity_delivered, $_POST['unit'][$key], $_POST['unit_price'][$key]);
            $stmt_di->execute();
            $stmt_di->close();
        }

        // If all steps succeeded, commit the transaction
        $conn->commit();
        $_SESSION['success'] = 'Delivery Receipt created and inventory updated!';

    } catch (Exception $e) {
        // If any step failed, roll back all database changes
        $conn->rollback();
        $_SESSION['error'] = 'Transaction Failed: ' . $e->getMessage();
    }

    header('Location: ../delivery_receipt.php'); // Redirect back to the DR list
    exit();
}
?>