<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'pharmacist', 'superuser'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_prescription_data = [];

// Fetch existing prescription data if ID is provided in GET
if (isset($_GET['id'])) {
  $prescription_id_to_edit = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
  $stmt->bind_param("i", $prescription_id_to_edit);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_prescription_data = $result->fetch_assoc();
  } else {
    $error_message = "Prescription not found for editing.";
  }
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $prescription_id = $_POST['prescription_id'] ?? null; // Hidden field for prescription ID
  $patient_id = $_POST['patient_id'] ?? '';
  $session_doctor_id = $_SESSION['id']; // This is login.id, not doctors.id
  $medication_id = $_POST['id'] ?? '';
  $prescription_date = $_POST['prescription_date'] ?? '';
  $dosage = $_POST['dosage'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  // Fetch the actual doctor_id from the doctors table using the session staff_id
  $doctor_id_stmt = $conn->prepare("SELECT id FROM doctors WHERE staff_id = ?");
  $doctor_id_stmt->bind_param("i", $session_doctor_id);
  $doctor_id_stmt->execute();
  $doctor_id_result = $doctor_id_stmt->get_result();
  $doctor_data = $doctor_id_result->fetch_assoc();
  $doctor_id = $doctor_data['id'] ?? null;
  $doctor_id_stmt->close();

  if (empty($prescription_id) || empty($patient_id) || empty($medication_id) || empty($prescription_date) || empty($dosage)) {
    $error_message = "Please fill in all required fields and ensure a valid doctor is logged in.";
  } else {
    // Update existing prescription
    $stmt = $conn->prepare("UPDATE prescriptions SET patient_id = ?, medication_id = ?, dosage = ?, prescription_date = ?, notes = ?, doctor_id = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("iissssii", $patient_id, $medication_id, $dosage, $prescription_date, $notes, $doctor_id, $branch_id, $prescription_id);

    if ($stmt->execute()) {
      $success_message = "Prescription updated successfully!";
      header("Location: prescriptions.php");
    } else {
      $error_message = "Failed to update prescription: " . $stmt->error;
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

$medications = [];
$result_medications = $conn->query("SELECT m.*, p.name FROM medications m
INNER JOIN products p ON m.product_id = p.id");
if ($result_medications) {
  while ($row = $result_medications->fetch_assoc()) {
    $medications[] = $row;
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
            <h4 class="page-title">Edit Prescription</h4>
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
                <a href="prescriptions.php">Prescriptions</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Prescription</a>
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
                  <h6 class="text-danger m-4 small"> All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($edit_prescription_data['id'] ?? ''); ?>">
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo (isset($edit_prescription_data['patient_id']) && $edit_prescription_data['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="id">
                          <option value="" selected disabled>Select Medication</option>
                          <?php foreach ($medications as $medication): ?>
                            <option value="<?php echo $medication['id']; ?>" <?php echo (isset($edit_prescription_data['medication_id']) && $edit_prescription_data['medication_id'] == $medication['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($medication['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Prescription Date" type="datetime-local" name="prescription_date" value="<?php echo htmlspecialchars($edit_prescription_data['prescription_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Dosage" style="border: 1px solid red;" name="dosage" value="<?php echo htmlspecialchars($edit_prescription_data['dosage'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($edit_prescription_data['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($edit_prescription_data['branch_id']) && $edit_prescription_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-3">
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