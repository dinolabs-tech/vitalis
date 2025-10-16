<?php
session_start();
include('database/db_connect.php'); // Include your database connection

// Function to set session message
function setSessionMessage($type, $message) {
    $_SESSION['status'] = $message;
    $_SESSION['status_code'] = $type;
}

// Add Room
if (isset($_POST['add_room_btn'])) {
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $capacity = $_POST['capacity'];
    $room_cost = $_POST['room_cost'];
    $bed_cost = $_POST['bed_cost'];
    $status = $_POST['status'];
    $branch_id = $_POST['branch_id'];

    $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type, capacity, room_cost, bed_cost, status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiddss", $room_number, $room_type, $capacity, $room_cost, $bed_cost, $status, $branch_id);

    if ($stmt->execute()) {
        setSessionMessage("success", "Room Added Successfully");
    } else {
        setSessionMessage("error", "Error Adding Room: " . $stmt->error);
    }
    $stmt->close();
    header('Location: rooms.php');
    exit();
}

// Update Room
if (isset($_POST['update_room_btn'])) {
    $room_id = $_POST['edit_room_id'];
    $room_number = $_POST['room_number'];
    $room_type = $_POST['room_type'];
    $capacity = $_POST['capacity'];
    $room_cost = $_POST['room_cost'];
    $bed_cost = $_POST['bed_cost'];
    $status = $_POST['status'];
    $branch_id = $_POST['branch_id'];

    $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type=?, capacity=?, room_cost=?, bed_cost=?, status=?, branch_id=? WHERE id=?");
    $stmt->bind_param("ssiddssi", $room_number, $room_type, $capacity, $room_cost, $bed_cost, $status, $branch_id, $room_id);

    if ($stmt->execute()) {
        setSessionMessage("success", "Room Updated Successfully");
    } else {
        setSessionMessage("error", "Error Updating Room: " . $stmt->error);
    }
    $stmt->close();
    header('Location: rooms.php');
    exit();
}

// Delete Room
if (isset($_POST['delete_room_btn'])) {
    $room_id = $_POST['delete_room_id'];

    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
    $stmt->bind_param("i", $room_id);

    if ($stmt->execute()) {
        setSessionMessage("success", "Room Deleted Successfully");
    } else {
        setSessionMessage("error", "Error Deleting Room: " . $stmt->error);
    }
    $stmt->close();
    header('Location: rooms.php');
    exit();
}

// Close the database connection
$conn->close();
?>
