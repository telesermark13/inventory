<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = $conn->query("SELECT MAX(id) as max_id FROM material_requests");
    $row = $result->fetch_assoc();
    $new_id = $row['max_id'] + 1;
    $request_number = "MRF-" . date("Ymd") . "-" . str_pad($new_id, 4, '0', STR_PAD_LEFT);

    $requested_by = $_POST['requested_by'];
    $project = $_POST['project'];
    $location = $_POST['location'];
    $items = $_POST['items']; // array of item_id => quantity

    $stmt = $conn->prepare("INSERT INTO material_requests (request_number, requested_by, project, location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $request_number, $requested_by, $project, $location);
    $stmt->execute();
    $material_request_id = $stmt->insert_id;

    foreach ($items as $item_id => $quantity) {
        $stmt = $conn->prepare("INSERT INTO material_request_items (material_request_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $material_request_id, $item_id, $quantity);
        $stmt->execute();
    }

    echo "Material Request Created Successfully!";
    exit;
}
?>

<!-- HTML form -->
<form method="POST">
    Request No: <input type="text" name="request_number" required><br>
    Requested By: <input type="text" name="requested_by"><br>
    Project: <input type="text" name="project"><br>
    Location: <input type="text" name="location"><br>

    <h3>Items</h3>
    <?php
    $items = $conn->query("SELECT id, name FROM items");
    while ($item = $items->fetch_assoc()) {
        echo "{$item['name']}: <input type='number' name='items[{$item['id']}]' min='0'><br>";
    }
    ?>
    <button type="submit">Create Material Request</button>
</form>