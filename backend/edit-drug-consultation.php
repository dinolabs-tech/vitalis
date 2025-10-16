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
$consultation_data = [];
$consultation_id = $_GET['id'] ?? null;

if ($consultation_id) {
  // Fetch existing drug consultation data
  $stmt = $conn->prepare("SELECT * FROM drug_consultations WHERE id = ?"); // Assuming 'id' is the primary key
  $stmt->bind_param("i", $consultation_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $consultation_data = $result->fetch_assoc();
  } else {
    $error_message = "Drug consultation record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No drug consultation ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $consultation_id) {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? '';
  $consultation_date = $_POST['consultation_date'] ?? '';
  $notes = $_POST['notes'] ?? '';

  // Basic validation
  if (empty($patient_id) || empty($doctor_id) || empty($consultation_date) || empty($notes)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Update drug_consultations table
      $stmt = $conn->prepare("UPDATE drug_consultations SET patient_id = ?, doctor_id = ?, consultation_date = ?, consultation_notes = ? WHERE id = ?");
      $stmt->bind_param(
        "ssssi",
        $patient_id,
        $doctor_id,
        $consultation_date,
        $notes,
        $consultation_id
      );

      if (!$stmt->execute()) {
        throw new Exception("Error updating drug consultation record: " . $stmt->error);
      }
      $stmt->close();

      $conn->commit();
      $success_message = "Drug consultation record updated successfully!";
      // Refresh data after update
      header("Location: edit-drug-consultation.php?id=" . $consultation_id . "&success=true");
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update drug consultation record: " . $e->getMessage();
    }
  }
}

// If redirected with success message
if (isset($_GET['success']) && $_GET['success'] === 'true') {
  $success_message = "Drug consultation record updated successfully!";
  header("Location: drug_consultation.php");
  exit();
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch doctors for dropdown
$doctors = [];
$sql_doctors = "SELECT * FROM login";
$result_doctors = $conn->query($sql_doctors);
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
            <h4 class="page-title">Edit Drug Consultation</h4>
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
                <a href="drug_consultation.php">Drug Consultations</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Drug Consultation</a>
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
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($consultation_data['patient_id']) && $consultation_data['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="doctor_id">
                          <option value="">Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($consultation_data['doctor_id']) && $consultation_data['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input type="datetime-local" style="border: 1px solid red;" placeholder="Consultation Date" class="form-control" name="consultation_date" value="<?php echo htmlspecialchars($consultation_data['consultation_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" style="border: 1px solid red;" name="notes"><?php echo htmlspecialchars($consultation_data['consultation_notes'] ?? ''); ?></textarea>
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