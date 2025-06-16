<?php
session_start();

if (!isset($_SESSION['accountant_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php'; // konexions_expense database

$vertical_id = $_SESSION['vertical_id'];
$username = $_SESSION['username'];

// TODO: Replace dummy totals below with real queries from your database
$billing_total = 200000;
$infra_total = 75000;
$it_total = 50000;
$telecom_total = 30000;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Business Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<style>
    .header {
        background-color: #003366;
        color: white;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: Arial, sans-serif;
    }

    .header-title {
        font-size: 24px;
        font-weight: bold;
    }

    .nav-menu {
        display: flex;
        gap: 25px;
        align-items: center;
    }

    .nav-item {
        color: white;
        text-decoration: none;
        position: relative;
        font-size: 16px;
    }

    .nav-item i {
        margin-right: 8px;
    }

    .dropdown {
        position: relative;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #1a4876;
        box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
        z-index: 1;
        min-width: 200px;
        border-radius: 4px;
    }

    .dropdown-content a {
        color: white;
        padding: 10px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #0d3b5b;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .nav-item:hover {
        text-decoration: underline;
    }

    .chart-container {
        width: 90%;
        max-width: 900px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        font-family: Arial, sans-serif;
    }

    .chart-container h2 {
        text-align: center;
        color: #003366;
        margin-bottom: 25px;
    }
</style>

<body>

<div class="header">
    <div class="header-title">koneXions P & L</div>
    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i>Home</a>
        <a href="billing.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i>Billing Expense</a>
        <a href="infra_expense.php" class="nav-item"><i class="fas fa-building"></i>Infra Expense</a>
        <a href="it_asset_vendor.php" class="nav-item"><i class="fas fa-laptop"></i>IT Asset and Vendors</a>
        <a href="telecom_expense.php" class="nav-item"><i class="fas fa-phone"></i>Telecom Expense</a>

        <div class="dropdown">
            <span class="nav-item"><i class="fas fa-tasks"></i>Manage Task</span>
            <div class="dropdown-content">
                <a href="add_process.php"><i class="fas fa-cogs"></i> Manage Process (Add & Delete)</a>
                <a href="manage_it_vendors.php"><i class="fas fa-plus-circle"></i> Manage IT Vendor (Add & Delete)</a>
                <a href="manage_telecom_vendors.php"><i class="fas fa-plus-circle"></i> Manage Telecom Vendors (Add & Delete)</a>
                <a href="view_expense.php"><i class="fas fa-calendar-alt"></i> View Expenses (Monthly & Yearly)</a>
            </div>
        </div>

        <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>
</div>

<!-- CHART SECTION -->
<div class="chart-container">
    <h2>Monthly Total Expense Overview</h2>
    <canvas id="expenseLineChart"></canvas>
</div>

<script>
    const ctx = document.getElementById('expenseLineChart').getContext('2d');
    const expenseChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Billing', 'Infra', 'IT Asset & Vendor', 'Telecom'],
            datasets: [{
                label: 'Total Expenses (₹)',
                data: [<?= $billing_total ?>, <?= $infra_total ?>, <?= $it_total ?>, <?= $telecom_total ?>],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#007bff',
                pointRadius: 6,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        color: '#003366',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>
