<?php
session_start();
$user_vertical_id = $_SESSION['vertical_id']; // This should be set at login

$conn = new mysqli("localhost", "root", "", "konexions_expense");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch assigned vertical name
$vertical_name = '';
$vertical_query = $conn->query("SELECT vertical_name FROM verticals WHERE vertical_id = $user_vertical_id");
if ($vertical_query->num_rows > 0) {
  $vertical_row = $vertical_query->fetch_assoc();
  $vertical_name = $vertical_row['vertical_name'];
}


// Add Vendor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vendor'])) {
  $vendor_name = $conn->real_escape_string($_POST['vendor_name']);
  $amount = floatval($_POST['amount']);
  $process_id = intval($_POST['process_id']);
  $vertical_id = $user_vertical_id; // Get from logged-in user

  // Get current month start and end dates
  $start_date = date('Y-m-01');
  $end_date = date('Y-m-t');

  // Step 1: Insert vendor
  $insertVendor = "INSERT INTO telecom_vendors (vertical_id, vendor_name) VALUES ($vertical_id, '$vendor_name')";
  if ($conn->query($insertVendor) === TRUE) {
      $vendor_id = $conn->insert_id;

      // Step 2: Insert expense
      $insertExpense = "INSERT INTO telecom_expense  (vendor_id, process_id, vertical_id, amount, start_date, end_date) 
                        VALUES ($vendor_id, $process_id, $vertical_id, $amount, '$start_date', '$end_date')";
      if ($conn->query($insertExpense) === TRUE) {

          // Step 3: Calculate total for this process and current month
          $month = date('Y-m-01');
          $totalQuery = "
              SELECT SUM(amount) AS total
              FROM telecom_expense
              WHERE process_id = $process_id
                AND MONTH(start_date) = MONTH('$month')
                AND YEAR(start_date) = YEAR('$month')
          ";
          $result = $conn->query($totalQuery);
          $row = $result->fetch_assoc();
          $it_total = $row['total'] ?? 0;

          // Step 4: Update it_total for all records matching process and month
          $updateTotal = "
              UPDATE telecom_expense
              SET telecom_total = $it_total
              WHERE process_id = $process_id
                AND MONTH(start_date) = MONTH('$month')
                AND YEAR(start_date) = YEAR('$month')
          ";
          $conn->query($updateTotal);

          echo "<script>alert('Vendor added, expense inserted, and total updated successfully.');</script>";
      } else {
          echo "<script>alert('Failed to insert expense: " . $conn->error . "');</script>";
      }
  } else {
      echo "<script>alert('Failed to insert vendor: " . $conn->error . "');</script>";
  }
}


// Delete Vendor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vendor'])) {
  $vendor_id = intval($_POST['vendor_id']);

  // Step 1: Get the process_id and month of the vendor's expense(s)
  $processInfoQuery = "SELECT process_id, start_date FROM telecom_expense WHERE vendor_id = $vendor_id LIMIT 1";
  $processInfoResult = $conn->query($processInfoQuery);

  if ($processInfoResult->num_rows > 0) {
    $info = $processInfoResult->fetch_assoc();
    $process_id = $info['process_id'];
    $month = date('Y-m-01', strtotime($info['start_date']));  // First day of that month

    // Step 2: Delete related it_expenses
    $conn->query("DELETE FROM telecom_expense WHERE vendor_id = $vendor_id");

    // Step 3: Delete the vendor itself
    $sql = "DELETE FROM telecom_vendors WHERE vendor_id = $vendor_id AND vertical_id = $user_vertical_id";
    if ($conn->query($sql) === TRUE) {

      // Step 4: Recalculate total after deletion
      $totalQuery = "
        SELECT SUM(amount) AS total
        FROM telecom_expense
        WHERE process_id = $process_id
          AND MONTH(start_date) = MONTH('$month')
          AND YEAR(start_date) = YEAR('$month')
      ";
      $result = $conn->query($totalQuery);
      $row = $result->fetch_assoc();
      $it_total = $row['total'] ?? 0;

      // Step 5: Update all matching records with new total
      $updateTotal = "
        UPDATE telecom_expense
        SET telecom_total = $it_total
        WHERE process_id = $process_id
          AND MONTH(start_date) = MONTH('$month')
          AND YEAR(start_date) = YEAR('$month')
      ";
      $conn->query($updateTotal);

      echo "<script>alert('Vendor deleted and total updated successfully.');</script>";
    } else {
      echo "<script>alert('Error deleting vendor: " . $conn->error . "');</script>";
    }
  } else {
    echo "<script>alert('No associated expenses found for this vendor.');</script>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
  <title>Manage IT Vendors</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f8fb;
      color: #003366;
      padding: 20px;
    }

    .form-container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: auto;
    }

    h2 {
      text-align: center;
      color: #003366;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      font-weight: 600;
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-top: 6px;
    }

    select[disabled] {
      background-color: #eee;
      color: #333;
    }

    button {
      background-color: #003366;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background-color: #005599;
    }

    .delete-button {
      background-color: #c0392b;
    }

    .delete-button:hover {
      background-color: #e74c3c;
    }

    hr {
      margin: 30px 0;
    }
    .back-button {
  background-color: #6c757d;
}

.back-button:hover {
  background-color: #5a6268;
}

  </style>
</head>
<body>

<div class="form-container">
  <h2>Manage Telecom Vendors</h2>

  <!-- Add Vendor Form -->
  <form method="POST">
    <div class="form-group">
      <label>Vertical</label>
      <select disabled>
        <option selected><?= htmlspecialchars($vertical_name) ?></option>
      </select>
    </div>

    <div class="form-group">
      <label>Vendor Name</label>
      <input type="text" name="vendor_name" required>
    </div>

    <div class="form-group">
      <label>Amount (₹)</label>
      <input type="number" step="0.01" name="amount" required>
    </div>

    <div class="form-group">
      <label>Process</label>
      <select name="process_id" required>
        <option value="">-- Select Process --</option>
        <?php
        $result = $conn->query("SELECT process_id, process_name FROM processes WHERE vertical_id = $user_vertical_id");
        while ($row = $result->fetch_assoc()) {
          echo "<option value='{$row['process_id']}'>{$row['process_name']}</option>";
        }
        ?>
      </select>
    </div>

    <button type="submit" name="add_vendor">Add Vendor</button>
  </form>

  <hr>

  <!-- Delete Vendor Form -->
  <form method="POST">
    <div class="form-group">
      <label>Select Vendor to Delete</label>
      <select name="vendor_id" required>
        <option value="">-- Select Vendor --</option>
        <?php
        $vendors = $conn->query("SELECT vendor_id, vendor_name FROM telecom_vendors WHERE vertical_id = $user_vertical_id");
        while ($v = $vendors->fetch_assoc()) {
          echo "<option value='{$v['vendor_id']}'>{$v['vendor_name']}</option>";
        }
        ?>
      </select>
    </div>

    <button type="submit" name="delete_vendor" class="delete-button">Delete Vendor</button>
  </form>
</div>
    <div style="text-align: center; margin-top: 20px;">
      <a href="dashboard.php">
        <button type="button">Back</button>
      </a>
    </div>
</body>
</html>
