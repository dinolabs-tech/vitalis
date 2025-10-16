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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
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
    $error_message = "Please select a patient.";
  } else {
    // Insert into patient_vitals table
    $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, recorded_by_staff_id, temperature, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, respiration_rate, weight_kg, height_cm, blood_oxygen_saturation, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidiiiiidds", $patient_id, $recorded_by_staff_id, $temperature, $blood_pressure_systolic, $blood_pressure_diastolic, $heart_rate, $respiration_rate, $weight_kg, $height_cm, $blood_oxygen_saturation, $notes);

    if ($stmt->execute()) {
      $success_message = "Patient vitals recorded successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: patient-vitals.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to record patient vitals: " . $stmt->error;
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
            <h4 class="page-title">Add Patient Vital</h4>
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
                <a href="#">Add Patient Vital</a>
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

                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Temperature (°C)" type="text" name="temperature" value="<?php echo htmlspecialchars($_POST['temperature'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Blood Pressure (Systolic)" type="text" name="blood_pressure_systolic" value="<?php echo htmlspecialchars($_POST['blood_pressure_systolic'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Blood Pressure (Diastolic)" type="text" name="blood_pressure_diastolic" value="<?php echo htmlspecialchars($_POST['blood_pressure_diastolic'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Heart Rate (bpm)" type="text" name="heart_rate" value="<?php echo htmlspecialchars($_POST['heart_rate'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Respiration Rate (breaths/min)" type="text" name="respiration_rate" value="<?php echo htmlspecialchars($_POST['respiration_rate'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Weight (kg)" type="text" name="weight_kg" value="<?php echo htmlspecialchars($_POST['weight_kg'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Height (cm)" type="text" name="height_cm" value="<?php echo htmlspecialchars($_POST['height_cm'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Blood Oxygen Saturation (%)" type="text" name="blood_oxygen_saturation" value="<?php echo htmlspecialchars($_POST['blood_oxygen_saturation'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-12 text-center">
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