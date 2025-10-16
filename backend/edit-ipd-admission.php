<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_ipd_admission_data = [];

// Fetch IPD admission data for editing
if (isset($_GET['id'])) {
  $ipd_admission_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM ipd_admissions WHERE id = ?");
  $stmt->bind_param("i", $ipd_admission_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_ipd_admission_data = $result->fetch_assoc();
  } else {
    $error_message = "IPD Admission not found for editing.";
  }
  $stmt->close();
} else {
  $error_message = "No IPD Admission ID provided for editing.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ipd_admission_id'])) {
  $ipd_admission_id = $_POST['ipd_admission_id'];
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? null;
  $admission_date = $_POST['admission_date'] ?? '';
  $discharge_date = $_POST['discharge_date'] ?? '';
  $room_id = $_POST['room_id'] ?? null;
  $reason_for_admission = $_POST['reason_for_admission'] ?? '';
  $diagnosis = $_POST['diagnosis'] ?? '';
  $treatment = $_POST['treatment'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $status = $_POST['status'] ?? 'admitted';
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($admission_date) || empty($reason_for_admission)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();
    try {
      // Get old room_id to free it if changed
      $old_room_id = null;
      $stmt_old_room = $conn->prepare("SELECT room_id FROM ipd_admissions WHERE id = ?");
      $stmt_old_room->bind_param("i", $ipd_admission_id);
      $stmt_old_room->execute();
      $result_old_room = $stmt_old_room->get_result();
      if ($row = $result_old_room->fetch_assoc()) {
        $old_room_id = $row['room_id'];
      }
      $stmt_old_room->close();

      $stmt = $conn->prepare("UPDATE ipd_admissions SET patient_id = ?, doctor_id = ?, admission_date = ?, discharge_date = ?, room_id = ?, reason_for_admission = ?, diagnosis = ?, treatment = ?, notes = ?, status = ?, branch_id = ? WHERE id = ?");
      $stmt->bind_param("ssssisssssii", $patient_id, $doctor_id, $admission_date, $discharge_date, $room_id, $reason_for_admission, $diagnosis, $treatment, $notes, $status, $branch_id, $ipd_admission_id);

      if (!$stmt->execute()) {
        throw new Exception("Error updating IPD admission: " . $stmt->error);
      }
      $stmt->close();

      // Update room statuses
      if ($old_room_id && $old_room_id != $room_id) {
        // Free the old room
        $stmt_free_old_room = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt_free_old_room->bind_param("i", $old_room_id);
        if (!$stmt_free_old_room->execute()) {
          throw new Exception("Error freeing old room: " . $stmt_free_old_room->error);
        }
        $stmt_free_old_room->close();
      }

      if ($room_id) {
        // Occupy the new room if different or if status is admitted
        $room_status_to_set = ($status === 'admitted') ? 'occupied' : 'available';
        $stmt_occupy_new_room = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt_occupy_new_room->bind_param("si", $room_status_to_set, $room_id);
        if (!$stmt_occupy_new_room->execute()) {
          throw new Exception("Error updating new room status: " . $stmt_occupy_new_room->error);
        }
        $stmt_occupy_new_room->close();
      }

      $conn->commit();
      $success_message = "IPD admission updated successfully!";
      header("Location: ipd-admissions.php?success=" . urlencode($success_message));
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update IPD admission: " . $e->getMessage();
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

// Fetch available rooms for dropdown (include current room if occupied)
$rooms = [];
$room_sql = "SELECT id, room_number, room_type FROM rooms WHERE status = 'available'";
if (isset($edit_ipd_admission_data['room_id']) && $edit_ipd_admission_data['room_id']) {
  $room_sql .= " OR id = " . $edit_ipd_admission_data['room_id'];
}
$room_sql .= " ORDER BY room_number ASC";
$result_rooms = $conn->query($room_sql);
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
  }
}

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT d.id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id ORDER BY l.staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
  }
}
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
            <h4 class="page-title">Edit IPD Admission</h4>
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
                <a href="ipd-admissions.php">IPD Admission</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit IPD Admission</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">
              <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
              <?php endif; ?>
              <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
              <?php endif; ?>

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <input type="hidden" name="ipd_admission_id" value="<?php echo htmlspecialchars($edit_ipd_admission_data['id'] ?? ''); ?>">

                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($edit_ipd_admission_data['patient_id']) && $edit_ipd_admission_data['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id">
                          <option value="">Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($edit_ipd_admission_data['doctor_id']) && $edit_ipd_admission_data['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Admission Date" type="datetime-local" name="admission_date" value="<?php echo htmlspecialchars(str_replace(' ', 'T', $edit_ipd_admission_data['admission_date'] ?? '')); ?>" required>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Discharge Date" type="datetime-local" name="discharge_date" value="<?php echo htmlspecialchars(str_replace(' ', 'T', $edit_ipd_admission_data['discharge_date'] ?? '')); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="room_id">
                          <option value="">Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo (isset($edit_ipd_admission_data['room_id']) && $edit_ipd_admission_data['room_id'] == $room['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($edit_ipd_admission_data['branch_id']) && $edit_ipd_admission_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Reason for Admission" style="border: 1px solid red;" name="reason_for_admission" required><?php echo htmlspecialchars($edit_ipd_admission_data['reason_for_admission'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Diagnosis" name="diagnosis"><?php echo htmlspecialchars($edit_ipd_admission_data['diagnosis'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Treatment" name="treatment"><?php echo htmlspecialchars($edit_ipd_admission_data['treatment'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($edit_ipd_admission_data['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="status">
                          <option value="admitted" <?php echo (isset($edit_ipd_admission_data['status']) && $edit_ipd_admission_data['status'] == 'admitted') ? 'selected' : ''; ?>>Admitted</option>
                          <option value="discharged" <?php echo (isset($edit_ipd_admission_data['status']) && $edit_ipd_admission_data['status'] == 'discharged') ? 'selected' : ''; ?>>Discharged</option>
                          <option value="transferred" <?php echo (isset($edit_ipd_admission_data['status']) && $edit_ipd_admission_data['status'] == 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mt-3">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  </form>
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