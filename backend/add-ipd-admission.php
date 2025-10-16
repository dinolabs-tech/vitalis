<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? null;
  $admission_date = $_POST['admission_date'] ?? '';
  $discharge_date = $_POST['discharge_date'] ?? null;
  $room_id = $_POST['room_id'] ?? null;
  $reason_for_admission = $_POST['reason_for_admission'] ?? '';
  $diagnosis = $_POST['diagnosis'] ?? '';
  $treatment = $_POST['treatment'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $status = $_POST['status'] ?? 'admitted'; // Default status
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($admission_date) || empty($reason_for_admission)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("INSERT INTO ipd_admissions (patient_id, doctor_id, admission_date, discharge_date, room_id, reason_for_admission, diagnosis, treatment, notes, status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssiisssssi", $patient_id, $doctor_id, $admission_date, $discharge_date, $room_id, $reason_for_admission, $diagnosis, $treatment, $notes, $status, $branch_id);

      if (!$stmt->execute()) {
        throw new Exception("Error adding IPD admission: " . $stmt->error);
      }

      // Update room status if room_id is provided
      if ($room_id) {
        $stmt_room = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
        $stmt_room->bind_param("i", $room_id);
        if (!$stmt_room->execute()) {
          throw new Exception("Error occupying room: " . $stmt_room->error);
        }
        $stmt_room->close();
      }

      $conn->commit();
      $success_message = "IPD admission added successfully!";
      $_POST = array();
      header("Location: ipd-admissions.php?success=" . urlencode($success_message));
      exit; // Important to exit after header redirect
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to add IPD admission: " . $e->getMessage();
    } finally {
      if (isset($stmt) && $stmt !== null) {
        $stmt->close();
      }
    }
  }
}

$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role = 'doctor' ORDER BY staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

$rooms = [];
$result_rooms = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE status = 'available' ORDER BY room_number ASC");
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
  }
}

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
            <h4 class="page-title">Add IPD Admission</h4>
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
                <a href="#">Add IPD Admission</a>
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
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="doctor_id">
                          <option value="" selected disabled>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Admission Date" type="datetime-local" name="admission_date">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Discharge Date" type="datetime-local" name="discharge_date">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="room_id">
                          <option value="" selected disabled>Select Room</option>
                          <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_number'] . ' (' . $room['room_type'] . ')'); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Reason for Admission" name="reason_for_admission"></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Diagnosis" name="diagnosis"></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Treatment" name="treatment"></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="status">
                          <option value="" selected disabled>Status</option>
                          <option value="admitted">Admitted</option>
                          <option value="discharged">Discharged</option>
                          <option value="transferred">Transferred</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 text-center mt-3">
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