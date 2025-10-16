<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or nurse role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'nurse')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if (isset($_POST['submit'])) {
  $vital_id = $_POST['vital_id'] ?? ''; // Get vital_id from hidden input
  $patient_id = $_POST['patient_id'] ?? ''; // Should be hidden or read-only
  $recorded_by_staff_id = $_SESSION['id'] ?? null; // Assuming current logged-in staff records vitals
  $temperature = $_POST['temperature'] ?? null;
  $blood_pressure_systolic = $_POST['blood_pressure_systolic'] ?? null;
  $blood_pressure_diastolic = $_POST['blood_pressure_diastolic'] ?? null;
  $heart_rate = $_POST['heart_rate'] ?? null;
  $respiration_rate = $_POST['respiration_rate'] ?? null;
  $weight_kg = $_POST['weight_kg'] ?? null;
  $height_cm = $_POST['height_cm'] ?? null;
  $blood_oxygen_saturation = $_POST['blood_oxygen_saturation'] ?? null;
  $notes = $_POST['notes'] ?? '';

  // Basic validation
  if (empty($patient_id)) {
    $error_message = "Patient ID is missing.";
  } else {
    // Update patient_vitals table
    $stmt = $conn->prepare("UPDATE patient_vitals SET patient_id=?, recorded_by_staff_id=?, temperature=?, blood_pressure_systolic=?, blood_pressure_diastolic=?, heart_rate=?, respiration_rate=?, weight_kg=?, height_cm=?, blood_oxygen_saturation=?, notes=? WHERE id=?");
    $stmt->bind_param("sidiiiiiddsi", $patient_id, $recorded_by_staff_id, $temperature, $blood_pressure_systolic, $blood_pressure_diastolic, $heart_rate, $respiration_rate, $weight_kg, $height_cm, $blood_oxygen_saturation, $notes, $vital_id);

    if ($stmt->execute()) {
      $success_message = "Patient vitals updated successfully!";
      header("Location: patient-vitals.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to update patient vitals: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch existing vital data for editing
$vital_data = null;
if (isset($_GET['editid'])) {
  $vital_id = $_GET['editid'];
  $stmt = $conn->prepare("SELECT pv.*, p.first_name, p.last_name FROM patient_vitals pv JOIN patients p ON pv.patient_id = p.patient_id WHERE pv.id = ?");
  $stmt->bind_param("i", $vital_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $vital_data = $result->fetch_assoc();
  } else {
    $error_message = "Vital record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No vital ID provided for editing.";
}

// Fetch patients for dropdown (if patient_id needs to be selectable, though for edit it's usually fixed)
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
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
            <h4 class="page-title">Edit Patient Vitals</h4>
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
                <a href="patient-vitals.php">Patient Vitals</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Patient Vitals</a>
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
                    <?php if ($vital_data): ?>

                      <div class="col-md-6">
                        <div class="form-group">
                          <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($vital_data['patient_id']); ?>">
                          <input type="hidden" name="vital_id" value="<?php echo htmlspecialchars($vital_data['id']); ?>">
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($vital_data['first_name'] . ' ' . $vital_data['last_name'] . ' (' . $vital_data['patient_id'] . ')'); ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Temperature (°C)" type="text" name="temperature" value="<?php echo htmlspecialchars($vital_data['temperature'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Blood Pressure (Systolic)" type="text" name="blood_pressure_systolic" value="<?php echo htmlspecialchars($vital_data['blood_pressure_systolic'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Blood Pressure (Diastolic" type="text" name="blood_pressure_diastolic" value="<?php echo htmlspecialchars($vital_data['blood_pressure_diastolic'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Heart Rate (bpm)" type="text" name="heart_rate" value="<?php echo htmlspecialchars($vital_data['heart_rate'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Respiration Rate (breaths/min)" type="text" name="respiration_rate" value="<?php echo htmlspecialchars($vital_data['respiration_rate'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Weight (kg)" type="text" name="weight_kg" value="<?php echo htmlspecialchars($vital_data['weight_kg'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Height (cm)" type="text" name="height_cm" value="<?php echo htmlspecialchars($vital_data['height_cm'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Blood Oxygen Saturation (%)" type="text" name="blood_oxygen_saturation" value="<?php echo htmlspecialchars($vital_data['blood_oxygen_saturation'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($vital_data['notes'] ?? ''); ?></textarea>
                        </div>
                      </div>
                      <div class="col-md-12 text-center">
                        <button class="btn btn-primary submit-btn btn-icon btn-round" name="submit"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p class="text-center">No vital record found for editing or an error occurred.</p>
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