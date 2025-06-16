<?php
// manage_process.php

session_start();
$user_vertical_id = $_SESSION['vertical_id']; // Assume vertical_id is stored in session

$conn = new mysqli("localhost", "root", "", "konexions_expense");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Add Process
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_process'])) {
  $process_name = $conn->real_escape_string($_POST['process_name']);
  $vertical_id = $user_vertical_id;  // Automatically use the vertical_id from session

  $sql = "INSERT INTO processes (process_name, vertical_id) VALUES ('$process_name', $vertical_id)";
  if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Process added successfully');</script>";
  } else {
    echo "<script>alert('Error: " . $conn->error . "');</script>";
  }
}

// Delete Process
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_process'])) {
  $process_id = intval($_POST['process_id']);

  $sql = "DELETE FROM processes WHERE process_id = $process_id AND vertical_id = $user_vertical_id";
  if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Process deleted successfully');</script>";
  } else {
    echo "<script>alert('Error deleting process: " . $conn->error . "');</script>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
  <title>Manage Processes</title>
  <style>
      body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f7f9fc;
      color: #003366;
      margin: 0;
      padding: 20px;
    }

    .form-container {
      background-color: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: 0 auto;
    }

    h2 {
      text-align: center;
      color: #003366;
      margin-bottom: 25px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 600;
      color: #003366;
      margin-bottom: 8px;
    }

    input[type="text"],
    select {
      width: 100%;
      padding: 10px 14px;
      font-size: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
    }

    button {
      background-color: #003366;
      color: #fff;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #005799;
    }

    .delete-button {
      background-color: #c0392b;
    }

    .delete-button:hover {
      background-color: #e74c3c;
    }

    hr {
      margin: 30px 0;
      border: none;
      border-top: 1px solid #ddd;
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
    <h2>Manage Processes</h2>

    <!-- Add Process Form -->
    <form method="POST">
      <div class="form-group">
        <label>Process Name</label>
        <input type="text" name="process_name" required>
      </div>

      <div class="form-group">
        <label>Select Vertical</label>
        <select name="vertical_id" required disabled>
          <option value="<?php echo $user_vertical_id; ?>" selected>
            <?php
            // Get the vertical name based on the vertical_id from the session
            $result = $conn->query("SELECT vertical_name FROM verticals WHERE vertical_id = $user_vertical_id");
            if ($row = $result->fetch_assoc()) {
              echo $row['vertical_name'];
            }
            ?>
          </option>
        </select>
      </div>

      <button type="submit" name="add_process">Add Process</button>
    </form>

    <hr>

    <!-- Delete Process Form -->
    <form method="POST">
      <div class="form-group">
        <label>Select Process to Delete</label>
        <select name="process_id" required>
          <option value="">-- Select Process --</option>
          <?php
          $processes = $conn->query("SELECT * FROM processes WHERE vertical_id = $user_vertical_id");
          while ($p = $processes->fetch_assoc()) {
            echo "<option value='{$p['process_id']}'>{$p['process_name']}</option>";
          }
          ?>
        </select>
      </div>

      <button type="submit" name="delete_process" class="delete-button">Delete Process</button>
    </form>
  </div>
<!-- Back Button -->
<div style="text-align: center; margin-top: 20px;">
      <a href="dashboard.php">
        <button type="button">Back</button>
      </a>
    </div>
</body>
</html>
