<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['accountant_id'])) {
    header("Location: index.php");
    exit();
}

$vertical_id = $_SESSION['vertical_id'];
$message = '';

// Fetch vertical name for display
$vertical_name = '';
$stmt = $conn->prepare("SELECT vertical_name FROM verticals WHERE vertical_id = ?");
$stmt->bind_param("i", $vertical_id);
$stmt->execute();
$stmt->bind_result($vertical_name);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $process_id = $_POST['process_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $fields = ['rent', 'electricity', 'maintenance', 'agent_salary', 'support_staff_salary', 'esi_pf_contribution', 'daily_expenses', 'team_rr_incentive', 'central_team_cost', 'festival_expenses', 'petty_cash', 'input_amount', 'misc_expenses']; // removed 'infra_total'

    $values = [];
    foreach ($fields as $field) {
        $values[$field] = isset($_POST[$field]) ? floatval($_POST[$field]) : 0.00;
    }
    
    $infra_total = array_sum($values); // correctly sums only real expense fields
    

    $stmt = $conn->prepare("INSERT INTO infra (vertical_id, process_id, start_date, end_date, rent, electricity, maintenance, agent_salary, support_staff_salary, esi_pf_contribution, daily_expenses, team_rr_incentive, central_team_cost, festival_expenses, petty_cash, input_amount, misc_expenses, infra_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissdddddddddddddd", $vertical_id, $process_id, $start_date, $end_date, $values['rent'], $values['electricity'], $values['maintenance'], $values['agent_salary'], $values['support_staff_salary'], $values['esi_pf_contribution'], $values['daily_expenses'], $values['team_rr_incentive'], $values['central_team_cost'], $values['festival_expenses'], $values['petty_cash'], $values['input_amount'], $values['misc_expenses'], $infra_total);

    if ($stmt->execute()) {
        $message = "Infra expense saved successfully!";
    } else {
        $message = "Error saving data: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch processes for the vertical
$processes = [];
$stmt = $conn->prepare("SELECT process_id, process_name FROM processes WHERE vertical_id = ?");
$stmt->bind_param("i", $vertical_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $processes[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="icon" href="/KonexionsPL/photos/koneXion_logo.png" type="image/png">
    <title>Infra Expense Form</title>
    <style>
            body {
            font-family: 'Segoe UI', sans-serif;
            padding: 40px;
            background: #ecf1f8;
            margin: 0;
        }

        .form-container {
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 16px;
            max-width: 1000px;
            margin: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-top: 6px solid #1b2a49;
        }

        h2 {
            text-align: center;
            color: #1b2a49;
            margin-bottom: 30px;
            font-size: 26px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        input[type="number"], input[type="date"], select, input[disabled] {
            width: 80%;
            padding: 10px 12px;
            border: 1px solid #cfd8dc;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9fbfd;
        }

        input[disabled] {
            background-color: #f1f1f1;
            color: #555;
        }

        .actions {
            text-align: center;
            margin-top: 10px;
        }

        .actions input {
            padding: 12px 28px;
            margin: 10px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            transition: background-color 0.3s;
        }

        .submit-btn {
            background-color: #1b2a49;
            color: white;
        }

        .submit-btn:hover {
            background-color: #2c3e75;
        }

        .reset-btn {
            background-color: #c0392b;
            color: white;
        }

        .reset-btn:hover {
            background-color: #e74c3c;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            color: green;
            font-size: 16px;
        }
        .actions a:hover {
    background-color: #95a5a6;
}

    </style>
    <script>
        function calculateTotal() {
            let total = 0;
            const fields = ['rent', 'electricity', 'maintenance', 'agent_salary', 'support_staff_salary', 'esi_pf_contribution', 'daily_expenses', 'team_rr_incentive', 'central_team_cost', 'festival_expenses', 'petty_cash', 'input_amount', 'misc_expenses'];

            fields.forEach(function(id) {
                const val = parseFloat(document.getElementById(id).value) || 0;
                total += val;
            });

            document.getElementById('infra_total').value = total.toFixed(2);
        }
    </script>
</head>
<body>
    

    <div class="form-container">
        <h2>Infra Expense Entry</h2>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="grid">
                <div>
                    <label for="vertical_display">Vertical</label>
                    <input type="text" id="vertical_display" value="<?= htmlspecialchars($vertical_name) ?>" disabled />
                    <input type="hidden" name="vertical_id" value="<?= $vertical_id ?>" />
                </div>
                <div>
                    <label for="process_id">Select Process</label>
                    <select name="process_id" required>
                        <option value="">-- Select Process --</option>
                        <?php foreach ($processes as $process): ?>
                            <option value="<?= $process['process_id'] ?>"><?= htmlspecialchars($process['process_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" required />
                </div>
                <div>
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" required />
                </div>

                <?php
                $labels = [
                    'rent' => 'Rent',
                    'electricity' => 'Electricity',
                    'maintenance' => 'Maintenance',
                    'agent_salary' => 'Agent Salary',
                    'support_staff_salary' => 'Support Staff Salary',
                    'esi_pf_contribution' => 'ESI/PF Contribution',
                    'daily_expenses' => 'Daily Expenses',
                    'team_rr_incentive' => 'Team R&R/Incentive',
                    'central_team_cost' => 'Central Team Cost',
                    'festival_expenses' => 'Festival Expenses',
                    'petty_cash' => 'To be added / Petty Cash',
                    'input_amount' => 'Input Amount',
                    'misc_expenses' => 'Misc Expenses'
                ];

                foreach ($labels as $id => $label): ?>
                    <div>
                        <label for="<?= $id ?>"><?= $label ?></label>
                        <input type="number" id="<?= $id ?>" name="<?= $id ?>" step="0.01" min="0" />
                    </div>
                <?php endforeach; ?>

                <div>
                    <label for="infra_total">Total</label>
                    <input type="number" id="infra_total" name="infra_total" readonly />
                </div>
            </div>

            <div class="actions">
                <input type="button" class="submit-btn" value="Calculate" onclick="calculateTotal()" />
                <input type="reset" class="reset-btn" value="Reset" />
                <input type="submit" class="submit-btn" value="Save" />
                <a href="dashboard.php" class="submit-btn" style="text-decoration: none; display: inline-block; padding: 12px 28px; background-color: #7f8c8d; color: white; border-radius: 8px; font-weight: bold;">Back</a>
            </div>
        </form>
    </div>
</body>
</html>
