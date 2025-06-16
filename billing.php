<?php
session_start();
include 'db_connection.php';

// Get vertical ID from session
$vertical_id = $_SESSION['vertical_id'] ?? null;
if (!$vertical_id) {
    header("Location: login.php");
    exit; 
}

// Fetch vertical name
$vertical_name = '';
$vertical_result = $conn->query("SELECT vertical_name FROM verticals WHERE vertical_id = $vertical_id");
if ($vertical_result && $row = $vertical_result->fetch_assoc()) {
    $vertical_name = $row['vertical_name'];
}

// Fetch processes assigned to this vertical  
$selected_process_id = $_POST['process_id'] ?? ''; // get selected value from POST

$process_options = '';
$process_result = $conn->query("SELECT process_id, process_name FROM processes WHERE vertical_id = $vertical_id");
if ($process_result) {
    while ($row = $process_result->fetch_assoc()) {
        $selected = ($row['process_id'] == $selected_process_id) ? 'selected' : '';
        $process_options .= "<option value='{$row['process_id']}' $selected>{$row['process_name']}</option>";
    }
}

// Initialize variables
$billingCommitment = $grossBilling = $managementCost = $directorRemuneration = $outstanding = 0;
$tds = $netBilling = $interestCapital = $totalExpenses = $netProfit = $profitPercent = 0;
$infra_total = $it_total = $telecom_total = 0;
$process_id = $start_date = $end_date = '';

$monthDate = $start_date ?: date('Y-m-d'); // fallback to current month if not posted

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $action = $_POST['action'] ?? '';
  $billingCommitment = floatval($_POST['billingCommitment'] ?? 0);
  $grossBilling = floatval($_POST['grossBilling'] ?? 0);
  $managementCost = floatval($_POST['managementCost'] ?? 0);
  $directorRemuneration = floatval($_POST['directorRemuneration'] ?? 0);
  $outstanding = floatval($_POST['outstanding'] ?? 0);
  $infra_total = floatval($_POST['infra_total'] ?? 0);  // ⬅ Manual input
  $it_total = floatval($_POST['it_total'] ?? 0);        // ⬅ Manual input
  $telecom_total = floatval($_POST['telecom_total'] ?? 0); // ⬅ Manual input
  $process_id = intval($_POST['process_id'] ?? 0);
  $start_date = $_POST['start_date'] ?? '';
  $end_date = $_POST['end_date'] ?? '';
  $monthDate = $start_date ?: date('Y-m-d');

  // Calculations
  $tds = $grossBilling * 0.10;
  $netBilling = $grossBilling - $tds;
  $interestCapital = $outstanding * 0.02;
  $totalExpenses = $managementCost + $interestCapital + $infra_total + $it_total + $telecom_total + $directorRemuneration;
  $netProfit = $netBilling - $totalExpenses;
  $profitPercent = $netBilling > 0 ? ($netProfit / $netBilling) * 100 : 0;

  if ($action === 'save') {
      $stmt = $conn->prepare("INSERT INTO billing (
          vertical_id, process_id, start_date, end_date, billing_commitment,
          gross_billing, less_tds, net_billing, management_cost, director_remuneration,
          outstanding, interest_capital, total_billing, net_profit, profit_percent
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $stmt->bind_param("iissddddddddddd", $vertical_id, $process_id, $start_date, $end_date,
          $billingCommitment, $grossBilling, $tds, $netBilling, $managementCost,
          $directorRemuneration, $outstanding, $interestCapital, $totalExpenses,
          $netProfit, $profitPercent);

      $stmt->execute();
      $stmt->close();

      echo "<script>alert('Billing data saved successfully.');</script>";
  }
}

?>
<!-- Updated HTML starts here -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">s
  <title>Billing Form - koneXion</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #e6f0ff;
    }
    .form-container {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.15);
      border-top: 8px solid navy;
    }
    h2 {
      text-align: center;
      color: navy;
      margin-bottom: 25px;
    }
    form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 15px 20px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    label {
      font-weight: bold;
      margin-bottom: 5px;
      color: #333;
    }
    input, select {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    input[disabled] {
      background-color: #f1f1f1;
      font-weight: bold;
      color: #333;
    }
    .button-container {
      grid-column: span 2;
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 20px;
    }
    button {
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      color: white;
    }
    .calculate-button { background-color: #007bff; }
    .save-button { background-color: #28a745; }
    .back-button { background-color: #6c757d; }
    .reset-button { background-color: #dc3545; }
  </style>
</head>
<body>

<div class="form-container">
  <form method="post" id="billingForm">
    <h2>koneXion - Billing Entry</h2>
    <input type="hidden" name="action" id="actionInput" value="" />

    <!-- Form Fields -->
    <div class="form-group">
      <label>Vertical</label>
      <input type="text" value="<?= htmlspecialchars($vertical_name) ?>" disabled />
    </div>

    <div class="form-group">
      <label>Select Process</label>
      <select name="process_id" required>
        <option value="">-- Select Process --</option>
        <?= $process_options ?>
      </select>
    </div>

    <div class="form-group">
      <label>Start Date</label>
      <input type="date" name="start_date" value="<?= $start_date ?>" required />
    </div>

    <div class="form-group">
      <label>End Date</label>
      <input type="date" name="end_date" value="<?= $end_date ?>" required />
    </div>

    <div class="form-group">
      <label>Billing Commitment</label>
      <input type="number" name="billingCommitment" value="<?= $billingCommitment ?>" />
    </div>

    <div class="form-group">
      <label>Gross Billing</label>
      <input type="number" name="grossBilling" value="<?= $grossBilling ?>" />
    </div>

    <div class="form-group">
      <label>Less TDS @ 10%</label>
      <input type="text" id="tds" value="<?= number_format($tds, 2) ?>" disabled />
    </div>

    <div class="form-group">
      <label>Net Billing Post TDS</label>
      <input type="text" id="netBilling" value="<?= number_format($netBilling, 2) ?>" disabled />
    </div>

    <div class="form-group">
      <label>Management Cost</label>
      <input type="number" name="managementCost" value="<?= $managementCost ?>" />
    </div>

    <div class="form-group">
      <label>Director Remuneration</label>
      <input type="number" name="directorRemuneration" value="<?= $directorRemuneration ?>" />
    </div>

    <div class="form-group">
      <label>Outstanding as on Date</label>
      <input type="number" name="outstanding" value="<?= $outstanding ?>" />
    </div>

    <div class="form-group">
      <label>Interest on Capital @ 2%</label>
      <input type="text" id="interestCapital" value="<?= number_format($interestCapital, 2) ?>" disabled />
    </div>

    <div class="form-group">
  <label>Infra Total</label>
  <input type="number" name="infra_total" value="<?= $infra_total ?>" step="0.01" required />
</div>

<div class="form-group">
  <label>IT Asset and Vendor Total</label>
  <input type="number" name="it_total" value="<?= $it_total ?>" step="0.01" required />
</div>

<div class="form-group">
  <label>Telecom Expense Total</label>
  <input type="number" name="telecom_total" value="<?= $telecom_total ?>" step="0.01" required />
</div>


    <div class="form-group">
      <label>Total Expenses</label>
      <input type="text" id="totalExpenses" value="<?= number_format($totalExpenses, 2) ?>" disabled />
    </div>

    <div class="form-group">
      <label>Net Profit</label>
      <input type="text" id="netProfit" value="<?= number_format($netProfit, 2) ?>" disabled />
    </div>

    <div class="form-group">
      <label>Profit %</label>
      <input type="text" id="profitPercent" value="<?= number_format($profitPercent, 2) ?> %" disabled />
    </div>

    <!-- Buttons -->
    <div class="button-container">
      <button type="submit" class="calculate-button" onclick="document.getElementById('actionInput').value='calculate'">Calculate</button>
      <button type="submit" class="save-button" onclick="document.getElementById('actionInput').value='save'">Save</button>
      <button type="button" class="reset-button" onclick="resetForm()">Reset</button>
      <button type="button" class="back-button" onclick="window.location.href='dashboard.php'">Back</button>
    </div>
  </form>
</div>
<script>
function resetForm() {
  // Clear form input fields
  document.querySelector("[name='process_id']").value = "";
  document.querySelector("[name='start_date']").value = "";
  document.querySelector("[name='end_date']").value = "";
  document.querySelector("[name='billingCommitment']").value = "";
  document.querySelector("[name='grossBilling']").value = "";
  document.querySelector("[name='managementCost']").value = "";
  document.querySelector("[name='directorRemuneration']").value = "";
  document.querySelector("[name='outstanding']").value = "";

  // Reset display-only (disabled) computed fields
  const resetDisplayFields = [
    "Less TDS @ 10%",
    "Net Billing Post TDS",
    "Interest on Capital @ 2%",
    "Total Expenses",
    "Net Profit",
    "Profit %"
  ];
  
  // Loop through labels and clear their corresponding input
  resetDisplayFields.forEach(labelText => {
    const label = Array.from(document.querySelectorAll("label"))
                       .find(el => el.textContent.includes(labelText));
    if (label) {
      const input = label.parentElement.querySelector("input");
      if (input) input.value = "";
    }
  });

  // Clear the hidden action input
  document.getElementById('actionInput').value = '';
}
</script>

</body>
</html>
