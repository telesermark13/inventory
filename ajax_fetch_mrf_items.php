<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
$id = intval($_GET['id']);

$result = $conn->query("SELECT mri.quantity, i.name FROM material_request_items mri 
                        JOIN items i ON mri.item_id = i.id 
                        WHERE mri.material_request_id = $id");

while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>{$row['quantity']}</td></tr>";
}
?>
