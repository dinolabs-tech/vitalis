<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $admission_id = $_POST['admission_id'] ?? '';
  $timestamp = $_POST['timestamp'] ?? '';
  $heart_rate_value = $_POST['heart_rate_value'] ?? '';
  $blood_pressure_value = $_POST['blood_pressure_value'] ?? '';

  // Basic validation
  if (empty($admission_id) || empty($timestamp) || empty($heart_rate_value) || empty($blood_pressure_value)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Insert Heart Rate
      $stmt_hr = $conn->prepare("INSERT INTO icu_patient_monitoring (admission_id, parameter_name, value, timestamp) VALUES (?, ?, ?, ?)");
      $parameter_name_hr = "Heart Rate";
      $stmt_hr->bind_param(
        "isss",
        $admission_id,
        $parameter_name_hr,
        $heart_rate_value,
        $timestamp
      );
      if (!$stmt_hr->execute()) {
        throw new Exception("Error adding Heart Rate record: " . $stmt_hr->error);
      }
      $stmt_hr->close();

      // Insert Blood Pressure
      $stmt_bp = $conn->prepare("INSERT INTO icu_patient_monitoring (admission_id, parameter_name, value, timestamp) VALUES (?, ?, ?, ?)");
      $parameter_name_bp = "Blood Pressure";
      $stmt_bp->bind_param(
        "isss",
        $admission_id,
        $parameter_name_bp,
        $blood_pressure_value,
        $timestamp
      );
      if (!$stmt_bp->execute()) {
        throw new Exception("Error adding Blood Pressure record: " . $stmt_bp->error);
      }
      $stmt_bp->close();

      $conn->commit();
      $success_message = "ICU monitoring records added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: icu_monitoring.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to add ICU monitoring records: " . $e->getMessage();
    }
  }
}

// Fetch active admissions for dropdown
$admissions = [];
$sql_admissions = "SELECT a.id AS admission_id, p.first_name, p.last_name
                   FROM admissions a
                   JOIN patients p ON a.patient_id = p.patient_id
                   WHERE a.status = 'admitted'"; // Only show active admissions
$result_admissions = $conn->query($sql_admissions);
if ($result_admissions) {
  while ($row = $result_admissions->fetch_assoc()) {
    $admissions[] = $row;
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
            <h4 class="page-title">Add ICU Monitoring</h4>
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
                <a href="icu_monitoring.php">ICU Monitoring</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add ICU Monitoring</a>
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
                  <form method="POST" action="" class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="admission_id">
                          <option value="" selected disabled>Select Admission</option>
                          <?php foreach ($admissions as $admission): ?>
                            <option value="<?php echo $admission['admission_id']; ?>" <?php echo (isset($_POST['admission_id']) && $_POST['admission_id'] == $admission['admission_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name'] . ' (Admission ID: ' . $admission['admission_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="datetime-local" class="form-control" style="border: 1px solid red;" placeholder="Timestamp" name="timestamp" value="<?php echo htmlspecialchars($_POST['timestamp'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Heart Rate Value" type="text" name="heart_rate_value" value="<?php echo htmlspecialchars($_POST['heart_rate_value'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Blood pressure Value" style="border: 1px solid red;" type="text" name="blood_pressure_value" value="<?php echo htmlspecialchars($_POST['blood_pressure_value'] ?? ''); ?>">
                      </div>
                    </div>

                    <div class="col-md-12 text-center">
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