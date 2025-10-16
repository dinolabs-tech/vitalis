<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or receptionist role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_admission_data = [];
$admission_id = $_GET['id'] ?? null;

// Fetch existing admission data if ID is provided
if ($admission_id) {
  $stmt = $conn->prepare("SELECT * FROM admissions WHERE id = ?");
  $stmt->bind_param("i", $admission_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_admission_data = $result->fetch_assoc();
  } else {
    $error_message = "Admission not found.";
    // Redirect if admission not found
    header("Location: admissions.php?error=" . urlencode($error_message));
    exit;
  }
  $stmt->close();
} else {
  $error_message = "No admission ID provided for editing.";
  header("Location: admissions.php?error=" . urlencode($error_message));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $room_id = $_POST['room_id'] ?? '';
  $admission_date = $_POST['admission_date'] ?? '';
  $discharge_date = $_POST['discharge_date'] ?? null;
  $reason = $_POST['reason'] ?? '';
  $status = $_POST['status'] ?? 'admitted';
  $admitted_by_staff_id = $_SESSION['id'] ?? null; // Assuming current logged-in staff

  // Basic validation
  if (empty($patient_id) || empty($room_id) || empty($admission_date) || empty($reason)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Get current room_id before update to handle room status change
      $old_room_id = $edit_admission_data['room_id'];

      // Update admissions table
      $stmt = $conn->prepare("UPDATE admissions SET patient_id = ?, room_id = ?, admission_date = ?, discharge_date = ?, reason = ?, status = ?, admitted_by_staff_id = ? WHERE id = ?");
      $stmt->bind_param("sisssisi", $patient_id, $room_id, $admission_date, $discharge_date, $reason, $status, $admitted_by_staff_id, $admission_id);

      if (!$stmt->execute()) {
        throw new Exception("Error updating admission: " . $stmt->error);
      }
      $stmt->close();

      // Update room status if room_id changed or status changed
      if ($old_room_id != $room_id) {
        // Free up the old room
        $stmt_old_room = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt_old_room->bind_param("i", $old_room_id);
        if (!$stmt_old_room->execute()) {
          throw new Exception("Error freeing old room status: " . $stmt_old_room->error);
        }
        $stmt_old_room->close();

        // Occupy the new room
        $stmt_new_room = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmt_new_room->bind_param("i", $room_id);
        if (!$stmt_new_room->execute()) {
          throw new Exception("Error occupying new room status: " . $stmt_new_room->error);
        }
        $stmt_new_room->close();
      } elseif ($status === 'discharged' && $edit_admission_data['status'] !== 'discharged') {
        // If status changed to discharged and room wasn't changed, free up the room
        $stmt_room_free = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt_room_free->bind_param("i", $room_id);
        if (!$stmt_room_free->execute()) {
          throw new Exception("Error freeing room on discharge: " . $stmt_room_free->error);
        }
        $stmt_room_free->close();
      } elseif ($status === 'admitted' && $edit_admission_data['status'] !== 'admitted') {
        // If status changed to admitted and room wasn't changed, occupy the room
        $stmt_room_occupy = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmt_room_occupy->bind_param("i", $room_id);
        if (!$stmt_room_occupy->execute()) {
          throw new Exception("Error occupying room on re-admission: " . $stmt_room_occupy->error);
        }
        $stmt_room_occupy->close();
      }

      $conn->commit();
      $success_message = "Admission updated successfully!";
      // Refresh data after successful update
      $stmt = $conn->prepare("SELECT * FROM admissions WHERE id = ?");
      $stmt->bind_param("i", $admission_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $edit_admission_data = $result->fetch_assoc();
      $stmt->close();
      header("Location: admissions.php?success=" . urlencode($success_message));
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update admission: " . $e->getMessage();
    }
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch available rooms for dropdown (and the currently assigned room, even if occupied)
$rooms = [];
$current_room_id = $edit_admission_data['room_id'] ?? null;
$sql_rooms = "SELECT id, room_number, room_type FROM rooms WHERE status = 'available'";
if ($current_room_id) {
  $sql_rooms .= " OR id = " . $current_room_id;
}
$sql_rooms .= " ORDER BY room_number ASC";

$result_rooms = $conn->query($sql_rooms);
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
  }
}

// Use fetched data to pre-fill form or use POST data if submission failed
$display_patient_id = $_POST['patient_id'] ?? ($edit_admission_data['patient_id'] ?? '');
$display_room_id = $_POST['room_id'] ?? ($edit_admission_data['room_id'] ?? '');
$display_admission_date = $_POST['admission_date'] ?? ($edit_admission_data['admission_date'] ?? '');
$display_discharge_date = $_POST['discharge_date'] ?? ($edit_admission_data['discharge_date'] ?? '');
$display_reason = $_POST['reason'] ?? ($edit_admission_data['reason'] ?? '');
$display_status = $_POST['status'] ?? ($edit_admission_data['status'] ?? 'admitted');

?>

<!DOCTYPE html>
<html lang="en">
<?php include('components/head.php'); ?>

<body>
  <div class="wrapper">
    <?php include('components/sidebar.php'); ?>

    <div class="main-panel">
      <?php include('components/navbar.php'); ?>

      <div class="container">
        <div class="page-inner">
          <div class="page-header">
            <h4 class="page-title">Edit Admission</h4>
            <ul class="breadcrumbs">
              <li class="nav-home">
                <a href="index.php">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="admissions.php">Admissions</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Admission</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">
              <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php echo $error_message; ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>
              <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <?php echo $success_message; ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo ($display_patient_id == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="room_id">
                          <option value="">Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo ($display_room_id == $room['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Admission Date" type="datetime-local" name="admission_date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($display_admission_date))); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Discharge Date" type="datetime-local" name="discharge_date" value="<?php echo htmlspecialchars($display_discharge_date ? date('Y-m-d\TH:i', strtotime($display_discharge_date)) : ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Reason for Admission" name="reason"><?php echo htmlspecialchars($display_reason); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="status">
                          <option value="admitted" <?php echo ($display_status == 'admitted') ? 'selected' : ''; ?>>Admitted</option>
                          <option value="discharged" <?php echo ($display_status == 'discharged') ? 'selected' : ''; ?>>Discharged</option>
                          <option value="transferred" <?php echo ($display_status == 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  </form>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <?php include('components/footer.php'); ?>
  </div>
  </div>
  <?php include('components/script.php'); ?>
</body>

</html>