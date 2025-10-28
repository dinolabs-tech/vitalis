<?php
session_start();
include_once('database/db_connect.php');

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
  }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'lab_technician' && $_SESSION['role'] !== 'nurse') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Test Sample
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $lab_test_id = $_POST['lab_test_id'] ?? null;
  $sample_type = $_POST['sample_type'] ?? '';
  $collection_date = $_POST['collection_date'] ?? date('Y-m-d H:i:s');
  $collected_by_staff_id = $_POST['collected_by_staff_id'] ?? null;
  $status = $_POST['status'] ?? 'collected';
  $results_file_path = $_POST['results_file_path'] ?? null;
  $branch_id = $_POST['branch_id'] ?? null;
  $test_sample_id = $_POST['test_sample_id'] ?? null; // For editing

  if (empty($lab_test_id) || empty($sample_type) || empty($collection_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($test_sample_id) {
      // Update existing test sample
      $stmt = $conn->prepare("UPDATE test_samples SET lab_test_id = ?, sample_type = ?, collection_date = ?, collected_by_staff_id = ?, status = ?, results_file_path = ?, branch_id = ? WHERE id = ?");
      $stmt->bind_param("isssisii", $lab_test_id, $sample_type, $collection_date, $collected_by_staff_id, $status, $results_file_path, $branch_id, $test_sample_id);
      if ($stmt->execute()) {
        $success_message = "Test Sample updated successfully!";
      } else {
        $error_message = "Error updating test sample: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new test sample
      $stmt = $conn->prepare("INSERT INTO test_samples (lab_test_id, sample_type, collection_date, collected_by_staff_id, status, results_file_path, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isssisi", $lab_test_id, $sample_type, $collection_date, $collected_by_staff_id, $status, $results_file_path, $branch_id);
      if ($stmt->execute()) {
        $success_message = "Test Sample added successfully!";
      } else {
        $error_message = "Error adding test sample: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}


// Handle Delete Test Sample
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_test_sample = $conn->prepare("DELETE FROM test_samples WHERE id = ?");
    $stmt_test_sample->bind_param("i", $delete_id);

    if (!$stmt_test_sample->execute()) {
      throw new Exception("Error deleting test sample record: " . $stmt_test_sample->error);
    }

    $stmt_test_sample->close();
    $conn->commit();

    $success_message = "Test Sample deleted successfully!";
    header("Location: test-samples.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Test Sample: " . $e->getMessage();
    header("Location: test-samples.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all test samples
$test_samples = [];
$sql = "SELECT ts.*, lt.test_name, p.first_name, p.last_name, s.staffname as collected_by_staff_name, b.branch_name
        FROM test_samples ts
        LEFT JOIN lab_tests lt ON ts.lab_test_id = lt.id
        LEFT JOIN patients p ON lt.patient_id = p.id
        LEFT JOIN login s ON ts.collected_by_staff_id = s.id
        LEFT JOIN branches b ON ts.branch_id = b.branch_id";

$conditions = [];
$params = [];
$types = "";

if (isset($_GET['branch_id']) && $_GET['branch_id'] !== '') {
    $conditions[] = "ts.branch_id = ?";
    $params[] = $_GET['branch_id'];
    $types .= "i";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY ts.collection_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $test_samples[] = $row;
        }
    }
    $stmt->close();
} else {
    $error_message = "Failed to prepare statement: " . $conn->error;
}

// Fetch test sample data for editing if ID is provided in GET
$edit_test_sample_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_test_sample_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM test_samples WHERE id = ?");
  $stmt->bind_param("i", $edit_test_sample_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_test_sample_data = $result->fetch_assoc();
  } else {
    $error_message = "Test Sample not found for editing.";
  }
  $stmt->close();
}

// Fetch lab tests for dropdown
$lab_tests = [];
$sql_lab_tests = "SELECT lt.id, lt.test_name, p.first_name, p.last_name 
                  FROM lab_tests lt 
                  LEFT JOIN patients p ON lt.patient_id = p.id
                  ORDER BY lt.test_name ASC";
$result_lab_tests = $conn->query($sql_lab_tests);
if ($result_lab_tests) {
  while ($row = $result_lab_tests->fetch_assoc()) {
    $lab_tests[] = $row;
  }
}

// Fetch staff for dropdown (lab technicians, nurses, admins)
$staff = [];
$result_staff = $conn->query("SELECT id, staffname FROM login WHERE role IN ('admin', 'lab_technician', 'nurse') ORDER BY staffname ASC");
if ($result_staff) {
  while ($row = $result_staff->fetch_assoc()) {
    $staff[] = $row;
  }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
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
            <h3 class="fw-bold mb-3">Test Samples</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Test Samples</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-test-sample.php" class="btn btn-primary btn-round">Add Test Sample</a>
              </div>
            <?php endif; ?>
          </div>

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


          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped custom-table" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Lab Test</th>
                          <th>Patient Name</th>
                          <th>Sample Type</th>
                          <th>Collection Date</th>
                          <th>Collected By</th>
                          <th>Status</th>
                          <th>Branch</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($test_samples) > 0): ?>
                          <?php foreach ($test_samples as $sample): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($sample['test_name']); ?></td>
                              <td><?php echo htmlspecialchars($sample['first_name'] . ' ' . $sample['last_name']); ?></td>
                              <td><?php echo htmlspecialchars($sample['sample_type']); ?></td>
                              <td><?php echo htmlspecialchars($sample['collection_date']); ?></td>
                              <td><?php echo htmlspecialchars($sample['collected_by_staff_name'] ?? 'N/A'); ?></td>
                              <td><?php echo htmlspecialchars($sample['status']); ?></td>
                              <td><?php echo htmlspecialchars($sample['branch_name'] ?? 'N/A'); ?></td>
                              <td class="text-right">
                                <div class="d-flex">
                                  <a href="edit-test-sample.php?id=<?php echo $sample['id']; ?>" class="btn-icon btn-round btn-primary text-white me-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $sample['id']; ?>" data-test-name="<?php echo htmlspecialchars($sample['test_name']); ?>" data-patient-name="<?php echo htmlspecialchars($sample['first_name'] . ' ' . $sample['last_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-test-sample"><i class="fas fa-trash"></i> </a>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="8" class="text-center">No test samples found.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>



            </div>
          </div>

        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="delete_test_sample_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-test-sample-message">Are you sure you want to delete this test sample record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteTestSampleForm" method="POST" action="test-samples.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-test-sample-id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-test-sample').on('click', function(e) {
        e.preventDefault();
        var testSampleId = $(this).data('id');
        var testName = $(this).data('test-name');
        var patientName = $(this).data('patient-name');
        $('#delete-test-sample-id').val(testSampleId);
        $('#delete-test-sample-message').text("Are you sure you want to delete the test sample for '" + testName + "' (Patient: " + patientName + ")?");
        $('#delete_test_sample_modal').modal('show');
      });
    });
  </script>
</body>

</html>
