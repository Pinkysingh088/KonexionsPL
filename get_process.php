<?php
require_once 'db_connection.php';

if (isset($_POST['vertical_id'])) {
    $vertical_id = $_POST['vertical_id'];

    $stmt = $conn->prepare("SELECT process_id, process_name FROM processes WHERE vertical_id = ?");
    $stmt->bind_param("i", $vertical_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Process</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['process_id'] . '">' . $row['process_name'] . '</option>';
    }
}
?>
