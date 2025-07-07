<?php
// FILE: zaiko/dashboard.php (The Controller)

// Authentication and Database
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// --- DATA FETCHING ---

// Get primary inventory statistics
$stats = $conn->query("
    SELECT
        COUNT(*) as total_items,
        SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_items
    FROM items
")->fetch_assoc();

// Get total inventory value
$inventory_value = $conn->query("
    SELECT
        SUM(unit_price * quantity) as total_nontaxed_value,
        SUM(unit_price * quantity * IF(taxable=1, 1.12, 1)) as total_taxed_value
    FROM items
")->fetch_assoc();

// Get inventory movement for the line chart (last 7 days)
$movement_dates = [];
$items_added_series = [];
$items_removed_series = [];
$movement_res = $conn->query("
    SELECT DATE(created_at) as day,
        SUM(CASE WHEN movement_type='in' THEN quantity ELSE 0 END) as added,
        SUM(CASE WHEN movement_type='out' THEN quantity ELSE 0 END) as removed
    FROM inventory_movements
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 DAY)
    GROUP BY day ORDER BY day ASC
");
while ($row = $movement_res->fetch_assoc()) {
  $movement_dates[] = date('D', strtotime($row['day']));
  $items_added_series[] = (int)$row['added'];
  $items_removed_series[] = (int)$row['removed'];
}

// Get low stock items
$low_stocks = $conn->query("SELECT * FROM items WHERE quantity <= min_stock_level ORDER BY quantity ASC")->fetch_all(MYSQLI_ASSOC);
$low_stocks_old = $conn->query("SELECT * FROM old_stocks WHERE quantity <= min_stock_level ORDER BY quantity ASC")->fetch_all(MYSQLI_ASSOC);

// Get pending deliveries
$pending_deliveries = $conn->query("SELECT COUNT(*) as count FROM delivery_receipts WHERE outstanding > 0")->fetch_assoc()['count'];

// Get recent activity
$recent_activity = $conn->query("
    SELECT m.*, i.name as item_name, u.username as user
    FROM inventory_movements m
    JOIN items i ON m.item_id = i.id
    JOIN users u ON m.user_id = u.id
    ORDER BY m.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// ---- PAGE SETUP ----
$page_title = 'Dashboard';
$view = 'views/dashboard.php'; // Defines the path to the view file

// Load the main template, which will in turn load the view
include 'templates/layout.php';