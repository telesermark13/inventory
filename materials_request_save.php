<?php
//zaiko/materials_request_save.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// CSRF validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $_SESSION['error'] = "CSRF token validation failed.";
    header("Location: materials_request.php");
    exit;
}

// Validate at least one item
if (!isset($_POST['items']) || !is_array($_POST['items'])) {
    $_SESSION['error'] = "Please add at least one item.";
    header("Location: materials_request.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) {
    $_SESSION['error'] = "User session not found. Please login again.";
    header("Location: login.php");
    exit;
}

$supplier_id = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$request_date_str = $_POST['request_date'] ?? date('Y-m-d');
$request_date = date('Y-m-d H:i:s', strtotime($request_date_str));

// This tax multiplier should match your JavaScript (1.12 for 12% VAT)
$tax_rate_multiplier = 1.12;

$conn->begin_transaction();

try {
    // Insert header
    $stmt = $conn->prepare(
        "INSERT INTO materials_requests (user_id, supplier_id, status, request_date, total_amount_nontaxed, total_tax_amount, grand_total_amount, processed_at)
         VALUES (?, ?, 'pending', ?, 0.00, 0.00, 0.00, NULL)"
    );
    $stmt->bind_param("iis", $user_id, $supplier_id, $request_date);
    $stmt->execute();
    $request_id = $conn->insert_id;
    $stmt->close();

    $sum_total_amount_nontaxed = 0;
    $sum_total_tax_amount = 0;
    $sum_grand_total_amount = 0;

    // Prepare statement for inserting items
    $sql = "INSERT INTO materials_request_items
    (request_id, master_item_id, name, description, sku, serial_number, unit, category, quantity, price, unit_price, taxable, subtotal, tax_amount, total)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $conn->prepare($sql);

    foreach ($_POST['items'] as $item) {
        // Validate required fields
        if (empty($item['item_id']) || floatval($item['qty']) <= 0 || empty($item['name']) || empty($item['category'])) {
            continue; // Skip invalid items
        }

        $master_item_id = (int)$item['item_id'];
        $name = $item['name'];
        $description = $item['description'] ?? '';
        $sku = $item['sku'] ?? '';
        $serial_number = $item['serial_number'] ?? ''; // Get the serial number
        $unit = $item['unit'] ?? '';
        $category = $item['category'];
        $quantity = (float)$item['qty'];
        $input_price = (float)($item['price'] ?? 0);
        $is_taxable = !empty($item['taxable']) ? 1 : 0;

        $price_nontaxed = 0;
        $price_taxed = 0;

        if ($is_taxable) {
            $price_nontaxed = $input_price;
            $price_taxed = $input_price * $tax_rate_multiplier;
        } else {
            $price_taxed = $input_price;
            $price_nontaxed = $input_price / $tax_rate_multiplier;
        }
        
        $subtotal = $quantity * $price_nontaxed;
        $total = $quantity * $price_taxed;
        $tax_amount = $total - $subtotal;
        
        $sum_total_amount_nontaxed += $subtotal;
        $sum_total_tax_amount += $tax_amount;
        $sum_grand_total_amount += $total;

        $stmt_item->bind_param(
            "iissssssddidddd",
            $request_id,
            $master_item_id,
            $name,
            $description,
            $sku,
            $serial_number,
            $unit,
            $category,
            $quantity,
            $price_taxed, 
            $price_nontaxed,
            $is_taxable,
            $subtotal,
            $tax_amount,
            $total
        );
        $stmt_item->execute();
    }
    $stmt_item->close();

    // Update totals on the main materials_requests record
    $stmt_update = $conn->prepare(
        "UPDATE materials_requests
         SET total_amount_nontaxed = ?, total_tax_amount = ?, grand_total_amount = ?
         WHERE id = ?"
    );
    $stmt_update->bind_param("dddi", $sum_total_amount_nontaxed, $sum_total_tax_amount, $sum_grand_total_amount, $request_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    $_SESSION['success'] = "Material request #{$request_id} submitted successfully!";
    header("Location: materials_request.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Material Request Save Error: " . $e->getMessage());
    $_SESSION['error'] = "Error saving request: " . $e->getMessage();
    header("Location: materials_request.php");
    exit();
}
?>