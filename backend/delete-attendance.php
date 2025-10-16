<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_id = $_POST['attendance_id'] ?? '';

    if (empty($attendance_id)) {
        $_SESSION['error_message'] = "No attendance ID provided for deletion.";
    } else {
        $stmt = $conn->prepare("DELETE FROM staff_attendance WHERE id = ?");
        $stmt->bind_param("i", $attendance_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Attendance record deleted successfully!";
            // Log audit
            $user_id = $_SESSION['user_id'];
            $action = "Deleted attendance record with ID: " . $attendance_id;
            $details = json_encode(['attendance_id' => $attendance_id]);
            $conn->query("INSERT INTO audit_logs (user_id, action, details) VALUES ('$user_id', '$action', '$details')");
        } else {
            $_SESSION['error_message'] = "Error deleting attendance record: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: attendance.php");
    exit;
} else {
    header("Location: attendance.php");
    exit;
}
?>
