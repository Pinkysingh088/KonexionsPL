<?php 
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch verticals
$verticals = $conn->query("SELECT vertical_id, vertical_name FROM verticals");

// Handle form data
$selected_vertical = $_GET['vertical'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$selected_category = $_GET['category'] ?? '';
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Functions
function getTotalsByProcess($conn, $table, $amount_col, $start, $end, $vertical_id, $is_distinct_total = false) {
    $sql = $is_distinct_total ?
        "SELECT p.process_name, MAX(e.$amount_col) AS total FROM $table e JOIN processes p ON e.process_id = p.process_id WHERE e.start_date BETWEEN ? AND ? AND e.vertical_id = ? GROUP BY e.process_id" :
        "SELECT p.process_name, SUM(e.$amount_col) AS total FROM $table e JOIN processes p ON e.process_id = p.process_id WHERE e.start_date BETWEEN ? AND ? AND e.vertical_id = ? GROUP BY e.process_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $start, $end, $vertical_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getBillingDetails($conn, $start, $end, $vertical_id) {
    $sql = "SELECT p.process_name, SUM(b.total_billing) AS total, MAX(b.net_profit) AS net_profit, MAX(b.profit_percent) AS profit_percent FROM billing b JOIN processes p ON b.process_id = p.process_id WHERE b.start_date BETWEEN ? AND ? AND b.vertical_id = ? GROUP BY b.process_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $start, $end, $vertical_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getCategoryDetails($conn, $category, $month, $year, $vertical_id) {
  // Map category to table name
  $table_map = [
      'billing' => 'billing',
      'infra' => 'infra',
      'it' => 'it_expenses',
      'telecom' => 'telecom_expense'
  ];

  if (!isset($table_map[$category])) return [];

  $table = $table_map[$category];
  $month_year = "$year-$month";

  // Get field names dynamically from the selected table
  $sql_fields = "DESCRIBE $table";
  $fields_result = $conn->query($sql_fields);
  
  // Prepare the field names array
  $fields = [];
  while ($row = $fields_result->fetch_assoc()) {
      // Exclude the columns that aren't relevant to the category data (like 'id', 'start_date', etc.)
      if (!in_array($row['Field'], ['id', 'start_date', 'process_id', 'vertical_id'])) {
          $fields[] = $row['Field'];
      }
  }

  if (empty($fields)) return [];

  // Build the query dynamically based on the fetched fields
  $fields_sql = implode(", ", $fields);
  $sql = "SELECT process_id, $fields_sql FROM $table WHERE DATE_FORMAT(start_date, '%Y-%m') = ? AND vertical_id = ?";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("si", $month_year, $vertical_id);
  $stmt->execute();
  return $stmt->get_result();
}

// Fetch Totals
$totals = ['billing' => [], 'infra' => [], 'it_expenses' => [], 'telecom_expense' => []];
if ($selected_vertical) {
    $totals['billing'] = getBillingDetails($conn, $start_date, $end_date, $selected_vertical);
    $totals['infra'] = getTotalsByProcess($conn, 'infra', 'infra_total', $start_date, $end_date, $selected_vertical);
    $totals['it_expenses'] = getTotalsByProcess($conn, 'it_expenses', 'it_total', $start_date, $end_date, $selected_vertical, true);
    $totals['telecom_expense'] = getTotalsByProcess($conn, 'telecom_expense', 'telecom_total', $start_date, $end_date, $selected_vertical, true);
}

$category_details = [];
if ($selected_category && $selected_vertical) {
    $category_details = getCategoryDetails($conn, $selected_category, $selected_month, $selected_year, $selected_vertical);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - koneXion</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f4f9ff; margin: 0; }
    .header, .container { padding: 20px; }
    .filters, .category-filters { display: flex; gap: 10px; margin-bottom: 20px; }
     .table-box table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
  }

  .table-box th, .table-box td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
  }

  .table-box thead th {
    background-color: #003366;
    color: white;
  }

  /* Alternating row colors */
  .table-box tbody tr:nth-child(even) {
    background-color: #ffe6cc; /* Soft orange for even rows */
  }

  .table-box tbody tr:nth-child(odd) {
    background-color: #e6f7ff; /* Light blue for odd rows */
  }

  /* Hover effect */
  .table-box tbody tr:hover {
    background-color: #ffccff; /* Light pink on hover */
    transform: scale(1.01);
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  }

  .table-box tbody tr {
    transition: all 0.2s ease;
  }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    th { background: #003366; color: white; }
    .section {
  margin-bottom: 40px;
}
.flex-box {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.chart-box, .table-box {
  flex: 1 1 45%;
  min-width: 300px;
}
.centered-filters {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}
select, input[type="date"], button {
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
}
 .top-bar {
    background-color: #003366;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .logo-text {
    font-size: 20px;
    font-weight: bold;
    color: white;
  }

  .logo-text span {
    color: orange;
  }

  .nav-links {
    display: flex;
    gap: 20px;
    align-items: center;
  }

  .nav-links a {
    color: white;
    text-decoration: none;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .nav-links a:hover {
    transform: scale(1.1);
    color: orange;
  }


  </style>
</head>
<body>
<!-- Navigation Bar -->
<!-- Add Font Awesome CDN in <head> if not already included -->

<!-- Navigation Bar -->
<div class="top-bar">
  <div class="logo-text">
    kone<span>X</span>ions
  </div>
  <div class="nav-links">
    <a href="admin_dashboard.php">
      <i class="fas fa-home"></i> Home
    </a>
    <a href="admin_view.php">
      <i class="fas fa-eye"></i> Process Expense View
    </a>
    <a href="admin_total_view.php">
      <i class="fas fa-table"></i> Total Expense View
    </a>
    <button onclick="openDownloadPopup()" style="padding: 6px 12px; background-color: orange; border: none; color: white; border-radius: 4px; cursor: pointer;">
    <i class="fas fa-download"></i> Download Excel
    </button>
    <a href="logout.php">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<div class="container">
  <!-- Global Filter -->
  <form method="GET" class="centered-filters">
  <select name="vertical" required>
    <option value="">Select Vertical</option>
    <?php while ($v = $verticals->fetch_assoc()): ?>
      <option value="<?= $v['vertical_id'] ?>" <?= $selected_vertical == $v['vertical_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($v['vertical_name']) ?>
      </option>
    <?php endwhile; ?>
  </select>
  <input type="date" name="start_date" value="<?= $start_date ?>" required>
  <input type="date" name="end_date" value="<?= $end_date ?>" required>
  <button type="submit">View</button>
</form>
  <!-- Expense Chart & Table -->
  <div class="section">
    <h3>Total Expenses per Process</h3>
    <div class="flex-box">
      <div class="chart-box">
        <canvas id="expenseChart"></canvas>
      </div>
      <div class="table-box">
        <table>
          <thead>
            <tr>
              <th>Process</th>
              <th>Billing</th>
              <th>Infra</th>
              <th>IT</th>
              <th>Telecom</th>
              <th>Net Profit</th>
              <th>Profit %</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $processes = [];

            foreach ($totals as $category => $result) {
                if ($result instanceof mysqli_result) {
                    while ($row = $result->fetch_assoc()) {
                        $p = $row['process_name'];
                        $processes[$p] = $processes[$p] ?? ['billing' => 0, 'infra' => 0, 'it_expenses' => 0, 'telecom_expense' => 0, 'net_profit' => 0, 'profit_percent' => 0];
                        $processes[$p][$category] = $row['total'] ?? 0;
                        if ($category === 'billing') {
                            $processes[$p]['net_profit'] = $row['net_profit'] ?? 0;
                            $processes[$p]['profit_percent'] = $row['profit_percent'] ?? 0;
                        }
                    }
                }
            }
            
          $labels = [];
          $data = [];
          foreach ($processes as $name => $vals):
            $total = $vals['billing']; // ✅ Only billing total now
            $labels[] = $name;
            $data[] = $total;
          ?>
          <tr>
            <td><?= htmlspecialchars($name) ?></td>
            <td>₹<?= number_format($vals['billing'] ?? 0, 2) ?></td>
            <td>₹<?= number_format($vals['infra'] ?? 0, 2) ?></td>
            <td>₹<?= number_format($vals['it_expenses'] ?? 0, 2) ?></td>
            <td>₹<?= number_format($vals['telecom_expense'] ?? 0, 2) ?></td>
            <td>₹<?= number_format($vals['net_profit'] ?? 0, 2) ?></td>
            <td><?= number_format($vals['profit_percent'] ?? 0, 2) ?>%</td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

<script>
  const ctx = document.getElementById('expenseChart').getContext('2d');
  const expenseChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Billing Amount',
        data: <?= json_encode($data) ?>,
        backgroundColor: [
          '#4e79a7', '#f28e2b', '#e15759', '#76b7b2',
          '#59a14f', '#edc949', '#af7aa1', '#ff9da7',
          '#9c755f', '#bab0ab'
        ],
        borderWidth: 1
      }]
    },
    options: {
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#003366' }
        },
        x: {
          ticks: { color: '#003366' }
        }
      }
    }
  });
</script>


<script>
<?php if (!empty($categoryLabels)): ?>
  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  new Chart(categoryCtx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($categoryLabels) ?>,
      datasets: [{
        label: '<?= ucfirst($selected_category) ?> Amounts',
        data: <?= json_encode($categoryValues) ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
<?php endif; ?>
</script>


<!-- Hover Script -->
<script>
  const dropdownTrigger = document.querySelector("a[href='#']");
  const dropdownMenu = document.getElementById("dropdown-menu");

  dropdownTrigger.addEventListener("mouseover", () => {
    dropdownMenu.style.display = "flex";
  });

  dropdownTrigger.addEventListener("mouseout", () => {
    setTimeout(() => {
      if (!dropdownMenu.matches(':hover')) {
        dropdownMenu.style.display = "none";
      }
    }, 200);
  });

  dropdownMenu.addEventListener("mouseleave", () => {
    dropdownMenu.style.display = "none";
  });
</script>


<!-- Download Filter Popup -->
<div id="downloadPopup" style="display:none; position:fixed; top:20%; left:35%; background:white; padding:20px; border:2px solid #003366; border-radius:10px; z-index:999;">
  <h3>Select Vertical & Date Range</h3>
  <form action="download_excel.php" method="POST">
    <label>Vertical:</label><br>
    <select name="vertical_id" required>
      <option value="">-- Select Vertical --</option>
      <?php 
      // Re-fetch verticals (if outside PHP loop)
      $verticals = $conn->query("SELECT vertical_id, vertical_name FROM verticals");
      while ($v = $verticals->fetch_assoc()): ?>
        <option value="<?= $v['vertical_id'] ?>"><?= htmlspecialchars($v['vertical_name']) ?></option>
      <?php endwhile; ?>
    </select><br><br>

    <label>Start Date:</label><br>
    <input type="date" name="start_date" required><br><br>

    <label>End Date:</label><br>
    <input type="date" name="end_date" required><br><br>

    <button type="submit" style="background-color:#003366;color:white;padding:8px 15px;border:none;border-radius:5px;">Download Excel</button>
    <button type="button" onclick="closeDownloadPopup()" style="margin-left:10px;">Cancel</button>
  </form>
</div>


<!-- Background overlay -->
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
