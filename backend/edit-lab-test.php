<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Fetch lab test data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $test_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM lab_tests WHERE id = ?");
  $stmt->bind_param("i", $test_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $lab_test = $result->fetch_assoc();
    $test_id = $lab_test['id'];
    $patient_id = $lab_test['patient_id'];
    $test_name = $lab_test['test_name'];
    $description = $lab_test['description'];
    $test_date = $lab_test['test_date'];
    $doctor_id = $lab_test['doctor_id'];
    $status = $lab_test['status'];
    $branch_id = $lab_test['branch_id'];
  } else {
    $error_message = "Lab Test not found.";
  }
  $stmt->close();
}

// Fetch data for dropdowns
$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

$doctors = [];
$result_doctors = $conn->query("SELECT id, staffname FROM login WHERE role = 'doctor'");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
  }
}

// Handle form submission for updating lab test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $test_id = isset($_POST['test_id']) ? $_POST['test_id'] : '';
  $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : '';
  $test_name = isset($_POST['test_name']) ? $_POST['test_name'] : '';
  $description = isset($_POST['description']) ? $_POST['description'] : '';
  $test_date = isset($_POST['test_date']) ? $_POST['test_date'] : '';
  $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
  $status = isset($_POST['status']) ? $_POST['status'] : '';
  $branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : null;

  $stmt = $conn->prepare("UPDATE lab_tests SET patient_id = ?, test_name = ?, description = ?, test_date = ?, doctor_id = ?, status = ?, branch_id = ? WHERE id = ?");
  $stmt->bind_param("isssisii", $patient_id, $test_name, $description, $test_date, $doctor_id, $status, $branch_id, $test_id);

  if ($stmt->execute()) {
    $success_message = "Lab Test updated successfully!";
    header("Location: lab-tests.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error updating lab test: " . $conn->error;
  }
  $stmt->close();
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
            <h4 class="page-title">Edit Lab Test</h4>
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
                <a href="#">Edit Lab Test</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">

              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
              <?php endif; ?>
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
              <?php endif; ?>

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6><hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($test_id); ?>">

                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border:1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?= (isset($patient_id) && $patient_id == $patient['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border:1px solid red;" placeholder="Test Name" name="test_name" value="<?= htmlspecialchars($test_name) ?>">
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Description" name="description"><?= htmlspecialchars($description) ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Test Date" style="border:1px solid red;" type="datetime-local" name="test_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($test_date))) ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id">
                          <option value="" disabled selected>Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?= (isset($doctor_id) && $doctor_id == $doctor['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="status">
                          <option value="" selected disabled>Status</option>
                          <option value="pending" <?= (isset($status) && $status == 'pending') ? 'selected' : '' ?>>Pending</option>
                          <option value="completed" <?= (isset($status) && $status == 'completed') ? 'selected' : '' ?>>Completed</option>
                          <option value="cancelled" <?= (isset($status) && $status == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?= (isset($branch_id) && $branch_id == $branch['branch_id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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