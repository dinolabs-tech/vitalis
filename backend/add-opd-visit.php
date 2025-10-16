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
  $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['id'] : ($_POST['doctor_id'] ?? null);
  $reason = $_POST['reason'] ?? '';
  $visit_date = $_POST['visit_date'] ?? '';
  $symptoms = $_POST['symptoms'] ?? '';
  $diagnosis = $_POST['diagnosis'] ?? '';
  $treatment = $_POST['treatment'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($doctor_id) || empty($visit_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("INSERT INTO opd_visits (patient_id, doctor_id, visit_date, reason_for_visit, symptoms, diagnosis, treatment, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssi", $patient_id, $doctor_id, $visit_date, $reason, $symptoms, $diagnosis, $treatment, $branch_id);

    if ($stmt->execute()) {
      $success_message = "OPD visit added successfully!";
      $_POST = array();
      header("Location: opd-visits.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add OPD visit: " . $stmt->error;
    }
    $stmt->close();
  }
}

$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
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
            <h4 class="page-title">Add OPD Visit</h4>
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
                <a href="opd-visits.php">OPD Visits</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add OPD Visit</a>
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
                            <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
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
                        <input class="form-control" style="border: 1px solid red;" placeholder="Visit Date" type="datetime-local" name="visit_date">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Reason for Visit" style="border: 1px solid red;" name="reason"></textarea>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Symptoms" name="symptoms"></textarea>
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
                        <select class="form-control form-select" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
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