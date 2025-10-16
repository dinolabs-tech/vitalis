<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_operation_data = [];

// Fetch operation data for editing
if (isset($_GET['id'])) {
  $operation_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM operations WHERE id = ?");
  $stmt->bind_param("i", $operation_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_operation_data = $result->fetch_assoc();
  } else {
    $error_message = "Operation not found for editing.";
  }
  $stmt->close();
} else {
  $error_message = "No operation ID provided for editing.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['operation_id'])) {
  $operation_id = $_POST['operation_id'];
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['id'] : ($_POST['doctor_id'] ?? null);
  $room_id = $_POST['room_id'] ?? null;
  $operation_date = $_POST['operation_date'] ?? '';
  $start_time = $_POST['start_time'] ?? null;
  $end_time = $_POST['end_time'] ?? null;
  $procedure_name = $_POST['procedure_name'] ?? '';
  $status = $_POST['status'] ?? 'scheduled';
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($operation_date) || empty($procedure_name)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE operations SET patient_id = ?, doctor_id = ?, room_id = ?, operation_date = ?, start_time = ?, end_time = ?, procedure_name = ?, status = ?, notes = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("siissssssii", $patient_id, $doctor_id, $room_id, $operation_date, $start_time, $end_time, $procedure_name, $status, $notes, $branch_id, $operation_id);

    if ($stmt->execute()) {
      $success_message = "Operation updated successfully!";
      // Refresh data after update
      header("Location: operations.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Error updating operation: " . $stmt->error;
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

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT d.id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id ORDER BY l.staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// Fetch rooms for dropdown
$rooms = [];
$result_rooms = $conn->query("SELECT id, room_number, room_type FROM rooms ORDER BY room_number ASC");
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
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
            <h4 class="page-title">Edit Operation</h4>
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
                <a href="operations.php">Operations</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Operation</a>
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
                    <input type="hidden" name="operation_id" value="<?php echo htmlspecialchars($edit_operation_data['id'] ?? ''); ?>">

                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($edit_operation_data['patient_id']) && $edit_operation_data['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id">
                          <option value="">Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($edit_operation_data['doctor_id']) && $edit_operation_data['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="room_id">
                          <option value="">Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo (isset($edit_operation_data['room_id']) && $edit_operation_data['room_id'] == $room['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Operation Date" type="date" name="operation_date" value="<?php echo htmlspecialchars($edit_operation_data['operation_date'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Start Time" type="time" name="start_time" value="<?php echo htmlspecialchars($edit_operation_data['start_time'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="time" placeholder="End Time" name="end_time" value="<?php echo htmlspecialchars($edit_operation_data['end_time'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Procedure Name" style="border: 1px solid red;" name="procedure_name" value="<?php echo htmlspecialchars($edit_operation_data['procedure_name'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="status">
                          <option value="scheduled" <?php echo (isset($edit_operation_data['status']) && $edit_operation_data['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                          <option value="in_progress" <?php echo (isset($edit_operation_data['status']) && $edit_operation_data['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                          <option value="completed" <?php echo (isset($edit_operation_data['status']) && $edit_operation_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                          <option value="cancelled" <?php echo (isset($edit_operation_data['status']) && $edit_operation_data['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($edit_operation_data['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($edit_operation_data['branch_id']) && $edit_operation_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 text-center mt-3">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
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