<?php
session_start();
include_once('database/db_connect.php');

// Check permissions
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Get the record ID
$id = $_GET['id'] ?? '';
if (empty($id)) {
  header("Location: drug_administration.php");
  exit;
}

// Fetch existing record
$stmt = $conn->prepare("
  SELECT p.*, pat.first_name, pat.last_name, m.id AS medication_id, prod.name AS product_name, l.staffname
  FROM prescriptions p
  JOIN patients pat ON p.patient_id = pat.id
  JOIN medications m ON p.medication_id = m.id
  JOIN products prod ON m.product_id = prod.id
  JOIN login l ON p.doctor_id = l.id
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

// if (!$admin) {
//   header("Location: drug_administration.php");
//   exit;
// }

// Fetch dropdowns
$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients");
while ($row = $result_patients->fetch_assoc()) $patients[] = $row;

$medications = [];
$result_medications = $conn->query("SELECT m.id AS medication_id, p.name AS product_name FROM medications m JOIN products p ON m.product_id = p.id");
while ($row = $result_medications->fetch_assoc()) $medications[] = $row;

$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role = 'doctor'");
while ($row = $result_doctors->fetch_assoc()) $doctors[] = $row;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $medication_id = $_POST['medication_id'] ?? '';
  $dosage = $_POST['dosage'] ?? '';
  $administration_date = $_POST['administration_date'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? '';

  if (empty($patient_id) || empty($medication_id) || empty($dosage) || empty($administration_date) || empty($doctor_id)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE prescriptions SET patient_id=?, medication_id=?, dosage=?, prescription_date=?, doctor_id=? WHERE id=?");
    $stmt->bind_param("iissii", $patient_id, $medication_id, $dosage, $administration_date, $doctor_id, $id);
    if ($stmt->execute()) {
      $success_message = "Prescription record updated successfully!";
      header("Location: drug_administration.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to update prescription record: " . $stmt->error;
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
            <h4 class="page-title">Edit Drug Administration</h4>
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
                <a href="drug_administration.php">Drug Administration</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Drug Administration</a>
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
                  <div class="row">
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="patient_id" required>
                          <option value="" disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo ($admin['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="medication_id" required>
                          <option value="" disabled>Select Medication</option>
                          <?php foreach ($medications as $medication): ?>
                            <option value="<?php echo $medication['medication_id']; ?>" <?php echo ($admin['medication_id'] == $medication['medication_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($medication['product_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Dosage" name="dosage" value="<?php echo htmlspecialchars($admin['dosage']); ?>" required>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input type="datetime-local" class="form-control" name="administration_date" value="<?php echo date('Y-m-d\TH:i', strtotime($admin['prescription_date'])); ?>" required>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id" required>
                          <option value="" disabled>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo ($admin['id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-2">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
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