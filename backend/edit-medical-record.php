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
$medical_record_data = [];
$record_id = $_GET['id'] ?? null;

if ($record_id) {
  // Fetch existing medical record data
  $stmt = $conn->prepare("SELECT * FROM medical_records WHERE id = ?"); // Assuming 'id' is the primary key
  $stmt->bind_param("i", $record_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $medical_record_data = $result->fetch_assoc();
  } else {
    $error_message = "Medical record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No medical record ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record_id) {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? null;
  $appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
  $record_date = $_POST['record_date'] ?? '';
  $diagnosis = $_POST['diagnosis'] ?? '';
  $treatment = $_POST['treatment'] ?? '';
  $notes = $_POST['notes'] ?? null;

  // Basic validation
  if (empty($patient_id) || empty($doctor_id) || empty($record_date) || empty($diagnosis) || empty($treatment)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Update medical_records table
      $stmt = $conn->prepare("UPDATE medical_records SET patient_id = ?, doctor_id = ?, appointment_id = ?, record_date = ?, diagnosis = ?, treatment = ?, notes = ? WHERE id = ?");
      $stmt->bind_param(
        "siissssi", // s for patient_id, i for doctor_id, i for appointment_id, s for record_date, s for diagnosis, s for treatment, s for notes, i for record_id
        $patient_id,
        $doctor_id,
        $appointment_id,
        $record_date,
        $diagnosis,
        $treatment,
        $notes,
        $record_id
      );

      if (!$stmt->execute()) {
        throw new Exception("Error updating medical record: " . $stmt->error);
      }
      $stmt->close();

      $conn->commit();
      $success_message = "Medical record updated successfully!";
      // Refresh data after update
      header("Location: medical_record_management.php?success=" . urlencode($success_message));
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update medical record: " . $e->getMessage();
    }
  }
}

// If redirected with success message
if (isset($_GET['success']) && $_GET['success'] === 'true') {
  $success_message = "Medical record updated successfully!";
  // After successful update, re-fetch the data to display the latest changes
  $stmt = $conn->prepare("SELECT * FROM medical_records WHERE id = ?");
  $stmt->bind_param("i", $record_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $medical_record_data = $result->fetch_assoc();
  }
  $stmt->close();
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
            <h4 class="page-title">Edit Medical Record</h4>
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
                <a href="medical_record_management.php">Medical Record Management</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Medical Record</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-12">
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
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($medical_record_data['patient_id']) && $medical_record_data['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="doctor_id">
                          <option value="">Select Doctor</option>
                          <?php
                          $doctors = [];
                          $result_doctors = $conn->query("SELECT id, staffname FROM login WHERE role = 'doctor'");
                          if ($result_doctors) {
                            while ($row = $result_doctors->fetch_assoc()) {
                              $doctors[] = $row;
                            }
                          }
                          foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($medical_record_data['doctor_id']) && $medical_record_data['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Appointment ID" type="number" name="appointment_id" value="<?php echo htmlspecialchars($medical_record_data['appointment_id'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="datetime-local" style="border: 1px solid red;" placeholder="Record Date" class="form-control" name="record_date" value="<?php echo htmlspecialchars($medical_record_data['record_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Diagnosis" name="diagnosis"><?php echo htmlspecialchars($medical_record_data['diagnosis'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Treatment" style="border: 1px solid red;" name="treatment"><?php echo htmlspecialchars($medical_record_data['treatment'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($medical_record_data['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
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