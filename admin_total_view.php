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
$selected_category = $_POST['category'] ?? 'billing';
$selected_vertical = $_POST['vertical'] ?? '';

$condition = "MONTH(created_at) = '$selected_month' AND YEAR(created_at) = '$selected_year' AND vertical_id = '$selected_vertical'";

function getFieldTotals($conn, $table, $fields, $condition) {
    $fieldTotals = [];
    foreach ($fields as $field) {
        $sql = "SELECT SUM($field) AS total FROM $table WHERE $condition";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $fieldTotals[$field] = $row['total'] ?? 0;
    }
    return $fieldTotals;
}

function getITVendors($conn, $month, $year, $vertical_id) {
    $vendors = [];
    $sql = "SELECT v.vendor_name, SUM(e.amount) as total_amount 
            FROM it_expenses e
            JOIN it_vendors v ON e.vendor_id = v.vendor_id
            WHERE MONTH(e.created_at) = ? AND YEAR(e.created_at) = ? AND e.vertical_id = ?
            GROUP BY v.vendor_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $month, $year, $vertical_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $vendors[$row['vendor_name']] = $row['total_amount'];
    }

    return $vendors;
}

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
        'fields' => getITVendors($conn, $selected_month, $selected_year, $selected_vertical)
    ],
    'telecom' => [
        'table' => 'telecom_vendors',
        'fields' => []
    ]
];

$fieldTotals = [];

if ($selected_category === 'telecom') {
    $stmt = $conn->prepare("SELECT v.vendor_name, SUM(e.amount) AS amount 
                            FROM telecom_expense e 
                            JOIN telecom_vendors v ON e.vendor_id = v.vendor_id 
                            WHERE MONTH(e.created_at) = ? AND YEAR(e.created_at) = ? AND e.vertical_id = ?
                            GROUP BY v.vendor_name");
    $stmt->bind_param("iii", $selected_month, $selected_year, $selected_vertical);
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
                                WHERE v.vendor_name = ? 
                                AND MONTH(e.created_at) = ? 
                                AND YEAR(e.created_at) = ? 
                                AND e.vertical_id = ?");
        $stmt->bind_param("siii", $vendor_name, $selected_month, $selected_year, $selected_vertical);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $fieldTotals[$vendor_name] = $row['total'] ?? 0;
    }
} else {
    $table_to_fetch = $category_fields[$selected_category]['table'];
    $fields_to_fetch = array_keys($category_fields[$selected_category]['fields']);
    $fieldTotals = getFieldTotals($conn, $table_to_fetch, $fields_to_fetch, $condition);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6fa; }
        .header { background: #003366; color: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header-title { font-size: 24px; font-weight: bold; }
        .nav-menu { display: flex; gap: 25px; }
        .nav-item { color: white; text-decoration: none; font-size: 16px; }
        .chart-container { display: flex; max-width: 1200px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .chart-section, .table-section { flex: 1; }
        .chart-section { padding-right: 20px; }
        .table-section { padding-left: 20px; }
        form { text-align: center; margin-top: 20px; }
        select, button { padding: 10px; margin: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #003366; color: white; }
        .chart-section {
        flex: 1;
        padding-right: 20px;
        position: relative;
        /* force a sensible height so Chart.js can scale nicely */
        height: 300px;
        background: #fafafa;
        border-radius: 8px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
        .chart-section canvas {
        /* fill its container */
        width: 100% !important;
        height: 100% !important;
        }
        .chart-section {
  /* was 300px; make it bigger: */
  height: 400px;
  /* …the rest stays the same… */
}
    /* Navigation Bar Hover Effect */
    .nav-link {
        color: white;
        text-decoration: none;
        padding: 10px;
        display: inline-block;
    }

    .nav-link:hover {
        background-color: #005f99; /* Lighten the background on hover */
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }

    /* Table Hover Effect */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table th, table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    table tr:nth-child(even) {
        background-color: #f2f2f2; /* Light gray for even rows */
    }

    table tr:hover {
        background-color: #d1e7ff; /* Light blue on hover */
        cursor: pointer;
    }

    /* Add alternate row color for better readability */
    table th {
        background-color: #003366;
        color: white;
    }

    </style>
</head>
<body>
<!-- Add Font Awesome CDN in <head> if not already included -->
<!-- Navigation Bar -->
<div style="background-color: #003366; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between;">
<div style="font-size: 20px; font-weight: bold; color: white;"> 
    kone<span style="color: orange;">X</span>ions
</div>

  <div style="display: flex; gap: 20px; align-items: center;">
    <a href="admin_dashboard.php" class="nav-link">
      <i class="fas fa-home"></i> Home
    </a>
    <a href="admin_view.php" class="nav-link">
      <i class="fas fa-eye"></i> Process Expense View
    </a>
    <a href="admin_total_view.php" class="nav-link">
      <i class="fas fa-table"></i> Total Expense View
    </a>
    <a href="logout.php" class="nav-link">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<!-- Filter Form -->
<form method="POST">
    <select name="vertical" required>
        <option value="">Select Vertical</option>
        <?php
        $vertical_sql = "SELECT vertical_id, vertical_name FROM verticals";
        $vertical_result = $conn->query($vertical_sql);
        while ($row = $vertical_result->fetch_assoc()) {
            $vid = $row['vertical_id'];
            $vname = $row['vertical_name'];
            $isSelected = ($selected_vertical == $vid) ? 'selected' : '';
            echo "<option value='$vid' $isSelected>$vname</option>";
        }
        ?>
    </select>

    <select name="month" required>
        <option value="">Select Month</option>
        <?php
        for ($i = 1; $i <= 12; $i++) {
            $value = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = date("F", mktime(0, 0, 0, $i, 10));
            $isSelected = ($selected_month == $value) ? 'selected' : '';
            echo "<option value='$value' $isSelected>$name</option>";
        }
        ?>
    </select>

    <select name="year" required>
        <option value="">Select Year</option>
        <?php
        $current_year = date('Y');
        for ($y = $current_year; $y >= $current_year - 5; $y--) {
            $isSelected = ($selected_year == $y) ? 'selected' : '';
            echo "<option value='$y' $isSelected>$y</option>";
        }
        ?>
    </select>

    <select name="category" required>
        <option value="">Select Category</option>
        <option value="billing" <?= $selected_category == 'billing' ? 'selected' : '' ?>>Billing</option>
        <option value="infra" <?= $selected_category == 'infra' ? 'selected' : '' ?>>Infra</option>
        <option value="it_asset" <?= $selected_category == 'it_asset' ? 'selected' : '' ?>>IT Asset and Vendors</option>
        <option value="telecom" <?= $selected_category == 'telecom' ? 'selected' : '' ?>>Telecom</option>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if (!empty($fieldTotals)) : ?>
    <div class="chart-container">
        <div class="chart-section">
            <h3><?= ucfirst($selected_category) ?> Trend for <?= date('F', mktime(0,0,0,$selected_month,10)) ?> <?= $selected_year ?></h3>
            <canvas id="expenseChart"></canvas>
        </div>
        <div class="table-section">
            <h3><?= ucfirst($selected_category) ?> Totals</h3>
            <table>
                <thead><tr><th>Field</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($fieldTotals as $key => $val): ?>
                    <tr>
                        <td><?= htmlspecialchars($category_fields[$selected_category]['fields'][$key] ?? $key) ?></td>
                        <td>
                        <?php 
                            if ($key === 'profit_percent') {
                                // show percent
                                echo number_format($val, 2) . '%';
                            } else {
                                // show rupees
                                echo '₹' . number_format($val, 2);
                            }
                        ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (in_array($selected_category,['it_asset','telecom'])): ?>
                    <tr><th>Total</th><th>₹<?= number_format($category_total,2) ?></th></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
  const ctx = document.getElementById('expenseChart').getContext('2d');
  const labels = <?= json_encode(array_keys($fieldTotals)) ?>;
  const data   = <?= json_encode(array_values($fieldTotals)) ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Amount (₹)',
        data: data,
        /* styling the line & fill */
        borderColor: '#007bff',
        backgroundColor: 'rgba(0,123,255,0.1)',
        borderWidth: 3,
        tension: 0.2,
        fill: true,
        /* styling the points */
        pointRadius: 5,
        pointBackgroundColor: '#007bff',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7,
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#007bff',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: { top: 10, bottom: 10, left: 10, right: 10 }
      },
      plugins: {
        title: {
          display: true,
          text: '<?= ucfirst($selected_category) ?> Trend',
          font: { size: 18, weight: 'bold' },
          padding: { bottom: 10 }
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          padding: 8,
          titleFont: { size: 14 },
          bodyFont: { size: 13 },
          callbacks: {
            label: ctx => `₹ ${ctx.parsed.y.toLocaleString()}`
          }
        },
        legend: { display: false }
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true,
            text: 'Fields',
            font: { size: 14 }
          },
          grid: { display: false }
        },
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Amount (₹)',
            font: { size: 14 }
          },
          grid: {
            color: 'rgba(0,0,0,0.05)',
            drawBorder: false
          },
          ticks: {
            callback: v => '₹ ' + v.toLocaleString()
          }
        }
      },
      elements: {
        line: { borderJoinStyle: 'round' }
      }
    }
  });
</script>

<?php endif; ?>
</body>
</html>
