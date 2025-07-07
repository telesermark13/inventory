<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = intval($_POST['po_id']);
    $user_id = $_SESSION['user_id'];

    $fully_received = true;

    foreach ($_POST['received_qty'] as $index => $qty) {
        $item_id = intval($_POST['item_id'][$index]);
        $received_qty = floatval($qty);

        if ($received_qty <= 0) continue;

        // Fetch PO item details
        $stmt = $conn->prepare("SELECT quantity, quantity_received, item_id, unit_price FROM purchase_order_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item_data = $result->fetch_assoc();

        $ordered_qty = $item_data['quantity'];
        $existing_received = $item_data['quantity_received'];
        $inventory_item_id = $item_data['item_id'];
        $price = $item_data['unit_price'];

        $new_received = $existing_received + $received_qty;

        // Update PO item received quantity
        $stmt = $conn->prepare("UPDATE purchase_order_items SET quantity_received = ? WHERE id = ?");
        $stmt->bind_param("di", $new_received, $item_id);
        $stmt->execute();

        // Check if full
        if ($new_received < $ordered_qty) {
            $fully_received = false;
        }

        // Update inventory
        $stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("di", $received_qty, $inventory_item_id);
        $stmt->execute();

        // Log inventory movement
        $stmt = $conn->prepare("INSERT INTO inventory_movements (item_id, movement_type, quantity, price_nontaxed, reference_type, reference_id, user_id) VALUES (?, 'in', ?, ?, 'purchase_order', ?, ?)");
        $stmt->bind_param("idiii", $inventory_item_id, $received_qty, $price, $po_id, $user_id);
        $stmt->execute();
    }

    // Update PO status
    $status = $fully_received ? 'fully_received' : 'partially_received';
    $stmt = $conn->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $po_id);
    $stmt->execute();

    $_SESSION['success'] = "Purchase order successfully received.";
    header("Location: ../purchase_orders.php");
    exit;
}
?>
