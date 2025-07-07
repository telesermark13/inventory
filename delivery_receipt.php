<?php
// delivery_receipt.php (Controller)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// AJAX: Handle request for fetching items from a selected Purchase Order
if (isset($_GET['action']) && $_GET['action'] === 'fetch_po_items' && isset($_GET['po_id'])) {
    header('Content-Type: application/json');
    $po_id = (int)$_GET['po_id'];

    $q_po = $conn->prepare("SELECT po.*, s.name as supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = ?");
    $q_po->bind_param("i", $po_id);
    $q_po->execute();
    $po = $q_po->get_result()->fetch_assoc();
    $q_po->close();

    $items = [];
    if ($po) {
        $q_items = $conn->prepare(
            "SELECT
                poi.quantity, poi.unit as po_item_unit, poi.description as po_item_description, poi.sku as po_item_sku,
                i.id as item_id, i.name, i.serial_number as item_serial_number, i.quantity as stock_qty, i.unit_price, i.price_taxed, i.price_nontaxed
             FROM purchase_order_items poi
             LEFT JOIN items i ON poi.item_id = i.id AND poi.item_type = 'current'
             WHERE poi.order_id = ?"
        );
        $q_items->bind_param("i", $po_id);
        $q_items->execute();
        $res = $q_items->get_result();
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $q_items->close();
    }

    echo json_encode(['po' => $po, 'items' => $items]);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page_title = 'Create/Edit Delivery Receipt';

// 1. Prepare default form data
$is_edit_mode = isset($_GET['edit']) && is_numeric($_GET['edit']);
$delivery_number_from_get = $is_edit_mode ? (int)$_GET['edit'] : 0;
$form_data = [
    'is_edit' => $is_edit_mode, 'delivery_number' => 0, 'existing_data' => null,
    'existing_items_details' => [], 'all_inventory_items' => [], 'approved_purchase_orders' => [],
];

// 2. Fetch approved POs for the dropdown
$po_statuses = ['fully_received', 'partially_received'];
$q_pos = $conn->prepare("SELECT po.id, s.name as supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE po.status IN (?, ?) ORDER BY po.id DESC");
$q_pos->bind_param("ss", ...$po_statuses);
$q_pos->execute();
$res_pos = $q_pos->get_result();
while ($po = $res_pos->fetch_assoc()) {
    $form_data['approved_purchase_orders'][] = $po;
}
$q_pos->close();

// 3. Fetch all inventory items
$res_items = $conn->query("SELECT id, name, sku, serial_number, unit, quantity, description, unit_price, price_taxed, price_nontaxed FROM items");
while ($item = $res_items->fetch_assoc()) $form_data['all_inventory_items'][] = array_merge($item, ['type' => 'current']);

// 4. Edit mode logic
if ($is_edit_mode) {
    $form_data['delivery_number'] = $delivery_number_from_get;
    $q = $conn->prepare("SELECT * FROM delivery_receipts WHERE delivery_number = ?");
    $q->bind_param("i", $delivery_number_from_get);
    $q->execute();
    $form_data['existing_data'] = $q->get_result()->fetch_assoc();
    $q->close();
    if ($form_data['existing_data']) {
        $q_items = $conn->prepare("SELECT * FROM delivered_items WHERE delivery_number = ?");
        $q_items->bind_param("i", $delivery_number_from_get);
        $q_items->execute();
        $res = $q_items->get_result();
        while ($row = $res->fetch_assoc()) $form_data['existing_items_details'][] = $row;
        $q_items->close();
    } else {
        $form_data['is_edit'] = false;
        $_SESSION['error'] = "Delivery Receipt not found.";
    }
}

// 5. New DR Number logic
if (!$form_data['is_edit']) {
    $r = $conn->query("SELECT MAX(delivery_number) as max_dn FROM delivery_receipts");
    $row = $r->fetch_assoc();
    $form_data['delivery_number'] = $row['max_dn'] ? $row['max_dn'] + 1 : 1000;
}

// 6. Handle POST (submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $is_edit_from_post = isset($_POST['is_edit']) && $_POST['is_edit'] === '1';
    $delivery_number_from_post = (int)$_POST['delivery_number_hidden'];
    $client = trim($_POST['client']);
    $project = trim($_POST['project']);
    $location = trim($_POST['location']);
    $date_str = $_POST['date'];
    $delivery_date = date('Y-m-d', strtotime($date_str));
    $received_by_name = trim($_POST['received_by']);
    $prepared_by_user_id = $_SESSION['user_id'];

    if (empty($client) || empty($date_str)) {
        $_SESSION['error'] = "Client and Date fields are required.";
        header("Location: delivery_receipt.php" . ($is_edit_from_post ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }

    $submitted_line_items = $_POST['line_items'] ?? [];
    if (empty($submitted_line_items)) {
        $_SESSION['error'] = "At least one item must be added.";
        header("Location: delivery_receipt.php" . ($is_edit_from_post ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($is_edit_from_post) {
            // Restore logic...
        }

        // Upsert Header...
        if ($is_edit_from_post) {
            $stmt = $conn->prepare("UPDATE delivery_receipts SET client=?, project=?, location=?, date=?, received_by=?, prepared_by=? WHERE delivery_number=?");
            $stmt->bind_param("sssssii", $client, $project, $location, $delivery_date, $received_by_name, $prepared_by_user_id, $delivery_number_from_post);
        } else {
            $receipt_number_text = 'DR-' . date('Ymd') . '-' . $delivery_number_from_post;
            $stmt = $conn->prepare("INSERT INTO delivery_receipts (receipt_number, delivery_number, client, project, location, date, prepared_by, received_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sissssis", $receipt_number_text, $delivery_number_from_post, $client, $project, $location, $delivery_date, $prepared_by_user_id, $received_by_name);
        }
        $stmt->execute();
        $stmt->close();

        $total_qty_delivered_overall = 0;
        $total_outstanding_overall = 0;

        foreach ($submitted_line_items as $line_item) {
            // == THIS IS THE FIX ==
            // Assign trimmed values to variables BEFORE passing them to bind_param.
            $item_desc_trimmed = trim($line_item['description']);
            $serial_num_trimmed = trim($line_item['serial_number']);
            $unit_trimmed = trim($line_item['unit']);
            // ======================

            $ordered_val = (float)($line_item['ordered'] ?? 0);
            $delivered_val = (float)($line_item['delivered'] ?? 0);
            $outstanding_val = $ordered_val - $delivered_val;
            $unit_price = (float)($line_item['unit_price'] ?? 0);
            $price_taxed = (float)($line_item['price_taxed'] ?? 0);
            $price_nontaxed = (float)($line_item['price_nontaxed'] ?? 0);
            $total_nontaxed = $delivered_val * $price_nontaxed;

            $item_id = null;
            if (!empty($line_item['master_item_full_id']) && strpos($line_item['master_item_full_id'], '_') !== false) {
                list($type, $extracted_item_id) = explode('_', $line_item['master_item_full_id']);
                $item_id = is_numeric($extracted_item_id) ? (int)$extracted_item_id : null;
            }

            $item_stmt = $conn->prepare("INSERT INTO delivered_items (delivery_number, item_id, item_description, serial_number, ordered, delivered, outstanding, unit, unit_price, price_taxed, price_nontaxed, total_nontaxed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $item_stmt->bind_param(
                "iisssdddssdd",
                $delivery_number_from_post,
                $item_id,
                $item_desc_trimmed,    // Use the variable
                $serial_num_trimmed,   // Use the variable
                $ordered_val,
                $delivered_val,
                $outstanding_val,
                $unit_trimmed,         // Use the variable
                $unit_price,
                $price_taxed,
                $price_nontaxed,
                $total_nontaxed
            );
            $item_stmt->execute();
            $item_stmt->close();

            if ($item_id && $delivered_val > 0) {
                $conn->query("UPDATE items SET quantity = quantity - $delivered_val WHERE id = $item_id");
            }

            $total_qty_delivered_overall += $delivered_val;
            $total_outstanding_overall += $outstanding_val;
        }

        // Update DR Header with totals
        $is_completed_val = ($total_outstanding_overall <= 0) ? 1 : 0;
        $update_totals_stmt = $conn->prepare("UPDATE delivery_receipts SET total_quantity=?, outstanding=?, is_completed=? WHERE delivery_number=?");
        $update_totals_stmt->bind_param("ddii", $total_qty_delivered_overall, $total_outstanding_overall, $is_completed_val, $delivery_number_from_post);
        $update_totals_stmt->execute();
        $update_totals_stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Delivery receipt successfully saved!";
        header("Location: delivery_receipt.php?edit=" . $delivery_number_from_post);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error saving delivery receipt: " . $e->getMessage();
        header("Location: delivery_receipt.php" . ($is_edit_from_post ? "?edit=" . $delivery_number_from_post : ""));
        exit;
    }
}

// 7. Pass everything to the view
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$view = 'views/delivery_receipt_form.php';
include 'templates/layout.php';
?>