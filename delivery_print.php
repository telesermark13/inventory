<?php
// /zaiko/delivery_print.php
require_once 'db.php';
$receipt_id = $_GET['id'];

$receipt = $conn->query("SELECT * FROM delivery_receipts WHERE id = $receipt_id")->fetch_assoc();
$items = $conn->query("SELECT di.quantity_delivered, i.name FROM delivery_items di 
                      JOIN items i ON di.item_id = i.id 
                      WHERE di.receipt_id = $receipt_id");
?>

<h1>Delivery Receipt</h1>
<p>Receipt #: <?= $receipt['receipt_number'] ?></p>
<p>Client: <?= $receipt['client'] ?></p>
<p>Project: <?= $receipt['project'] ?></p>
<p>Location: <?= $receipt['location'] ?></p>

<h3>Delivered Items:</h3>
<ul>
<?php while ($item = $items->fetch_assoc()): ?>
  <li><?= $item['name'] ?> — Qty: <?= $item['quantity_delivered'] ?></li>
<?php endwhile; ?>
</ul>
