<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';
$username = $_SESSION['username'];

$selected_month = $_POST['month'] ?? date('m');
$selected_year = $_POST['year'] ?? date('Y');
$selected_vertical = $_POST['vertical'] ?? '';
$selected_category = $_POST['category'] ?? 'billing';
$selected_process = $_POST['process'] ?? '';

$table_prefix = '';
if ($selected_category == 'it') {
    $table_prefix = 'it_expenses.';
} elseif ($selected_category == 'telecom') {
    $table_prefix = 'telecom_expense.';
}

$condition = "MONTH({$table_prefix}created_at) = '$selected_month' AND YEAR({$table_prefix}created_at) = '$selected_year'";

// Fetch verticals
$verticals = [];
$result = $conn->query("SELECT vertical_id, vertical_name FROM verticals");
while ($row = $result->fetch_assoc()) {
    $verticals[] = $row;
}

// Fetch processes according to selected vertical
$processes = [];
if ($selected_vertical) {
    $stmt = $conn->prepare("SELECT process_id, process_name FROM processes WHERE vertical_id = ?");
    $stmt->bind_param("i", $selected_vertical);
    $stmt->execute();
    $processes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$categoryNames = [
    'billing' => 'Billing', 
    'infra' => 'Infra',
    'it' => 'IT Asset and Vendors',
    'telecom' => 'Telecom'
];

$months = [
    '01' => 'January', '02' => 'February', '03' => 'March',
    '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September',
    '10' => 'October', '11' => 'November', '12' => 'December'
];

function getFields($conn, $table) {
    $result = $conn->query("DESCRIBE $table");
    $fields = [];
    $exclude = ['id', 'vertical_id', 'process_id', 'created_at', 'updated_at'];
    while ($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        if (preg_match('/^(int|decimal|float|double)/i', $row['Type']) && !in_array($field, $exclude)) {
            $fields[] = $field;
        }
    }
    return $fields;
}

function getTotals($conn, $table, $fields, $condition, $process_id) {
    $totals = [];
    foreach ($fields as $field) {
        $sql = "SELECT SUM($field) AS total FROM $table WHERE $condition AND process_id = '$process_id'";
        $result = $conn->query($sql);
        $totals[$field] = $result->fetch_assoc()['total'] ?? 0;
    }
    return $totals;
}

// Fetch records
$records = [];
$totalAmount = 0;
$totals = [];

if ($selected_process != '') {
    if ($selected_category == 'it') {
        $sql = "SELECT it_expenses.vendor_id, it_expenses.amount, it_vendors.vendor_name 
                FROM it_expenses 
                JOIN it_vendors ON it_expenses.vendor_id = it_vendors.vendor_id
                WHERE MONTH(it_expenses.created_at) = '$selected_month' 
                AND YEAR(it_expenses.created_at) = '$selected_year'
                AND it_expenses.process_id = '$selected_process'";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
            $totalAmount += $row['amount'];
        }
    } elseif ($selected_category == 'telecom') {
        $sql = "SELECT telecom_expense.vendor_id, telecom_expense.amount, telecom_vendors.vendor_name 
                FROM telecom_expense 
                JOIN telecom_vendors ON telecom_expense.vendor_id = telecom_vendors.vendor_id
                WHERE MONTH(telecom_expense.created_at) = '$selected_month' 
                AND YEAR(telecom_expense.created_at) = '$selected_year'
                AND telecom_expense.process_id = '$selected_process'";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
            $totalAmount += $row['amount'];
        }
    } else {
        $table = ($selected_category == 'billing') ? 'billing' : 'infra';
        $fields = getFields($conn, $table);
        $totals = getTotals($conn, $table, $fields, $condition, $selected_process);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>koneXions P & L Dashboard</title>
    <meta charset="UTF-8">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
    <style>
        body { font-family: Arial; background: #f4f6fa; }
        .header { background: #003366; color: #fff; padding: 15px; display: flex; justify-content: space-between; }
        .nav-menu a {
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.nav-menu a:hover {
    background-color: #0055a5;
}

        form { text-align: center; margin-top: 20px; }
        select, button { padding: 10px; margin: 5px; }
        .chart-container { width: 90%; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        table {
    width: 100%;
    margin-top: 30px;
    border-collapse: separate;
    border-spacing: 0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    overflow: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header Styling */
th {
    background: linear-gradient(to right, #003366, #0055a5);
    color: #fff;
    font-weight: bold;
    text-align: left;
    padding: 14px 16px;
    border-bottom: 2px solid #004080;
}

/* Cell Styling */
td {
    padding: 12px 16px;
    border-bottom: 1px solid #e6e6e6;
    color: #333;
    background-color: #fdfdfd;
}

/* Alternate Row Colors */
tr:nth-child(even) td {
    background-color: #f4f8fb;
}

tr:nth-child(odd) td {
    background-color: #ffffff;
}

/* Hover Effect */
tr:hover td {
    background-color: #e0f0ff;
    transition: background-color 0.3s;
}

/* Rounded Corners on First and Last Cells */
tr:first-child th:first-child {
    border-top-left-radius: 8px;
}
tr:first-child th:last-child {
    border-top-right-radius: 8px;
}
tr:last-child td:first-child {
    border-bottom-left-radius: 8px;
}
tr:last-child td:last-child {
    border-bottom-right-radius: 8px;
}

    </style>
</head>
<body>
<!-- Add this in your <head> section if not already present -->


<!-- Header Navigation -->
<div class="header" style="background-color: #003366; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center;">
<div class="title" style="font-size: 20px; font-weight: bold;">
  kone<span style="color: orange;">X</span>ions
</div>

    <div class="nav-menu" style="display: flex; gap: 20px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none;">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="admin_view.php" style="color: white; text-decoration: none;">
        <i class="fas fa-eye"></i> Process Expense View
        </a>
        <a href="admin_total_view.php" style="color: white; text-decoration: none;">
        <i class="fas fa-table"></i> Total Expense View
        </a>
        <a href="logout.php" style="color: white; text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<form method="POST" action="">
    <select name="month" required>
        <option value="">Select Month</option>
        <?php foreach ($months as $num => $name): ?>
            <option value="<?= $num ?>" <?= ($selected_month == $num) ? 'selected' : '' ?>><?= $name ?></option>
        <?php endforeach; ?>
    </select>

    <select name="year" required>
        <option value="">Select Year</option>
        <?php
        $currentYear = date("Y");
        for ($y = $currentYear; $y >= 2024; $y--) {
            echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
        }
        ?>
    </select>

    <select name="vertical" id="verticalDropdown" required>
        <option value="">Select Vertical</option>
        <?php foreach ($verticals as $vertical): ?>
            <option value="<?= $vertical['vertical_id'] ?>" <?= ($selected_vertical == $vertical['vertical_id']) ? 'selected' : '' ?>>
                <?= $vertical['vertical_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="category" id="categoryDropdown" required>
        <option value="">Select Category</option>
        <?php foreach ($categoryNames as $key => $value): ?>
            <option value="<?= $key ?>" <?= ($selected_category == $key) ? 'selected' : '' ?>><?= $value ?></option>
        <?php endforeach; ?>
    </select>
    
    <select name="process" id="processDropdown" required>
    <option value="">Select Process</option>
    <?php if (!empty($processes)) : ?>
        <?php foreach ($processes as $process): ?>
            <option value="<?= $process['process_id'] ?>" <?= ($selected_process == $process['process_id']) ? 'selected' : '' ?>>
                <?= $process['process_name'] ?>
            </option>
        <?php endforeach; ?>
    <?php endif; ?>
</select>

    <button type="submit">Filter</button>
</form>

<?php if ($selected_category == 'it' || $selected_category == 'telecom'): ?>
<div class="chart-container">
    <h3><?= $categoryNames[$selected_category] ?> Expense Details</h3>
    <table>
        <thead>
            <tr>
                <th>Vendor Name</th>
                <th>Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                    <td>₹<?= number_format($row['amount'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="text-align: right; font-weight: bold;">Total Amount: ₹<?= number_format($totalAmount, 2) ?></div>
</div>

<?php elseif (!empty($totals)): ?>
<div class="chart-container">
    <h3><?= $categoryNames[$selected_category] ?> Summary</h3>
    <table>
        <thead><tr><th>Field</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($totals as $field => $value): ?>
            <tr>
                <td><?= ucfirst(str_replace('_', ' ', $field)) ?></td>
                <td>
                <?= strpos($field, 'profit') !== false ? number_format($value, 2) . '%' : '₹' . number_format($value, 2) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<script>
$(document).ready(function () {
    $('#verticalDropdown').change(function () {
        var verticalId = $(this).val();
        if (verticalId !== '') {
            $.ajax({
                url: 'get_process.php',
                type: 'POST',
                data: { vertical_id: verticalId },
                success: function (data) {
                    $('#processDropdown').html(data);
                }
            });
        } else {
            $('#processDropdown').html('<option value="">Select Process</option>');
        }
    });
});
</script>

</body>
</html>
