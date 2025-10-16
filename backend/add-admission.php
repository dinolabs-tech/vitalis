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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $room_id = $_POST['room_id'] ?? '';
  $admission_date = $_POST['admission_date'] ?? '';
  $discharge_date = $_POST['discharge_date'] ?? null;
  $reason = $_POST['reason'] ?? '';
  $status = $_POST['status'] ?? 'admitted';
  $admitted_by_staff_id = $_SESSION['id'] ?? null; // Assuming current logged-in staff admits the patient

  // Basic validation
  if (empty($patient_id) || empty($room_id) || empty($admission_date) || empty($reason)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Insert into admissions table
    $stmt = $conn->prepare("INSERT INTO admissions (patient_id, room_id, admission_date, discharge_date, reason, status, admitted_by_staff_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssi", $patient_id, $room_id, $admission_date, $discharge_date, $reason, $status, $admitted_by_staff_id);

    if ($stmt->execute()) {
      $success_message = "Patient admitted successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: admissions.php?success=" . urlencode($success_message));
    } else {
      $error_message = "Failed to admit patient: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch available rooms for dropdown
$rooms = [];
$result_rooms = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE status = 'available'");
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
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
            <h4 class="page-title">Add Admission</h4>
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
                <a href="#">Add Admission</a>
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
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mb-5 border-danger" name="patient_id" required>
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mb-5" style="border:1px solid red;" name="room_id" required>
                          <option value="" selected disabled>Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" <?php echo (isset($_POST['room_id']) && $_POST['room_id'] == $room['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <input class="form-control" style="border:1px solid red;" placeholder="Admission Date" type="datetime-local" name="admission_date" value="<?php echo htmlspecialchars($_POST['admission_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <input class="form-control" placeholder="Discharge Date" type="datetime-local" name="discharge_date" value="<?php echo htmlspecialchars($_POST['discharge_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" style="border:1px solid red;" placeholder="Reason for Admission" name="reason"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border:1px solid red;" name="status">
                          <option value="" selected disabled>Status</option>
                          <option value="admitted" <?php echo (isset($_POST['status']) && $_POST['status'] == 'admitted') ? 'selected' : ''; ?>>Admitted</option>
                          <option value="discharged" <?php echo (isset($_POST['status']) && $_POST['status'] == 'discharged') ? 'selected' : ''; ?>>Discharged</option>
                          <option value="transferred" <?php echo (isset($_POST['status']) && $_POST['status'] == 'transferred') ? 'selected' : ''; ?>>Transferred</option>
                        </select>
                      </div>
                    </div>
                    <div class="m-t-20 text-center">
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