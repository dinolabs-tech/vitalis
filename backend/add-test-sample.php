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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $lab_test_id = $_POST['lab_test_id'] ?? '';
  $sample_type = $_POST['sample_type'] ?? '';
  $collection_date = $_POST['collection_date'] ?? '';
  $collected_by_staff_id = $_SESSION['id'] ?? null; // Assuming current logged-in staff collects the sample
  $status = $_POST['status'] ?? 'collected';
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($lab_test_id) || empty($sample_type) || empty($collection_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Insert into test_samples table
    $stmt = $conn->prepare("INSERT INTO test_samples (lab_test_id, sample_type, collection_date, collected_by_staff_id, status, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $lab_test_id, $sample_type, $collection_date, $collected_by_staff_id, $status, $branch_id);

    if ($stmt->execute()) {
      $success_message = "Test sample added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: test-samples.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add test sample: " . $stmt->error;
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
            <h4 class="page-title">Add Test Sample</h4>
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
                <a href="#">Add Test Sample</a>
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
                <div class="col-sm-12">
                  <div class="form-group">
                    <select class="form-control form-select" style="border: 1px solid red;" name="lab_test_id">
                      <option value="" selected disabled>Select Lab Test</option>
                      <?php foreach ($lab_tests as $test): ?>
                        <option value="<?php echo $test['id']; ?>" <?php echo (isset($_POST['lab_test_id']) && $_POST['lab_test_id'] == $test['id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($test['test_name'] . ' (Patient: ' . $test['patient_id'] . ')'); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Sample Type" name="sample_type" value="<?php echo htmlspecialchars($_POST['sample_type'] ?? ''); ?>">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Collection Date" type="datetime-local" name="collection_date" value="<?php echo htmlspecialchars($_POST['collection_date'] ?? ''); ?>">
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <select class="form-control form-select" style="border: 1px solid red;" name="status">
                      <option value="" selected disabled>Status</option>
                      <option value="collected" <?php echo (isset($_POST['status']) && $_POST['status'] == 'collected') ? 'selected' : ''; ?>>Collected</option>
                      <option value="in_progress" <?php echo (isset($_POST['status']) && $_POST['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                      <option value="analyzed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'analyzed') ? 'selected' : ''; ?>>Analyzed</option>
                      <option value="rejected" <?php echo (isset($_POST['status']) && $_POST['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group">
                    <select class="form-control form-select" name="branch_id">
                      <option value="" selected disabled>Select Branch</option>
                      <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($branch['branch_name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="m-t-20 text-center">
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
