<?php
session_start();

$conn = new mysqli("localhost", "root", "", "konexions_expense");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM admin_users WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    $_SESSION['admin_id'] = $row['admin_id'];
    $_SESSION['username'] = $row['username'];

    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "<script>alert('Invalid admin credentials'); window.location.href='index.php';</script>";
}
$conn->close();
?>
