<?php
session_start();
include_once('database/db_connect.php');
include_once('includes/config.php'); // Ensure $conn is defined here

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$bill_id_param = $_GET['id'] ?? '';
$patient_bill_record = null;

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients");
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

if (!empty($bill_id_param)) {
  // Fetch patient bill details
  $stmt = $conn->prepare("SELECT * FROM patient_bills WHERE id = ?");
  $stmt->bind_param("i", $bill_id_param);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $patient_bill_record = $result->fetch_assoc();
  } else {
    $error_message = "Patient bill record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No patient bill ID provided.";
}

/* ----------------------
   Handle patient bill update
-------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bill']) && $patient_bill_record) {
  $patient_id = $_POST['patient_id'] ?? '';
  $admission_id = $_POST['admission_id'] ?? null;
  $item_type = $_POST['item_type'] ?? '';
  $description = $_POST['description'] ?? '';
  $quantity = $_POST['quantity'] ?? 1;
  $unit_price = $_POST['unit_price'] ?? 0.00;

  // Basic validation
  if (empty($patient_id) || empty($item_type) || empty($description) || empty($quantity) || empty($unit_price)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $total_amount = $quantity * $unit_price;

    $query_bill = "UPDATE patient_bills SET patient_id=?, admission_id=?, item_type=?, description=?, quantity=?, unit_price=?, total_amount=? WHERE id=?";
    $stmt_bill = $conn->prepare($query_bill);
    $stmt_bill->bind_param('sissddsi', $patient_id, $admission_id, $item_type, $description, $quantity, $unit_price, $total_amount, $bill_id_param);

    if ($stmt_bill->execute()) {
      $success_message = "Patient bill updated successfully!";
      header("Location: patient-bills.php");
      exit;
    } else {
      $error_message = "Error updating patient bill: " . $stmt_bill->error;
    }
    $stmt_bill->close();
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
            <h4 class="page-title">Edit Patient Bill</h4>
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
                <a href="#">Edit Patient Bill</a>
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
            </div>

            <div class="card">
              <div class="card-title">
                <h6 class="text-danger m-4">All placeholders with red border are compulsory</h6>
                <hr>
              </div>
              <div class="card-body">
                <?php if ($patient_bill_record): ?>
                  <form method="post">
                    <input type="hidden" name="update_bill" value="1">
                    <div class="row">
                      <div class="col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select searchable-dropdown mt-5" style="border:1px solid red;" name="patient_id">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                              <option value="<?php echo $patient['patient_id']; ?>" <?php echo ($patient_bill_record['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['patient_id']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select searchable-dropdown mt-5" name="admission_id">
                            <option value="">Select Admission</option>
                            <?php foreach ($admissions as $admission): ?>
                              <option value="<?php echo $admission['id']; ?>" <?php echo ($patient_bill_record['admission_id'] == $admission['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars('Admission ID: ' . $admission['id'] . ' (Patient: ' . $admission['patient_id'] . ')'); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-sm-4">
                        <div class="form-group">
                          <select class="form-control form-select" style="border:1px solid red;" name="item_type">
                            <option value="">Select Item Type</option>
                            <option value="room_charge" <?php echo ($patient_bill_record['item_type'] == 'room_charge') ? 'selected' : ''; ?>>Room Charge</option>
                            <option value="medication" <?php echo ($patient_bill_record['item_type'] == 'medication') ? 'selected' : ''; ?>>Medication</option>
                            <option value="service" <?php echo ($patient_bill_record['item_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                            <option value="lab_test" <?php echo ($patient_bill_record['item_type'] == 'lab_test') ? 'selected' : ''; ?>>Lab Test</option>
                            <option value="other" <?php echo ($patient_bill_record['item_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-sm-4">
                        <div class="form-group">
                          <input class="form-control" style="border:1px solid red;" placeholder="Quantity" type="number" name="quantity" value="<?php echo htmlspecialchars($patient_bill_record['quantity'] ?? ''); ?>" min="1">
                        </div>
                      </div>
                      <div class="col-sm-4">
                        <div class="form-group">
                          <input class="form-control" style="border:1px solid red;" placeholder="Unit Price" type="text" name="unit_price" value="<?php echo htmlspecialchars($patient_bill_record['unit_price'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-12">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Description" style="border:1px solid red;" name="description"><?php echo htmlspecialchars($patient_bill_record['description'] ?? ''); ?></textarea>
                        </div>
                      </div>

                    </div>
                    <div class="m-t-20 text-center">
                      <button type="submit" name="update_bill" class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  </form>
                <?php endif; ?>
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