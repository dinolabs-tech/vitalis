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
$icu_monitoring_data = [];
$monitoring_id = $_GET['id'] ?? null;

if ($monitoring_id) {
  // Fetch existing ICU monitoring data
  $stmt = $conn->prepare("SELECT icu.*, p.first_name, p.last_name, a.patient_id AS admission_patient_id
                            FROM icu_patient_monitoring icu
                            JOIN admissions a ON icu.admission_id = a.id
                            JOIN patients p ON a.patient_id = p.patient_id
                            WHERE icu.id = ?");
  $stmt->bind_param("i", $monitoring_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $icu_monitoring_data = $result->fetch_assoc();
  } else {
    $error_message = "ICU monitoring record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No ICU monitoring ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $monitoring_id) {
  $admission_id = $_POST['admission_id'] ?? '';
  $parameter_name = $_POST['parameter_name'] ?? '';
  $value = $_POST['value'] ?? '';
  $timestamp = $_POST['timestamp'] ?? '';

  // Basic validation
  if (empty($admission_id) || empty($parameter_name) || empty($value) || empty($timestamp)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Update icu_patient_monitoring table
      $stmt = $conn->prepare("UPDATE icu_patient_monitoring SET admission_id = ?, parameter_name = ?, value = ?, timestamp = ? WHERE id = ?");
      $stmt->bind_param(
        "isssi", // i for admission_id, s for parameter_name, s for value, s for timestamp, i for monitoring_id
        $admission_id,
        $parameter_name,
        $value,
        $timestamp,
        $monitoring_id
      );

      if (!$stmt->execute()) {
        throw new Exception("Error updating ICU monitoring record: " . $stmt->error);
      }
      $stmt->close();

      $conn->commit();
      $success_message = "ICU monitoring record updated successfully!";
      // Refresh data after update
      header("Location: icu_monitoring.php?success=" . urlencode($success_message));
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update ICU monitoring record: " . $e->getMessage();
    }
  }
}

// If redirected with success message
if (isset($_GET['success']) && $_GET['success'] === 'true') {
  $success_message = "ICU monitoring record updated successfully!";
  // After successful update, re-fetch the data to display the latest changes
  $stmt = $conn->prepare("SELECT icu.*, p.first_name, p.last_name, a.patient_id AS admission_patient_id
                            FROM icu_patient_monitoring icu
                            JOIN admissions a ON icu.admission_id = a.id
                            JOIN patients p ON a.patient_id = p.patient_id
                            WHERE icu.id = ?");
  $stmt->bind_param("i", $monitoring_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $icu_monitoring_data = $result->fetch_assoc();
  }
  $stmt->close();
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
            <h4 class="page-title">Edit ICU Monitoring</h4>
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
                <a href="#">Edit ICU Monitoring</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
                <form method="POST" action="">
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
                    <label for="" class="text-danger ms-2">All placeholders with red border are compulsory</label>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="admission_id">
                          <option value="">Select Admission</option>
                          <?php foreach ($admissions as $admission): ?>
                            <option value="<?php echo $admission['admission_id']; ?>" <?php echo (isset($icu_monitoring_data['admission_id']) && $icu_monitoring_data['admission_id'] == $admission['admission_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name'] . ' (Admission ID: ' . $admission['admission_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input type="datetime-local" style="border: 1px solid red;" placeholder="Timestamp" class="form-control" name="timestamp" value="<?php echo htmlspecialchars($icu_monitoring_data['timestamp'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Parameter Name" type="text" name="parameter_name" value="<?php echo htmlspecialchars($icu_monitoring_data['parameter_name'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Value" style="border: 1px solid red;" name="value" value="<?php echo htmlspecialchars($icu_monitoring_data['value'] ?? ''); ?>">
                      </div>
                    </div>
                  </div>
                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
                  </div>
                </form>
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