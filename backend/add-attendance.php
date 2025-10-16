<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Fetch employees for dropdown
$employees = [];
$result_employees = $conn->query("SELECT id, staffname AS name, username AS employee_id FROM login WHERE role != 'patient' ORDER BY name ASC");
if ($result_employees) {
  while ($row = $result_employees->fetch_assoc()) {
    $employees[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $stmt = $conn->prepare("INSERT INTO staff_attendance (staff_id, date, punch_in, punch_out, production_time, break_time, overtime) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $employee_id, $date, $punch_in, $punch_out, $production_time, $break_time, $overtime);

    if ($stmt->execute()) {
      $success_message = "Attendance record added successfully!";
      // Log audit
      $user_id = $_SESSION['user_id'];
      $action = "Added attendance record for staff ID: " . $employee_id . " on " . $date;
      $details = json_encode($_POST);
      $conn->query("INSERT INTO audit_logs (user_id, action, details) VALUES ('$user_id', '$action', '$details')");

      header("Location: attendance.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Error adding attendance record: " . $stmt->error;
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
            <h4 class="page-title">Add Attendance</h4>
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
                <a href="#">Add Attendance</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6><hr>
                </div>
                <div class="class-body">
                  <form method="POST" action="">
                    <div class="form-group">
                      <select class="form-control form-select" style="border: 1px solid red;" name="employee_id" required>
                        <option value="" selected disabled>Select Employee</option>
                        <?php foreach ($employees as $employee): ?>
                          <option value="<?php echo $employee['id']; ?>" <?php echo (isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($employee['name'] . ' (' . $employee['employee_id'] . ')'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <input class="form-control" type="date" placeholder="Date" style="border: 1px solid red;" name="date" value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="time" style="border: 1px solid red;" placeholder="Punch In" name="punch_in" value="<?php echo htmlspecialchars($_POST['punch_in'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Punch Out" type="time" name="punch_out" value="<?php echo htmlspecialchars($_POST['punch_out'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="text" name="production_time" placeholder="Production Time" value="<?php echo htmlspecialchars($_POST['production_time'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="text" name="break_time" placeholder="Break Time" value="<?php echo htmlspecialchars($_POST['break_time'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="text" name="overtime" placeholder="Overtime" value="<?php echo htmlspecialchars($_POST['overtime'] ?? ''); ?>">
                    </div>
                    <div class="col-md-12 mb-5 text-center">
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