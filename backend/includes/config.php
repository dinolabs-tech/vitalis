<?php
// Database connection
require_once 'database/db_connect.php';

// Global mysqli connection object
global $mysqli;
$mysqli = $conn;

// Define base URL for redirects if needed
define('BASE_URL', 'https://www.dinolabstech.com/vitalis/backend/');
