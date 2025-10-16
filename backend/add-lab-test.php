<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'lab_technician')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $test_name = $_POST['test_name'] ?? '';
  $description = $_POST['description'] ?? '';
  $test_date = $_POST['test_date'] ?? '';
  $doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['id'] : ($_POST['doctor_id'] ?? null);
  $status = $_POST['status'] ?? 'pending';
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($patient_id) || empty($test_name) || empty($test_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id, test_name, description, test_date, doctor_id, status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isssisi", $patient_id, $test_name, $description, $test_date, $doctor_id, $status, $branch_id);

      if (!$stmt->execute()) {
        throw new Exception("Failed to add lab test: " . $stmt->error);
      }
      $lab_test_id = $stmt->insert_id;
      $stmt->close();

      // Add entry to patient_bills
      // For lab tests, the price will be fetched from fee settings when generating the invoice.
      // For now, we'll set unit_price and total_amount to 0 in patient_bills.
      $stmt_bill = $conn->prepare("INSERT INTO patient_bills (patient_id, admission_id, item_type, item_id, description, quantity, unit_price, total_amount, status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $item_type = 'lab_test';
      $quantity = 1;
      $unit_price = 0.00; // Price will be determined from fee settings
      $total_amount = 0.00; // Price will be determined from fee settings
      $bill_status = $status; // Use lab test status for bill status
      $admission_id = null; // Assuming no direct admission link here, can be updated if needed

      $stmt_bill->bind_param("isisisddss", $patient_id, $admission_id, $item_type, $lab_test_id, $test_name, $quantity, $unit_price, $total_amount, $bill_status, $test_date);

      if (!$stmt_bill->execute()) {
        throw new Exception("Failed to add lab test to patient bills: " . $stmt_bill->error);
      }
      $stmt_bill->close();

      $conn->commit();
      $success_message = "Lab test added successfully and billed!";
      $_POST = array();
      header("Location: lab-tests.php?success=" . urlencode($success_message));
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
            <h4 class="page-title">Add Lab Test</h4>
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
                <a href="lab-tests.php">Lab Tests</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Lab Test</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6><hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="">

                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                            <option value="">Select Patient</option>
                            <?php foreach ($patients as $patient): ?>
                              <option value="<?php echo $patient['id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" style="border: 1px solid red;" placeholder="Test Name" type="text" name="test_name" value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Description" name="description"></textarea>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Test Date" style="border: 1px solid red;" type="datetime-local" name="test_date">
                        </div>
                      </div>
                      <?php if ($_SESSION['role'] !== 'doctor'): ?>
                        <div class="col-md-6">
                          <div class="form-group">
                            <select class="form-control form-select" name="doctor_id">
                              <option value="">Select Doctor</option>
                              <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>"><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                      <?php endif; ?>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select" name="status">
                            <option value="" selected disabled>Status</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select" name="branch_id">
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                              <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
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
