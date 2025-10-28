<?php
/**
 * backup.php
 *
 * This script performs an automated daily backup of the MySQL database using `mysqldump`.
 * It checks if a backup has been performed within the last 24 hours and, if not,
 * executes the backup command and logs the outcome. The backup file is saved in the
 * site's root directory with a fixed name based on the database name.
 *
 * Key functionalities include:
 * - Database connection.
 * - Defining backup directory, filename, and log file.
 * - Setting a daily backup interval.
 * - Checking if a backup is due.
 * - Executing the `mysqldump` command to create a SQL dump.
 * - Logging the backup process, including success/failure and any errors.
 * - Error handling for database connection.
 */

// Include the database connection file. This file should establish a connection
// to the MySQL database and make the $conn object available, along with
// $servername, $username, $password, and $dbname.
include 'database/db_connect.php';

// Check if the database connection was successful. If not, terminate the script
// and display an error message.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define the directory to save the backup file. __DIR__ refers to the directory
// of the current script (site root folder in this case).
$backupDir = __DIR__;
// Define the backup file path. It uses a fixed filename based on the database name
// to ensure only one backup file is kept, overwriting the old one.
$backupFile = $backupDir . '/backup_' . $dbname . '.sql';
// Define the log file path for recording backup activities.
$logFile = $backupDir . '/backup.log';

// Define the backup interval in seconds (24 hours = 86400 seconds).
$backupInterval = 86400;

// Determine if a backup should be performed.
// A backup is needed if the backup file does not exist, or if the last modification
// time of the backup file is older than the defined backup interval.
$shouldBackup = !file_exists($backupFile) || (time() - filemtime($backupFile) >= $backupInterval);

// If a backup is due, proceed with the backup process.
if ($shouldBackup) {
    // Specify the path to the `mysqldump` executable.
    // This might need adjustment depending on the server environment (e.g., '/usr/bin/mysqldump' on Linux).
    $mysqldumpPath = 'mysqldump';

    // Build the `mysqldump` command.
    // `escapeshellarg` is used to properly escape arguments for shell commands, preventing command injection.
    // `2>&1` redirects standard error to standard output, so `exec` captures all output.
    $command = sprintf(
        '%s --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($mysqldumpPath),
        escapeshellarg($servername),
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($dbname),
        escapeshellarg($backupFile)
    );

    // Execute the command and capture its output and return status.
    exec($command, $output, $returnVar);

    // Log detailed results of the command execution.
    $logMessage = date('[Y-m-d H:i:s]') . " Command executed: " . $command . PHP_EOL;
    $logMessage .= date('[Y-m-d H:i:s]') . " Return code: " . $returnVar . PHP_EOL;
    if ($returnVar === 0) {
        // If the command executed successfully (return code 0).
        $logMessage .= date('[Y-m-d H:i:s]') . " Backup successful: " . $backupFile . " (" . filesize($backupFile) . " bytes)" . PHP_EOL;
    } else {
        // If the command failed.
        $logMessage .= date('[Y-m-d H:i:s]') . " Error: Backup failed with status code " . $returnVar . PHP_EOL;
        $logMessage .= date('[Y-m-d H:i:s]') . " Output: " . implode("\n", $output) . PHP_EOL; // Include command output for debugging.
    }
    // Append the log message to the log file.
    file_put_contents($logFile, $logMessage, FILE_APPEND);

    // Additional check: If the backup file exists but is empty, log a warning.
    if (file_exists($backupFile) && filesize($backupFile) === 0) {
        file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Warning: Backup file is empty: " . $backupFile . PHP_EOL, FILE_APPEND);
    }
}

// Close the database connection.
$conn->close();
?>
