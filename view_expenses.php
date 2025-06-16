<?php
session_start();

if (!isset($_SESSION['accountant_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$vertical_id = $_SESSION['vertical_id'];
$username = $_SESSION['username'];

$selected_month = $_POST['month'] ?? date('m');
$selected_year = $_POST['year'] ?? date('Y');
$selected_category = $_POST['category'] ?? 'billing'; // Default Billing
$selected_process = $_POST['process'] ?? '';

// Before the SQL queries:
$table_prefix = '';
if ($selected_category == 'it') {
    $table_prefix = 'it_expenses.';
} elseif ($selected_category == 'telecom') {
    $table_prefix = 'telecom_expense.';
} else {
    $table_prefix = ''; // billing or infra (single table queries)
}

$condition = "MONTH({$table_prefix}created_at) = '$selected_month' AND YEAR({$table_prefix}created_at) = '$selected_year'";

// Fetch processes
$processes = [];
$sql = "SELECT process_id, process_name FROM processes WHERE vertical_id = '$vertical_id'";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $processes[] = $row;
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

// Define getFields() and getTotals() functions outside the condition
function getFields($conn, $table) {
    $sql = "DESCRIBE $table";
    $result = $conn->query($sql);
    $fields = [];
    $excludeFields = ['id', 'vertical_id', 'process_id', 'created_at', 'updated_at'];
    while ($row = $result->fetch_assoc()) {
        $field = $row['Field'];
        $type = $row['Type'];
        if (preg_match('/^(int|decimal|float|double)/i', $type) && !in_array($field, $excludeFields)) {
            $fields[] = $field;
        }
    }
    return $fields;
}

function getTotals($conn, $table, $fields, $condition, $process_id) {
    $fieldTotals = [];
    foreach ($fields as $field) {
        $sql = "SELECT SUM($field) AS total FROM $table WHERE $condition AND process_id = '$process_id'";
        $result = $conn->query($sql);
        $fieldTotals[$field] = $result->fetch_assoc()['total'] ?? 0;
    }
    return $fieldTotals;
}

// New Part: Fetch data differently for IT and Telecom
$records = [];
$totalAmount = 0;

if ($selected_process != '') {
    if ($selected_category == 'it') {
        // Fetch vendors from it_vendors
        $sql = "SELECT it_expenses.vendor_id, it_expenses.amount, it_vendors.vendor_name 
        FROM it_expenses 
        JOIN it_vendors ON it_expenses.vendor_id = it_vendors.vendor_id
        WHERE MONTH(it_expenses.created_at) = '$selected_month' 
        AND YEAR(it_expenses.created_at) = '$selected_year'
        AND it_expenses.process_id = '$selected_process'";

        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
            $totalAmount += $row['amount']; // Add amount to total
        }
    } elseif ($selected_category == 'telecom') {
        // Fetch vendors from telecom_vendors
        $sql = "SELECT telecom_expense.vendor_id, telecom_expense.amount, telecom_vendors.vendor_name 
        FROM telecom_expense 
        JOIN telecom_vendors ON telecom_expense.vendor_id = telecom_vendors.vendor_id
        WHERE MONTH(telecom_expense.created_at) = '$selected_month' 
        AND YEAR(telecom_expense.created_at) = '$selected_year'
        AND telecom_expense.process_id = '$selected_process'";

        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
            $totalAmount += $row['amount']; // Add amount to total
        }
    } else {
        // Billing and Infra ka old method
        $table = ($selected_category == 'billing') ? 'billing' : 'infra';
        $fields = getFields($conn, $table);
        $totals = getTotals($conn, $table, $fields, $condition, $selected_process);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>koneXions P & L Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6fa; }
        .header { background: #003366; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-title { font-size: 24px; font-weight: bold; }
        .nav-menu { display: flex; gap: 25px; }
        .nav-item { color: white; text-decoration: none; font-size: 16px; }
        .chart-container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        form { text-align: center; margin-top: 20px; }
        select, button { padding: 10px; margin: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #003366; color: white; }
    </style>
</head>
<body>
<div class="header">
    <div class="header-title">koneXions P & L Dashboard</div>
    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Home</a>
        <a href="billing.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Billing Expense</a>
        <a href="infra_expense.php" class="nav-item"><i class="fas fa-building"></i> Infra Expense</a>
        <a href="it_asset_vendor.php" class="nav-item"><i class="fas fa-laptop"></i> IT Expense</a>
        <a href="telecom_expense.php" class="nav-item"><i class="fas fa-phone"></i> Telecom Expense</a>
        <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
        for ($y = $currentYear; $y >= 2025; $y--) {
            echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
        }
        ?>
    </select>

    <select name="category" required>
        <option value="">Select Category</option>
        <?php foreach ($categoryNames as $key => $value): ?>
            <option value="<?= $key ?>" <?= ($selected_category == $key) ? 'selected' : '' ?>><?= $value ?></option>
        <?php endforeach; ?>
    </select>

    <select name="process" required>
        <option value="">Select Process</option>
        <?php foreach ($processes as $process): ?>
            <option value="<?= $process['process_id'] ?>" <?= ($selected_process == $process['process_id']) ? 'selected' : '' ?>><?= $process['process_name'] ?></option>
        <?php endforeach; ?>
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
                        <td><?= number_format($row['amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align: right; font-weight: bold;">
            <p>Total Amount: ₹<?= number_format($totalAmount, 2) ?></p>
        </div>
    </div>
    <?php elseif (!empty($totals) && in_array($selected_category, ['billing', 'infra'])): ?>
    <div class="chart-container">
        <h3><?= $categoryNames[$selected_category] ?> Expense Summary</h3>
        <table>
    <thead>
        <tr>
            <th>Field</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($totals as $field => $value): ?>
            <?php
                $label = ucfirst(str_replace('_', ' ', $field));
                $formatted = ($field === 'profit_percent') ? number_format($value, 2) . '%' : '₹' . number_format($value, 2);
            ?>
            <tr>
                <td><?= $label ?></td>
                <td><?= $formatted ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
    </div>
<?php endif; ?>
</body>
</html>
