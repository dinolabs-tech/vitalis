<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Fetch radiology record data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $radiology_record_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM radiology_records WHERE id = ?");
  $stmt->bind_param("i", $radiology_record_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $radiology_record = $result->fetch_assoc();
    $radiology_record_id = $radiology_record['id'];
    $patient_id = $radiology_record['patient_id'];
    $doctor_id = $radiology_record['doctor_id'];
    $test_type = $radiology_record['test_name'];
    $request_date = $radiology_record['test_date'];
    $image_path = $radiology_record['radiology_image_path'];
    $diagnosis = $radiology_record['description'];
    $branch_id = $radiology_record['branch_id'];
  } else {
    $error_message = "Radiology Record not found.";
  }
  $stmt->close();
}

// Fetch data for dropdowns
$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients"); // Corrected
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

$doctors = [];
$result_doctors = $conn->query("SELECT d.staff_id AS id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id"); // Corrected
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

// Handle form submission for updating radiology record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $radiology_record_id = isset($_POST['radiology_record_id']) ? $_POST['radiology_record_id'] : '';
  $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : '';
  $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : null;
  $test_type = isset($_POST['test_type']) ? $_POST['test_type'] : '';
  $request_date = isset($_POST['request_date']) ? $_POST['request_date'] : '';
  $result_date = isset($_POST['result_date']) ? $_POST['result_date'] : null;
  $image_path = isset($_POST['image_path']) ? $_POST['image_path'] : null;
  $diagnosis = isset($_POST['diagnosis']) ? $_POST['diagnosis'] : '';
  $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
  $branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : null;

  $stmt = $conn->prepare("UPDATE radiology_records SET patient_id = ?, doctor_id = ?, test_name = ?, test_date = ?, radiology_image_path = ?, description = ?, branch_id = ? WHERE id = ?");
  $stmt->bind_param("sissssii", $patient_id, $doctor_id, $test_type, $request_date, $image_path, $diagnosis, $branch_id, $radiology_record_id);

  if ($stmt->execute()) {
    $success_message = "Radiology Record updated successfully!";
    header("Location: radiology-records.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error updating radiology record: " . $conn->error;
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
            <h4 class="page-title">Edit Radiology Record</h4>
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
                <a href="#">Edit Radiology Record</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <input type="hidden" name="radiology_record_id" value="<?php echo htmlspecialchars($radiology_record_id); ?>">

                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['id']; ?>" <?= (isset($patient_id) && $patient_id == $patient['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="doctor_id">
                          <option value="">Select Doctor</option>
                          <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?= (isset($doctor_id) && $doctor_id == $doctor['id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($doctor['staffname']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Test Type" type="text" name="test_type" value="<?= htmlspecialchars($test_type) ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Request Date" style="border: 1px solid red;" type="datetime-local" name="request_date" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($request_date))) ?>">
                      </div>
                    </div>

                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Image Path" type="text" name="image_path" value="<?= htmlspecialchars($image_path ?? '') ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Diagnosis" name="diagnosis"><?= htmlspecialchars($diagnosis ?? '') ?></textarea>
                      </div>
                    </div>

                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?= (isset($branch_id) && $branch_id == $branch['branch_id']) ? 'selected' : '' ?>><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-3">
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