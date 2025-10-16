<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $doctor_id = $_POST['doctor_id'] ?? '';
  $day_of_week = $_POST['day_of_week'] ?? '';
  $start_time = $_POST['start_time'] ?? '';
  $end_time = $_POST['end_time'] ?? '';
  $status = $_POST['status'] ?? 'active';

  if (empty($doctor_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $doctor_id, $day_of_week, $start_time, $end_time, $status);

    if ($stmt->execute()) {
      $success_message = "Schedule added successfully!";
      $_POST = array();
      header("Location: schedule.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add schedule: " . $stmt->error;
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
            <h4 class="page-title">Add Doctor Schedule</h4>
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
                <a href="#">Add Doctor Schedule</a>
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
                        <select class="form-control form-select" style="border: 1px solid red;" name="doctor_id" required>
                          <option value="" disabled selected>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($_POST['id']) && $_POST['id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="day_of_week" style="border: 1px solid red;" required>
                          <option value="" selected disabled>Select Day</option>
                          <option value="Sunday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                          <option value="Monday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                          <option value="Tuesday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                          <option value="Wednesday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                          <option value="Thursday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                          <option value="Friday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                          <option value="Saturday" <?php echo (isset($_POST['day_of_week']) && $_POST['day_of_week'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" type="time" name="start_time" placeholder="Start Time" value="<?php echo htmlspecialchars($_POST['start_time'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="time" name="end_time" placeholder="End Time" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="status">
                          <option value="" selected disabled>Status</option>
                          <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                          <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 mt-3 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
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