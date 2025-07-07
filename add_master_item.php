<?php
require_once __DIR__ . '/includes/db.php';

// Sanitize & fetch form inputs
$name = trim($_POST['name'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$serial_number = trim($_POST['serial_number'] ?? '');
$description = trim($_POST['description'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$unit_price = isset($_POST['unit_price']) && $_POST['unit_price'] !== '' ? (float)$_POST['unit_price'] : null;
$min_stock_level = (int)($_POST['min_stock_level'] ?? 0);
$taxable = isset($_POST['taxable']) && $_POST['taxable'] === 'on';
$category = trim($_POST['category'] ?? '');

// Handle optional fields
$sku = $sku !== '' ? $sku : null;
$serial_number = $serial_number !== '' ? $serial_number : null;
$description = $description !== '' ? $description : null;
$unit = $unit !== '' ? $unit : null;
$category = $category !== '' ? $category : null;

// Calculate price with and without tax
$price_nontaxed = $unit_price;
$price_taxed = $taxable ? round($unit_price * 1.12, 2) : $unit_price;

try {
    $stmt = $conn->prepare("
        INSERT INTO master_items
            (name, sku, serial_number, description, unit, quantity, unit_price, min_stock_level, price_taxed, price_nontaxed, category)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "sssssidddds",
        $name,
        $sku,
        $serial_number,
        $description,
        $unit,
        $quantity,
        $unit_price,
        $min_stock_level,
        $price_taxed,
        $price_nontaxed,
        $category
    );

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "Database error: " . $stmt->error;
    }

    $stmt->close();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

exit;
