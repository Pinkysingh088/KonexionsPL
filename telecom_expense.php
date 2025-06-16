<?php
session_start();
include 'db_connection.php';

$vertical_id = $_SESSION['vertical_id'] ?? null;

if (!$vertical_id) {
    header("Location: login.php");
    exit;
}

// Get vertical name
$vertical_name = '';
$vertical_sql = "SELECT vertical_name FROM verticals WHERE vertical_id = $vertical_id";
$vertical_result = mysqli_query($conn, $vertical_sql);
if ($row = mysqli_fetch_assoc($vertical_result)) {
    $vertical_name = $row['vertical_name'];
}

// Fetch processes for vertical
$processes = [];
$query = "SELECT process_id, process_name FROM processes WHERE vertical_id = $vertical_id";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $processes[] = $row;
}

// Fetch telecom vendors
$vendors = [];
$query = "SELECT vendor_id, vendor_name FROM telecom_vendors WHERE vertical_id = $vertical_id";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $vendors[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $process_id = $_POST['process_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $vendor_amounts = $_POST['vendors'] ?? [];

    $telecom_total = 0.0;

    foreach ($vendor_amounts as $vendor_id => $amount) {
        if (is_numeric($amount) && $amount > 0) {
            $telecom_total += floatval($amount);
        }
    }

    foreach ($vendor_amounts as $vendor_id => $amount) {
        if (is_numeric($amount) && $amount > 0) {
            // Check if vendor exists
            $check_vendor_sql = "SELECT * FROM telecom_vendors WHERE vendor_id = ? AND vertical_id = ?";
            $stmt = $conn->prepare($check_vendor_sql);
            $stmt->bind_param("ii", $vendor_id, $vertical_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $insert_vendor_sql = "INSERT INTO telecom_vendors (vertical_id, vendor_name)
                                      VALUES (?, (SELECT vendor_name FROM vendors WHERE vendor_id = ?))";
                $stmt = $conn->prepare($insert_vendor_sql);
                $stmt->bind_param("ii", $vertical_id, $vendor_id);
                $stmt->execute();
            }

            // Check existing expense
            $check_expense_sql = "SELECT * FROM telecom_expense WHERE vendor_id = ? AND process_id = ? AND start_date = ? AND end_date = ?";
            $stmt = $conn->prepare($check_expense_sql);
            $stmt->bind_param("iiss", $vendor_id, $process_id, $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $update_sql = "UPDATE telecom_expense SET amount = ?, telecom_total = ? WHERE vendor_id = ? AND process_id = ? AND start_date = ? AND end_date = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("diiiss", $amount, $telecom_total, $vendor_id, $process_id, $start_date, $end_date);
                $stmt->execute();
            } else {
                $insert_sql = "INSERT INTO telecom_expense (vendor_id, vertical_id, process_id, amount, start_date, end_date, telecom_total)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("iiidsss", $vendor_id, $vertical_id, $process_id, $amount, $start_date, $end_date, $telecom_total);
                $stmt->execute();
            }
        }
    }

    header("Location: telecom_expense.php?msg=Telecom expenses saved successfully");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
    <title>Telecom Expense Entry</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f4fa;
            padding: 40px;
        }
        .form-container {
            max-width: 1100px;
            margin: auto;
            background: #ffffff;
            padding: 35px 45px;
            border-radius: 16px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12);
            border-top: 6px solid #1b2a49;
        }
        h2 {
            text-align: center;
            color: #1b2a49;
            margin-bottom: 30px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
        }
        label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 80%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }
        button {
            padding: 12px 28px;
            margin: 0 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .submit-btn {
            background-color: #1b2a49;
            color: white;
        }
        .submit-btn:hover {
            background-color: #2c3e75;
            transform: scale(1.05);
        }
        .reset-btn {
            background-color: #c0392b;
            color: white;
        }
        .reset-btn:hover {
            background-color: #e74c3c;
            transform: scale(1.05);
        }
        .back-btn {
            background-color: #27ae60;
            color: white;
        }
        .back-btn:hover {
            background-color: #2ecc71;
            transform: scale(1.05);
        }
        .actions button:focus {
            outline: none;
        }
    </style>
    <script>
        function calculateTotal() {
            let total = 0;
            const fields = document.querySelectorAll('.vendor-input');
            fields.forEach(field => {
                total += parseFloat(field.value) || 0;
            });
            document.getElementById('telecom_total').value = total.toFixed(2);
        }
    </script>
</head>
<body>

<div class="form-container">
    <h2>Telecom Expense Entry</h2>
    <form method="post" action="telecom_expense.php">
        <div class="grid">
            <div>
                <label>Vertical</label>
                <input type="text" value="<?= htmlspecialchars($vertical_name) ?>" disabled>
                <input type="hidden" name="vertical_id" value="<?= $vertical_id ?>">
            </div>
            <div>
                <label>Process</label>
                <select name="process_id" required>
                    <option value="">-- Select Process --</option>
                    <?php foreach ($processes as $process): ?>
                        <option value="<?= $process['process_id'] ?>"><?= htmlspecialchars($process['process_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Start Date</label>
                <input type="date" name="start_date" required>
            </div>
            <div>
                <label>End Date</label>
                <input type="date" name="end_date" required>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Vendor-wise Expenses</h3>
        <div class="grid">
            <?php foreach ($vendors as $vendor): ?>
                <div>
                    <label><?= htmlspecialchars($vendor['vendor_name']) ?></label>
                    <input type="number" step="0.01" name="vendors[<?= $vendor['vendor_id'] ?>]" class="vendor-input" oninput="calculateTotal()" placeholder="Amount">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid" style="margin-top: 20px;">
            <div>
                <label>Total</label>
                <input type="number" id="telecom_total" name="telecom_total" readonly>
            </div>
        </div>

        <div class="actions">
            <button type="button" class="back-btn" onclick="window.location.href='dashboard.php'">Back</button>
            <button type="reset" class="reset-btn">Reset</button>
            <button type="submit" class="submit-btn">Save</button>
        </div>
    </form>
</div>

</body>
</html>
