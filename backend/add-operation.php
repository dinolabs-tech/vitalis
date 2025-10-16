<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $stmt = $conn->prepare("INSERT INTO operations (patient_id, doctor_id, room_id, operation_date, start_time, end_time, procedure_name, status, notes, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siissssssi", $patient_id, $doctor_id, $room_id, $operation_date, $start_time, $end_time, $procedure_name, $status, $notes, $branch_id);

    if ($stmt->execute()) {
      $success_message = "Operation added successfully!";
      $_POST = array();
      header("Location: operations.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Error adding operation: " . $stmt->error;
    }
    $stmt->close();
  }
}

$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

$rooms = [];
$result_rooms = $conn->query("SELECT id, room_number, room_type FROM rooms ORDER BY room_number ASC");
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
  }
}

$doctors = [];
$result_doctors = $conn->query("SELECT id, staffname FROM login WHERE role = 'doctor'");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
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
            <h4 class="page-title">Add Operation</h4>
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
                <a href="#">Add Operation</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border with compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">

                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id">
                          <option value="" selected disabled>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="room_id">
                          <option value="" selected disabled>Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Operation Date" style="border: 1px solid red;" type="date" name="operation_date" required>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Start Time" type="time" name="start_time">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="End Time" type="time" name="end_time">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Procedure Name" style="border: 1px solid red;" type="text" name="procedure_name" required>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="status">
                          <option value="" selected disabled>Status</option>
                          <option value="scheduled">Scheduled</option>
                          <option value="in_progress">In Progress</option>
                          <option value="completed">Completed</option>
                          <option value="cancelled">Cancelled</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-3">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-plus"></i></button>
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