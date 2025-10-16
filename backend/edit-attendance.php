<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$attendance_id = $_GET['id'] ?? '';
$attendance_record = null;

if (!empty($attendance_id)) {
  $stmt = $conn->prepare("SELECT * FROM staff_attendance WHERE id = ?");
  $stmt->bind_param("i", $attendance_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $attendance_record = $result->fetch_assoc();
  } else {
    $error_message = "Attendance record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No attendance ID provided.";
}

// Fetch employees for dropdown
$employees = [];
$result_employees = $conn->query("SELECT id, staffname AS name, username AS employee_id FROM login WHERE role != 'patient' ORDER BY name ASC");
if ($result_employees) {
  while ($row = $result_employees->fetch_assoc()) {
    $employees[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $attendance_record) {
  $employee_id = $_POST['employee_id'] ?? '';
  $date = $_POST['date'] ?? '';
  $punch_in = $_POST['punch_in'] ?? '';
  $punch_out = $_POST['punch_out'] ?? '';
  $production_time = $_POST['production_time'] ?? '';
  $break_time = $_POST['break_time'] ?? '';
  $overtime = $_POST['overtime'] ?? '';

  if (empty($employee_id) || empty($date) || empty($punch_in) || empty($punch_out)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE staff_attendance SET staff_id = ?, date = ?, punch_in = ?, punch_out = ?, production_time = ?, break_time = ?, overtime = ? WHERE id = ?");
    $stmt->bind_param("issssssi", $employee_id, $date, $punch_in, $punch_out, $production_time, $break_time, $overtime, $attendance_id);

    if ($stmt->execute()) {
      $success_message = "Attendance record updated successfully!";
      header("Location: attendance.php");
      exit();

      // Log audit
      $user_id = $_SESSION['user_id'];
      $action = "Updated attendance record for staff ID: " . $employee_id . " on " . $date . " (ID: " . $attendance_id . ")";
      $details = json_encode($_POST);
      $conn->query("INSERT INTO audit_logs (user_id, action, details) VALUES ('$user_id', '$action', '$details')");
    } else {
      $error_message = "Error updating attendance record: " . $stmt->error;
    }
    $stmt->close();
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
            <h4 class="page-title">Edit Attendance</h4>
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
                <a href="attendance.php">Attendance</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Attendance</a>
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
                  <form method="POST" action="">
                    <?php if ($attendance_record): ?>
                      <div class="form-group">
                        <select class="form-control form-select" style="border:1px solid red;" name="employee_id" required>
                          <option value="">Select Employee</option>
                          <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>" <?php echo ($attendance_record['staff_id'] == $employee['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($employee['name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="date" placeholder="Date" style="border:1px solid red;" name="date" value="<?php echo htmlspecialchars($attendance_record['date']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="time" placeholder="Punch In" style="border:1px solid red;" name="punch_in" value="<?php echo htmlspecialchars($attendance_record['punch_in']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="time" placeholder="Punch Out" style="border:1px solid red;" name="punch_out" value="<?php echo htmlspecialchars($attendance_record['punch_out']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" placeholder="Production Time" type="text" name="production_time" value="<?php echo htmlspecialchars($attendance_record['production_time']); ?>">
                      </div>
                      <div class="form-group">
                        <input class="form-control" placeholder="Break Time" type="text" name="break_time" value="<?php echo htmlspecialchars($attendance_record['break_time']); ?>">
                      </div>
                      <div class="form-group">
                        <input class="form-control" placeholder="Overtime" type="text" name="overtime" value="<?php echo htmlspecialchars($attendance_record['overtime']); ?>">
                      </div>
                      <div class="m-t-20 text-center">
                        <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p>No attendance record to edit.</p>
                    <?php endif; ?>
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