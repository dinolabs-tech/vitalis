<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or lab_technician role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'lab_technician')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_test_sample_data = [];

// Fetch test sample data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $sample_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM test_samples WHERE id = ?");
  $stmt->bind_param("i", $sample_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $edit_test_sample_data = $result->fetch_assoc();
  } else {
    $error_message = "Test Sample not found.";
  }
  $stmt->close();
} else {
  $error_message = "No Test Sample ID provided.";
}

// Handle form submission for updating test sample
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sample_id'])) {
  $sample_id = $_POST['sample_id'];
  $lab_test_id = $_POST['lab_test_id'] ?? '';
  $sample_type = $_POST['sample_type'] ?? '';
  $collection_date = $_POST['collection_date'] ?? '';
  $collected_by_staff_id = $_SESSION['id'] ?? null; // Assuming current logged-in staff collects the sample
  $status = $_POST['status'] ?? 'collected';
  $results_file_path = $_POST['results_file_path'] ?? null;
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($lab_test_id) || empty($sample_type) || empty($collection_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Update test_samples table
    $stmt = $conn->prepare("UPDATE test_samples SET lab_test_id = ?, sample_type = ?, collection_date = ?, collected_by_staff_id = ?, status = ?, results_file_path = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("isssssss", $lab_test_id, $sample_type, $collection_date, $collected_by_staff_id, $status, $results_file_path, $branch_id, $sample_id);

    if ($stmt->execute()) {
      $success_message = "Test sample updated successfully!";
      header("Location: test-samples.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to update test sample: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch lab tests for dropdown
$lab_tests = [];
$result_lab_tests = $conn->query("SELECT id, test_name, patient_id FROM lab_tests");
if ($result_lab_tests) {
  while ($row = $result_lab_tests->fetch_assoc()) {
    $lab_tests[] = $row;
  }
}

// Fetch branches for dropdown
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
            <h4 class="page-title">Edit Test Sample</h4>
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
                <a href="test-samples.php">Test Samples</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Test Sample</a>
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
                  <form method="POST" action="">
                    <input type="hidden" name="sample_id" value="<?php echo htmlspecialchars($edit_test_sample_data['id'] ?? ''); ?>">
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="lab_test_id">
                          <option value="">Select Lab Test</option>
                          <?php foreach ($lab_tests as $test): ?>
                            <option value="<?php echo $test['id']; ?>" <?php echo (isset($edit_test_sample_data['lab_test_id']) && $edit_test_sample_data['lab_test_id'] == $test['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($test['test_name'] . ' (Patient: ' . $test['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Sample Type" type="text" name="sample_type" value="<?php echo htmlspecialchars($edit_test_sample_data['sample_type'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <input class="form-control" type="datetime-local" style="border: 1px solid red;" placeholder="Collection Date" name="collection_date" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($edit_test_sample_data['collection_date'] ?? ''))); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="status">
                          <option value="collected" <?php echo (isset($edit_test_sample_data['status']) && $edit_test_sample_data['status'] == 'collected') ? 'selected' : ''; ?>>Collected</option>
                          <option value="in_progress" <?php echo (isset($edit_test_sample_data['status']) && $edit_test_sample_data['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                          <option value="analyzed" <?php echo (isset($edit_test_sample_data['status']) && $edit_test_sample_data['status'] == 'analyzed') ? 'selected' : ''; ?>>Analyzed</option>
                          <option value="rejected" <?php echo (isset($edit_test_sample_data['status']) && $edit_test_sample_data['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Test Result" name="results_file_path" rows="5"><?php echo htmlspecialchars($edit_test_sample_data['results_file_path'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($edit_test_sample_data['branch_id']) && $edit_test_sample_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="m-t-20 text-center">
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
