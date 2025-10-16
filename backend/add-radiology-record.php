<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'radiologist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['id'] : ($_POST['doctor_id'] ?? null);
  $test_name = $_POST['test_name'] ?? '';
  $test_date = $_POST['test_date'] ?? '';
  $description = $_POST['description'] ?? '';
  $radiology_image_path = $_POST['radiology_image_path'] ?? null;
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($test_name) || empty($test_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("INSERT INTO radiology_records (patient_id, doctor_id, test_name, test_date, description, radiology_image_path, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("iissssi", $patient_id, $doctor_id, $test_name, $test_date, $description, $radiology_image_path, $branch_id);

      if (!$stmt->execute()) {
        throw new Exception("Failed to add radiology record: " . $stmt->error);
      }
      $radiology_record_id = $stmt->insert_id;
      $stmt->close();

      // Add entry to patient_bills
      // For radiology records, the price will be fetched from fee settings when generating the invoice.
      // For now, we'll set unit_price and total_amount to 0 in patient_bills.
      $stmt_bill = $conn->prepare("INSERT INTO patient_bills (patient_id, admission_id, item_type, item_id, description, quantity, unit_price, total_amount, status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $item_type = 'radiology_record';
      $quantity = 1;
      $unit_price = 0.00; // Price will be determined from fee settings
      $total_amount = 0.00; // Price will be determined from fee settings
      $bill_status = 'completed'; // Assuming radiology record is completed when added
      $admission_id = null; // Assuming no direct admission link here, can be updated if needed

      $stmt_bill->bind_param("isisisddss", $patient_id, $admission_id, $item_type, $radiology_record_id, $test_name, $quantity, $unit_price, $total_amount, $bill_status, $test_date);

      if (!$stmt_bill->execute()) {
        throw new Exception("Failed to add radiology record to patient bills: " . $stmt_bill->error);
      }
      $stmt_bill->close();

      $conn->commit();
      $success_message = "Radiology record added successfully and billed!";
      $_POST = array();
      header("Location: radiology-records.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = $e->getMessage();
    }
  }
}

$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

$doctors = [];
if ($_SESSION['role'] !== 'doctor') {
  $result_doctors = $conn->query("SELECT id, staffname FROM login WHERE role = 'doctor'");
  if ($result_doctors) {
    while ($row = $result_doctors->fetch_assoc()) {
      $doctors[] = $row;
    }
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
            <h4 class="page-title">Add Radiology Record</h4>
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
                <a href="radiology-records.php">Radiology Records</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Radiology Record</a>
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
                  <form method="POST" action="" class="row g-3">

                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Test Name" type="text" name="test_name" value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>">
                      </div>
                    </div>
                    <?php if ($_SESSION['role'] !== 'doctor'): ?>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select" name="doctor_id">
                            <option value="" selected disabled>Select Doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                              <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                    <?php endif; ?>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Test Date" type="datetime-local" name="test_date">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Description" name="description"></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Radiology Image Path" type="text" name="radiology_image_path">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
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
