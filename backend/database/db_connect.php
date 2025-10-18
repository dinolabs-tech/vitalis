<?php
$servername = "localhost";
$username = "dinolabs_root";
$password = "foxtrot2november";
$dbname = "dinolabs_vitalis";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}



// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "vitalis";

// // Create connection to MySQL
// $conn = new mysqli($servername, $username, $password);

// // Check connection
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }

// // Create database if it doesn't exist
// $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
// if ($conn->query($sql) === FALSE) {
//     die("Error creating database: " . $conn->error);
// }

// // Select the database
// $conn->select_db($dbname);
?>
