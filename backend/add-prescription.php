<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'pharmacist', 'superuser'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $session_doctor_id = $_SESSION['id']; // This is login.id, not doctors.id
  $medication_id = $_POST['id'] ?? '';
  $prescription_date = $_POST['prescription_date'] ?? '';
  $dosage = $_POST['dosage'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  // Fetch the actual doctor_id from the doctors table using the session staff_id
  $doctor_id_stmt = $conn->prepare("SELECT id FROM login WHERE id = ?");
  $doctor_id_stmt->bind_param("i", $session_doctor_id);
  $doctor_id_stmt->execute();
  $doctor_id_result = $doctor_id_stmt->get_result();
  $doctor_data = $doctor_id_result->fetch_assoc();
  $doctor_id = $doctor_data['id'] ?? null;
  $doctor_id_stmt->close();

  if (empty($patient_id) || empty($medication_id) || empty($prescription_date) || empty($dosage) || empty($doctor_id)) {
    $error_message = "Please fill in all required fields and ensure a valid doctor is logged in.";
  } else {
    // Fetch product details for the selected medication
    $product_details_stmt = $conn->prepare("SELECT p.id AS product_id, p.name AS product_name, p.sell_price FROM medications m JOIN products p ON m.product_id = p.id WHERE m.id = ?");
    $product_details_stmt->bind_param("i", $medication_id);
    $product_details_stmt->execute();
    $product_details_result = $product_details_stmt->get_result();
    $product_data = $product_details_result->fetch_assoc();
    $product_details_stmt->close();

    $product_id = $product_data['product_id'] ?? null;
    $product_name = $product_data['product_name'] ?? 'Unknown Medication';
    $sell_price = $product_data['sell_price'] ?? 0.00;

    // Corrected INSERT statement and bind_param to match schema for prescriptions
    $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, doctor_id, medication_id, prescription_date, dosage, notes, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssi", $patient_id, $doctor_id, $medication_id, $prescription_date, $dosage, $notes, $branch_id);

    if ($stmt->execute()) {
      // Insert into patient_bills
      $item_type = 'medication';
      $description = $product_name . ' - ' . $dosage;
      $quantity = 1; // Assuming one unit of medication per prescription for billing
      $total_amount = $sell_price * $quantity;

      $bill_stmt = $conn->prepare("INSERT INTO patient_bills (patient_id, item_type, item_id, description, quantity, unit_price, total_amount, bill_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");
      $bill_stmt->bind_param("isissdd", $patient_id, $item_type, $product_id, $description, $quantity, $sell_price, $total_amount);

      if ($bill_stmt->execute()) {
        $success_message = "Prescription and patient bill added successfully!";
      } else {
        $error_message = "Prescription added, but failed to add patient bill: " . $bill_stmt->error;
      }
      $bill_stmt->close();

      $_POST = array();
      header("Location: prescriptions.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add prescription: " . $stmt->error;
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
            <h4 class="page-title">Add Prescription</h4>
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
                <a href="#">Add Prescription</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="id">
                          <option value="" selected disabled>Select Medication</option>
                          <?php foreach ($medications as $medication): ?>
                            <option value="<?php echo $medication['id']; ?>"><?php echo htmlspecialchars($medication['name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Prescription Date" type="datetime-local" name="prescription_date">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Dosage" style="border: 1px solid red;" type="text" name="dosage">
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 text-center mt-3">
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
