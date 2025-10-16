<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all salary records
$ret = "SELECT s.*, e.staffname AS employeeName FROM salary s JOIN login e ON s.employee_id = e.id ORDER BY s.salary_date DESC";
$stmt = $conn->prepare($ret);
$stmt->execute();
$res = $stmt->get_result();
$salaries = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Output headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="general_staff_payroll.csv"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
ini_set('zlib.output_compression','Off');

$output = fopen('php://output', 'w');

// Output CSV headers
fputcsv($output, array('Employee Name', 'Basic Salary', 'Total Additions', 'Total Deductions', 'Net Salary', 'Salary Date'));

// Output CSV data
if (count($salaries) > 0) {
    foreach ($salaries as $row) {
        fputcsv($output, array(
            $row['employeeName'],
            $row['basic_salary'],
            $row['total_additions'],
            $row['total_deductions'],
            $row['net_salary'],
            date('d', strtotime($row['salary_date']))
        ));
    }
} else {
    fputcsv($output, array('No payroll records found.'));
}

fclose($output);
exit;
?>
