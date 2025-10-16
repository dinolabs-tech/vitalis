<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or receptionist role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $admission_id = $_POST['admission_id'] ?? null;
  $item_type = $_POST['item_type'] ?? '';
  $item_id = $_POST['item_id'] ?? null; // This will depend on item_type
  $description = $_POST['description'] ?? '';
  $quantity = $_POST['quantity'] ?? 1;
  $unit_price = $_POST['unit_price'] ?? 0.00;

  // Basic validation
  if (empty($patient_id) || empty($item_type) || empty($description) || empty($quantity) || empty($unit_price)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $total_amount = $quantity * $unit_price;

    // Insert into patient_bills table
    $stmt = $conn->prepare("INSERT INTO patient_bills (patient_id, admission_id, item_type, item_id, description, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisissdd", $patient_id, $admission_id, $item_type, $item_id, $description, $quantity, $unit_price, $total_amount);

    if ($stmt->execute()) {
      $success_message = "Patient bill added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: patient-bills.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add patient bill: " . $stmt->error;
    }
    $stmt->close();
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

// Fetch admissions for dropdown
$admissions = [];
$result_admissions = $conn->query("SELECT id, patient_id, admission_date FROM admissions WHERE status = 'admitted'");
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
            <h4 class="page-title">Add Patient Bill</h4>
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
                <a href="patient-bills.php">Patient Bills</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Patient Bill</a>
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
                  <h6 class="text-danger m-4">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>

                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" name="admission_id">
                          <option value="" selected disabled>Select Admission</option>
                          <?php foreach ($admissions as $admission): ?>
                            <option value="<?php echo $admission['id']; ?>" <?php echo (isset($_POST['admission_id']) && $_POST['admission_id'] == $admission['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars('Admission ID: ' . $admission['id'] . ' (Patient: ' . $admission['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="item_type">
                          <option value="" selected disabled>Select Item Type</option>
                          <option value="room_charge" <?php echo (isset($_POST['item_type']) && $_POST['item_type'] == 'room_charge') ? 'selected' : ''; ?>>Room Charge</option>
                          <option value="medication" <?php echo (isset($_POST['item_type']) && $_POST['item_type'] == 'medication') ? 'selected' : ''; ?>>Medication</option>
                          <option value="service" <?php echo (isset($_POST['item_type']) && $_POST['item_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                          <option value="lab_test" <?php echo (isset($_POST['item_type']) && $_POST['item_type'] == 'lab_test') ? 'selected' : ''; ?>>Lab Test</option>
                          <option value="other" <?php echo (isset($_POST['item_type']) && $_POST['item_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Quantity" type="number" name="quantity" value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" min="1">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Unit Price" style="border: 1px solid red;" type="text" name="unit_price" value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>">
                      </div>
                    </div>

                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
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