<?php
include 'includes/config.php';
include 'includes/checklogin.php';

if (isset($_POST['submit'])) {
    $employeeId = $_POST['employeeId'];
    $providentFundAmount = $_POST['providentFundAmount'];
    $employeeShare = $_POST['employeeShare'];
    $organizationShare = $_POST['organizationShare'];
    $description = $_POST['description'];

    $query = "INSERT INTO provident_fund (employeeId, providentFundAmount, employeeShare, organizationShare, description) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssss', $employeeId, $providentFundAmount, $employeeShare, $organizationShare, $description);
    
    if ($stmt->execute()) {
        header("Location: provident-fund.php");
        exit();
    } else {
        header("Location: provident-fund.php");
        exit();
    }
    
    $stmt->close();
    
    echo "<script>window.location.href = 'provident-fund.php';</script>";
}
?>
