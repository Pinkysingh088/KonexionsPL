<?php
session_start();

$conn = new mysqli("localhost", "root", "", "konexions_expense");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM accountants WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    $_SESSION['accountant_id'] = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['vertical_id'] = $row['vertical_id'];

    header("Location: dashboard.php");
    exit();
} else {
    echo "<script>alert('Invalid username or password'); window.location.href='index.php';</script>";
}
$conn->close();
?>
