<?php
// Session start
session_start();

if (!isset($_SESSION['accountant_id'])) {
    header("Location: index.php");
    exit();
}

// Database Connection
require_once 'db_connection.php';

// Checking the assigned vertical to user
$vertical_id = $_SESSION['vertical_id'];
$username = $_SESSION['username'];

// Form Inputs
$selected_month = $_POST['month'] ?? date('m');
$selected_year = $_POST['year'] ?? date('Y');
$selected_category = $_POST['category'] ?? 'billing';

// sum of each field 
function getFieldTotals($conn, $table, $fields, $vertical_id, $selected_month, $selected_year) {
    $fieldTotals = [];
    foreach ($fields as $field) {
        $sql = "SELECT SUM($field) AS total FROM $table 
                WHERE ? BETWEEN MONTH(start_date) AND MONTH(end_date) 
                AND ? BETWEEN YEAR(start_date) AND YEAR(end_date)
                AND vertical_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $selected_month, $selected_year, $vertical_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $fieldTotals[$field] = $row['total'] ?? 0;
    }
    return $fieldTotals;
}

// IT Vendors fetch and sum
function getITVendors($conn, $vertical_id, $month, $year) {
    $vendors = [];
    $sql = "SELECT v.vendor_name, SUM(e.amount) as total_amount 
            FROM it_expenses e
            JOIN it_vendors v ON e.vendor_id = v.vendor_id
            WHERE e.vertical_id = ? 
            AND ? BETWEEN MONTH(e.start_date) AND MONTH(e.end_date)
            AND ? BETWEEN YEAR(e.start_date) AND YEAR(e.end_date)
            GROUP BY v.vendor_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $vertical_id, $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vendors[$row['vendor_name']] = $row['total_amount'];
    }

    return $vendors;
}

// Fetching category fields
$category_fields = [
    'billing' => [
        'table' => 'billing',
        'fields' => [
            'gross_billing' => 'Gross Billing',
            'less_tds' => 'Less TDS @ 10%',
            'net_billing' => 'Net Billing Post TDS Deduction',
            'management_cost' => 'Management Cost',
            'director_remuneration' => 'Director Remuneration',
            'outstanding' => 'Outstanding as on Date',
            'interest_capital' => 'Interest on Capital @ 2%',
            'total_billing' => 'Total Expenses',
            'net_profit' => 'Net Profit',
            'profit_percent' => 'Profit %'
        ]
    ],
    'infra' => [
        'table' => 'infra',
        'fields' => [
            'rent' => 'Rent',
            'electricity' => 'Electricity',
            'maintenance' => 'Maintenance',
            'agent_salary' => 'Agent Salary',
            'support_staff_salary' => 'Support Staff Salary',
            'esi_pf_contribution' => 'ESI/PF Contribution',
            'team_rr_incentive' => 'Team R&R Incentive',
            'central_team_cost' => 'Central Team Expense',
            'festival_expenses' => 'Festival Expenses',
            'petty_cash' => 'Petty Cash',
            'input_amount' => 'Input Amount',
            'misc_expenses' => 'Misc Expenses',
            'infra_total' => 'Infra Total',
        ]
    ],
    'it_asset' => [
        'table' => 'it_vendors',
        'fields' => getITVendors($conn, $vertical_id, $selected_month, $selected_year)
    ],
    'telecom' => [
        'table' => 'telecom_vendors',
        'fields' => [] // Filled dynamically below
    ]
];

$fieldTotals = [];

if ($selected_category === 'telecom') {
    $stmt = $conn->prepare("SELECT v.vendor_name, SUM(e.amount) AS amount 
                            FROM telecom_expense e 
                            JOIN telecom_vendors v ON e.vendor_id = v.vendor_id 
                            WHERE e.vertical_id = ? 
                            AND ? BETWEEN MONTH(e.start_date) AND MONTH(e.end_date)
                            AND ? BETWEEN YEAR(e.start_date) AND YEAR(e.end_date)
                            GROUP BY v.vendor_name");
    $stmt->bind_param("iii", $vertical_id, $selected_month, $selected_year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $category_fields['telecom']['fields'][$row['vendor_name']] = $row['vendor_name'];
        $fieldTotals[$row['vendor_name']] = $row['amount'] ?? 0;
    }

} elseif ($selected_category === 'it_asset') {
    foreach (array_keys($category_fields['it_asset']['fields']) as $vendor_name) {
        $stmt = $conn->prepare("SELECT SUM(e.amount) AS total 
                                FROM it_expenses e
                                JOIN it_vendors v ON e.vendor_id = v.vendor_id
                                WHERE v.vendor_name = ? AND e.vertical_id = ? 
                                AND ? BETWEEN MONTH(e.start_date) AND MONTH(e.end_date)
                                AND ? BETWEEN YEAR(e.start_date) AND YEAR(e.end_date)");
        $stmt->bind_param("siii", $vendor_name, $vertical_id, $selected_month, $selected_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $fieldTotals[$vendor_name] = $row['total'] ?? 0;
    }

} else {
    $table = $category_fields[$selected_category]['table'];
    $fields = array_keys($category_fields[$selected_category]['fields']);
    $fieldTotals = getFieldTotals($conn, $table, $fields, $vertical_id, $selected_month, $selected_year);

    if ($selected_category === 'billing') {
        $netProfit = $fieldTotals['net_profit'] ?? 0;
        $netBilling = $fieldTotals['net_billing'] ?? 0;
        $calculatedProfitPercent = ($netBilling > 0) ? ($netProfit / $netBilling) * 100 : 0;
        $fieldTotals['profit_percent'] = number_format($calculatedProfitPercent, 2);
    }
}

$category_total = 0;
if (in_array($selected_category, ['it_asset', 'telecom'])) {
    foreach ($fieldTotals as $amount) {
        $category_total += floatval($amount);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6fa; }
        .header { background: #003366; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-title { font-size: 24px; font-weight: bold; }
        .nav-menu {
  display: flex;
  gap: 25px;
}

.nav-item {
  color: white;
  text-decoration: none;
  font-size: 16px;
  transition: color 0.3s ease, background-color 0.3s ease; /* Smooth transition for color change */
}

/* Hover effect for nav items */
.nav-item:hover {
  color: #1abc9c; /* Change color on hover */
  background-color: rgba(255, 255, 255, 0.2); /* Add background color on hover for better visibility */
  border-radius: 5px; /* Optional: rounded corners for hover effect */
}

        .dropdown { position: relative; }
        .dropdown-content { display: none; position: absolute; background: #1a4876; min-width: 200px; border-radius: 4px; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a { color: white; padding: 10px; text-decoration: none; display: block; }
        .chart-container { max-width: 1200px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        form { text-align: center; margin-top: 20px; }
        select, button { padding: 10px; margin: 5px; }
        table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 30px;
}
.download-btn {
  color: white;
  text-decoration: none;
  font-size: 16px;
  padding: 6px 12px;
  cursor: pointer;
  background-color: orange;
  border-radius: 4px;
  transition: background-color 0.3s ease, color 0.3s ease;
  display: flex;
  align-items: center;
  gap: 5px;
}

.download-btn:hover {
  background-color: darkorange;
  color: #fff;
}

th, td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: left;
}

th {
  background: #003366;
  color: white;
}

/* Alternating row colors */
tr:nth-child(even) {
  background-color: #f2f2f2; /* Light gray for even rows */
}

tr:nth-child(odd) {
  background-color: #ffffff; /* White for odd rows */
}

/* Hover effect for table rows */
tr:hover {
  background-color: #f1f1f1; /* Light gray background when hovering over a row */
  cursor: pointer; /* Change cursor to pointer when hovering over a row */
}

            .header-title {
  font-size: 36px;
  font-weight: bold;
  text-align: center;
  color: white; /* Default color for the text */
}

.header-title .kone {
  color: white; /* 'kone' part in white */
}

.header-title .x {
  color: orange; /* 'X' part in orange */
}

.header-title span {
  display: inline-block;
}

    </style>
</head>
<body>
<div class="header">
    <div class="header-title">
  <span class="kone">kone</span><span class="x">X</span>ions P & L Dashboard
</div>

<div class="nav-menu"> 
    <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Home</a>
    <a href="billing.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Billing</a>
    <a href="infra_expense.php" class="nav-item"><i class="fas fa-building"></i> Infra</a>
    <a href="it_asset_vendor.php" class="nav-item"><i class="fas fa-laptop"></i> IT Asset</a>
    <a href="telecom_expense.php" class="nav-item"><i class="fas fa-phone"></i> Telecom</a>
    
    <div class="dropdown">
        <span class="nav-item"><i class="fas fa-tasks"></i> Manage Task</span>
        <div class="dropdown-content">
            <a href="add_process.php"><i class="fas fa-plus"></i> Add Process</a>
            <a href="manage_it_vendors.php"><i class="fas fa-laptop"></i> Manage IT Vendors</a>
            <a href="manage_telecom_vendors.php"><i class="fas fa-phone"></i> Manage Telecom Vendors</a>
            <a href="view_expenses.php"><i class="fas fa-calendar"></i> View Expenses</a>
        </div>
    </div>

    <div class="nav-item download-btn" onclick="openDownloadPopup()">
        <i class="fas fa-download"></i> Download Excel
    </div>
    <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
 
</div>

<!-- Filter Form -->
<form method="POST">
    <select name="month" required>
        <option value="">Select Month</option>
        <?php
        $months = [
            '01' => 'January', '02' => 'February', '03' => 'March',
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        foreach ($months as $num => $name) {
            $selected = ($selected_month == $num) ? 'selected' : '';
            echo "<option value='$num' $selected>$name</option>";
        }
        ?>
    </select>

    <select name="year" required>
        <option value="">Select Year</option>
        <?php
        $currentYear = date("Y");
        for ($y = $currentYear; $y >= 2024; $y--) {
            $selected = ($selected_year == $y) ? 'selected' : '';
            echo "<option value='$y' $selected>$y</option>";
        }
        ?>
    </select>

    <select name="category" required>
        <option value="">Select Category</option>
        <option value="billing" <?= ($selected_category == 'billing') ? 'selected' : '' ?>>Billing</option>
        <option value="infra" <?= ($selected_category == 'infra') ? 'selected' : '' ?>>Infra</option>
        <option value="it_asset" <?= ($selected_category == 'it_asset') ? 'selected' : '' ?>>IT Asset and Vendors</option>
        <option value="telecom" <?= ($selected_category == 'telecom') ? 'selected' : '' ?>>Telecom</option>
    </select>

    <button type="submit">Filter</button>
</form>
<!-- Chart and Table Container -->
<div class="chart-container" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <!-- Chart Section (Left Side) -->
    <div class="chart-section" style="flex: 1; max-width: 50%; padding-right: 20px;">
        <h2><?= ucfirst($selected_category) ?> Expense Summary - <?= $months[$selected_month] ?> <?= $selected_year ?></h2>
        <!-- Chart -->
        <canvas id="expenseChart" width="200" height="100"></canvas> <!-- Smaller chart size -->
    </div>

    <!-- Table Section (Right Side) -->
    <div class="table-section" style="flex: 1; max-width: 50%; padding-left: 20px;">
    <!-- Totals Table -->
    <table>
    <thead>
        <tr>
            <th>Expense Category</th>
            <th>Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($selected_category == 'it_asset') {
            // Fetch vendor-wise amounts for IT vendors
            $it_vendors = getITVendors($conn, $vertical_id, $selected_month, $selected_year);
            foreach ($it_vendors as $vendor_name => $total_amount) {
                echo "<tr><td>{$vendor_name}</td><td>&#8377;" . number_format($total_amount, 2) . "</td></tr>";
            }
        } else {
            // Loop through category fields like billing, infra, etc.
            foreach ($category_fields[$selected_category]['fields'] as $field_key => $label) {
                $value = $fieldTotals[$field_key] ?? 0;
                $formatted = ($field_key === 'profit_percent') ? number_format($value, 2) . '%' : '₹' . number_format($value, 2);
                echo "<tr><td>{$label}</td><td>{$formatted}</td></tr>";
            }
        }
        ?>
        <?php if (in_array($selected_category, ['it_asset', 'telecom'])): ?>
        <tr>
        <th colspan="1">Total</th>
        <td><strong><?= number_format($category_total, 2) ?></strong></td>
        </tr>
        <?php endif; ?>

    </tbody>
</table>

</div>
</div>
<!-- Download Filter Popup -->
<div id="downloadPopup" style="display:none; position:fixed; top:20%; left:35%; background:white; padding:20px 30px; border:2px solid #003366; border-radius:10px; z-index:999; box-shadow: 0 4px 10px rgba(0,0,0,0.2); min-width: 350px;">
  <h3 style="margin-top: 0; margin-bottom: 15px; color: #003366;">Select Vertical & Date Range</h3>
  <form action="download_excel.php" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
    
    <div style="display: flex; flex-direction: column; align-items: flex-start;">
      <label for="vertical_id"><strong>Vertical:</strong></label>
      <select name="vertical_id" id="vertical_id" required style="width: 54%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
        <option value="">-- Select Vertical --</option>
        <?php 
        $verticals = $conn->query("SELECT vertical_id, vertical_name FROM verticals");
        while ($v = $verticals->fetch_assoc()): ?>
          <option value="<?= $v['vertical_id'] ?>"><?= htmlspecialchars($v['vertical_name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div style="display: flex; flex-direction: column; align-items: flex-start;">
      <label for="start_date"><strong>Start Date:</strong></label>
      <input type="date" name="start_date" id="start_date" required style="width: 50%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
    </div>

    <div style="display: flex; flex-direction: column; align-items: flex-start;">
      <label for="end_date"><strong>End Date:</strong></label>
      <input type="date" name="end_date" id="end_date" required style="width: 50%; padding: 8px; border: 1px solid #ccc; border-radius: 5px;">
    </div>

    <div style="margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
      <button type="submit" style="background-color:#003366; color:white; padding:8px 15px; border:none; border-radius:5px;">Download Excel</button>
      <button type="button" onclick="closeDownloadPopup()" style="padding: 8px 15px; background: #ccc; border: none; border-radius: 5px;">Cancel</button>
    </div>
  </form>
</div>

<script>
    const ctx = document.getElementById('expenseChart').getContext('2d');
    
    // Prepare chart labels (Vendor names) and data (Amounts)
    const chartLabels = <?= json_encode(array_keys($fieldTotals)) ?>; // This will be the vendor names
    const chartData = <?= json_encode(array_values($fieldTotals)) ?>;  // This will be the corresponding expense totals
    
    // Creating the chart
    const expenseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,  // Vendor names
            datasets: [{
                label: 'Amount in ₹',
                data: chartData, // Corresponding totals for each vendor
                backgroundColor: 'rgba(0, 102, 204, 0.7)',
                borderColor: 'rgba(0, 102, 204, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ₹ ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹ ' + value.toLocaleString();  // Display the value with ₹ symbol
                        }
                    }
                }
            }
        }
    });
</script>


<!---download excelc java script-->
<script>
  function openDownloadPopup() {
    document.getElementById('downloadPopup').style.display = 'block';
  }

  function closeDownloadPopup() {
    document.getElementById('downloadPopup').style.display = 'none';
  }
</script>

</body>
</html>
