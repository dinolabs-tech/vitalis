<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$schedule_data = [];
$schedule_id = $_GET['id'] ?? null;

if ($schedule_id) {
  // Fetch existing schedule data
  $stmt = $conn->prepare("SELECT doctor_id, day_of_week, start_time, end_time, status FROM doctor_schedules WHERE id = ?");
  $stmt->bind_param("i", $schedule_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $schedule_data = $result->fetch_assoc();
  } else {
    $error_message = "Schedule not found.";
  }
  $stmt->close();
} else {
  $error_message = "No schedule ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $schedule_id) {
  $doctor_id = $_POST['doctor_id'] ?? '';
  $day_of_week = $_POST['day_of_week'] ?? '';
  $start_time = $_POST['start_time'] ?? '';
  $end_time = $_POST['end_time'] ?? '';
  $status = $_POST['status'] ?? 'active';

  if (empty($doctor_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE doctor_schedules SET doctor_id = ?, day_of_week = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?");
    $stmt->bind_param("issssi", $doctor_id, $day_of_week, $start_time, $end_time, $status, $schedule_id);

    if ($stmt->execute()) {
      $success_message = "Schedule updated successfully!";
      header("Location:schedule.php");
      exit();
    } else {
      $error_message = "Failed to update schedule: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role = 'doctor'");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
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
            <h4 class="page-title">Edit Doctor Schedule</h4>
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
                <a href="schedule.php">Doctor Schedule</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Doctor Schedule</a>
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
                  <form method="POST" action="" class="row g-3">

                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select rounded" style="border: 1px solid red;" name="doctor_id" required>
                          <option value="" selected disabled>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php if (isset($schedule_data['doctor_id']) && (int)$schedule_data['doctor_id'] == (int)$doctor['id']) echo 'selected'; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select rounded" style="border: 1px solid red;" name="day_of_week" required>
                          <option value="" selected disabled>Select Day</option>
                          <?php
                          $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                          foreach ($days_of_week as $day) {
                            $selected = (isset($schedule_data['day_of_week']) && $schedule_data['day_of_week'] == $day) ? 'selected' : '';
                            echo "<option value=\"$day\" $selected>$day</option>";
                          }
                          ?>
                        </select>
                      </div>
                    </div>


                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="time" class="form-control rounded" name="start_time" placeholder="Start Time" style="border: 1px solid red;" value="<?php echo htmlspecialchars($schedule_data['start_time'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="time" class="form-control rounded" name="end_time" placeholder="End Time" style="border: 1px solid red;" value="<?php echo htmlspecialchars($schedule_data['end_time'] ?? ''); ?>" required>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group d-flex">
                        <label class="mt-3 mx-3">Schedule Status</label>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="status" id="schedule_active" value="active" <?php echo (isset($schedule_data['status']) && $schedule_data['status'] == 'active') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="schedule_active">
                            Active
                          </label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="status" id="schedule_inactive" value="inactive" <?php echo (isset($schedule_data['status']) && $schedule_data['status'] == 'inactive') ? 'checked' : ''; ?>>
                          <label class="form-check-label" for="schedule_inactive">
                            Inactive
                          </label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-12 text-center">
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