<?php
session_start();
include('includes/config.php'); // Assuming config.php handles DB connection

// Check if user is logged in and has appropriate role (e.g., admin or doctor)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'doctor')) {
  // header("Location: login.php");
  // exit();
}

$opd_visit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$opd_visit = null;
$patients = [];
$doctors = [];
$branches = [];
$error_message = '';
$success_message = '';

// Fetch existing OPD visit data
if ($opd_visit_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM opd_visits WHERE id = ?");
  $stmt->bind_param("i", $opd_visit_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $opd_visit = $result->fetch_assoc();
  } else {
    $error_message = "OPD Visit not found.";
  }
  $stmt->close();
} else {
  $error_message = "Invalid OPD Visit ID.";
}

// Fetch patients for dropdown
$stmt = $conn->prepare("SELECT id, first_name, last_name FROM patients ORDER BY first_name");
$stmt->execute();
$patients_result = $stmt->get_result();
while ($row = $patients_result->fetch_assoc()) {
  $patients[] = $row;
}
$stmt->close();

// Fetch doctors for dropdown (assuming doctors are users with role 'doctor' in the login table)
$stmt = $conn->prepare("SELECT id, staffname FROM login WHERE role = 'doctor' ORDER BY staffname");
$stmt->execute();
$doctors_result = $stmt->get_result();
while ($row = $doctors_result->fetch_assoc()) {
  $doctors[] = $row;
}
$stmt->close();

// Fetch branches for dropdown
$stmt = $conn->prepare("SELECT branch_id, branch_name FROM branches ORDER BY branch_name");
$stmt->execute();
$branches_result = $stmt->get_result();
while ($row = $branches_result->fetch_assoc()) {
  $branches[] = $row;
}
$stmt->close();

// Handle form submission for updating OPD visit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $opd_visit_id > 0) {
  $patient_id = intval($_POST['patient_id']);
  $doctor_id = intval($_POST['doctor_id']);
  $visit_date = $_POST['visit_date'];
  $diagnosis = $_POST['diagnosis'];
  $treatment = $_POST['treatment'];
  $symptoms = $_POST['symptoms'];
  $branch_id = intval($_POST['branch_id']);

  // Validate input (basic validation)
  if (empty($patient_id) || empty($doctor_id) || empty($visit_date) || empty($diagnosis) || empty($branch_id)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE opd_visits SET patient_id = ?, doctor_id = ?, visit_date = ?, diagnosis = ?, treatment = ?, symptoms = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("iissssii", $patient_id, $doctor_id, $visit_date, $diagnosis, $treatment, $symptoms, $branch_id, $opd_visit_id);

    if ($stmt->execute()) {
      $success_message = "OPD Visit updated successfully!";
      header("Location: opd-visits.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Error updating OPD Visit: " . $stmt->error;
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
            <h4 class="page-title">Edit OPD Visit</h4>
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
                <a href="#">Edit OPD Visit</a>
              </li>
            </ul>
          </div>

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

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <?php if ($opd_visit): ?>
                    <form method="POST" class="row">
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                              <option value="<?php echo htmlspecialchars($patient['id']); ?>"
                                <?php echo ($opd_visit['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select" name="doctor_id" style="border: 1px solid red;" required>
                            <option value="">Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                              <option value="<?php echo htmlspecialchars($doctor['id']); ?>"
                                <?php echo ($opd_visit['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['staffname']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <input type="datetime-local" style="border: 1px solid red;" placeholder="Visit Date" class="form-control" name="visit_date" value="<?php echo htmlspecialchars($opd_visit['visit_date']); ?>" required>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <input type="text" class="form-control" style="border: 1px solid red;" placeholder="Diagnosis" name="diagnosis" value="<?php echo htmlspecialchars($opd_visit['diagnosis']); ?>" required>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <textarea class="form-control" name="treatment" placeholder="Treatment" rows="3"><?php echo htmlspecialchars($opd_visit['treatment']); ?></textarea>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Symptoms" name="symptoms" rows="3"><?php echo htmlspecialchars($opd_visit['symptoms']); ?></textarea>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select" style="border: 1px solid red;" name="branch_id" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                              <option value="<?php echo htmlspecialchars($branch['branch_id']); ?>"
                                <?php echo ($opd_visit['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-12 col-sm-6 mt-3">
                        <button type="submit" class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                        <a href="opd-visits.php" class="btn btn-secondary btn-icon btn-round"><i class="fas fa-times"></i></a>
                      </div>
                    </form>
                  <?php else: ?>
                    <p><?php echo $error_message; ?></p>
                    <a href="opd-visits.php" class="btn btn-primary">Back to OPD Visits</a>
                  <?php endif; ?>
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