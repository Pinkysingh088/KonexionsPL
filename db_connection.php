<?php
// db_connection.php
$servername = "localhost";  // Database server (e.g., localhost)
$username = "root";         // Database username
$password = "";             // Database password (empty if default)
$dbname = "konexions_expense";    // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
