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
  $patient_id = $_POST['patient_id'] ?? '';
  $medication_id = $_POST['medication_id'] ?? '';
  $dosage = $_POST['dosage'] ?? '';
  $administration_date = $_POST['administration_date'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? '';
  $branch_id = $_SESSION['branch_id'] ?? null; // Assuming branch_id is stored in session

  // Basic validation
  if (empty($patient_id) || empty($medication_id) || empty($dosage) || empty($administration_date) || empty($doctor_id)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Insert into prescriptions table
      $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medication_id, dosage, prescription_date, doctor_id, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param(
        "iisssi",
        $patient_id,
        $medication_id,
        $dosage,
        $administration_date,
        $doctor_id,
        $branch_id
      );

      if (!$stmt->execute()) {
        throw new Exception("Error adding prescription record: " . $stmt->error);
      }
      $stmt->close();

      $conn->commit();
      $success_message = "Prescription record added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: drug_administration.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to add prescription record: " . $e->getMessage();
    }
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT id, patient_id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch medications for dropdown
$medications = [];
$result_medications = $conn->query("SELECT m.id AS medication_id, p.name AS product_name FROM medications m JOIN products p ON m.product_id = p.id");
if ($result_medications) {
  while ($row = $result_medications->fetch_assoc()) {
    $medications[] = $row;
  }
}

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role = 'doctor'");
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
            <h4 class="page-title">Add Drug Administration</h4>
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
                <a href="drug_administration.php">Drug Administrations</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Drug Administration</a>
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
                  <form method="POST" action="" class="row g-3">
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control  form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="medication_id">
                          <option value="" selected disabled>Select Medication</option>
                          <?php foreach ($medications as $medication): ?>
                            <option value="<?php echo $medication['medication_id']; ?>" <?php echo (isset($_POST['medication_id']) && $_POST['medication_id'] == $medication['medication_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($medication['product_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Dosage" style="border: 1px solid red;" name="dosage" value="<?php echo htmlspecialchars($_POST['dosage'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input type="datetime-local" class="form-control" placeholder="Admnistration Date" style="border: 1px solid red;" name="administration_date" value="<?php echo htmlspecialchars($_POST['administration_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="doctor_id">
                          <option value="" selected disabled>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($_POST['id']) && $_POST['id'] == $doctor['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-2">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
                    </div>
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