<!-- delivery_create.php -->
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php'; 
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<form method="POST" action="delivery_create_submit.php">
    Delivery No: <input type="number" name="delivery_number" required><br>
    Receipt No: <input type="text" name="receipt_number" required><br>

    Material Request:
    <select name="material_request_id" id="material_request_id" required>
        <option value="">-- Select --</option>
        <?php
        $mrf = $conn->query("SELECT id, request_number FROM material_requests");
        while ($row = $mrf->fetch_assoc()) {
            echo "<option value='{$row['id']}'>{$row['request_number']}</option>";
        }
        ?>
    </select><br>

    Client: <input type="text" name="client"><br>
    Project: <input type="text" name="project"><br>
    Location: <input type="text" name="location"><br>

    <h3>Items</h3>
    <table id="item_table" border="1">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <button type="submit">Generate Delivery Receipt</button>
</form>

<script>
    $('#material_request_id').change(function() {
        var mrfId = $(this).val();
        if (!mrfId) return;

        $.get('ajax_fetch_mrf_items.php', {
            id: mrfId
        }, function(data) {
            $('#item_table tbody').html(data);
        });
    });
</script>