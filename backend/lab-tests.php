<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('database/db_connect.php');

// ✅ Role & session check
if (
  !isset($_SESSION['loggedin']) ||
  !in_array($_SESSION['role'], ['admin', 'lab_technician', 'doctor'])
) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// ✅ Handle Delete Lab Test
if (isset($_POST['delete_lab_test_id'])) {
  $id = $_POST['delete_lab_test_id'];
  $stmt = $conn->prepare("DELETE FROM lab_tests WHERE id = ?");
  $stmt->bind_param('i', $id);
  if ($stmt->execute()) {
    header("Location: lab-tests.php?success=" . urlencode("Lab Test deleted successfully!"));
    exit;
  } else {
    $error_message = "Error deleting lab test: " . $stmt->error;
  }
  $stmt->close();
}

// ✅ Handle Add/Edit Lab Test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_lab_test_id'])) {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? null;
  $test_name = $_POST['test_name'] ?? '';
  $price = $_POST['price'] ?? 0.00;
  $test_date = $_POST['test_date'] ?? date('Y-m-d H:i:s');
  $results = $_POST['results'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $performed_by_staff_id = $_POST['performed_by_staff_id'] ?? null;
  $notes = $_POST['notes'] ?? '';
  $lab_test_id = $_POST['lab_test_id'] ?? null;

  if (empty($patient_id) || empty($test_name) || empty($price) || $price <= 0) {
    $error_message = "Please fill in all required fields and ensure price is positive.";
  } else {
    if ($lab_test_id) {
      // ✅ Update existing test
      $stmt = $conn->prepare("UPDATE lab_tests 
        SET patient_id=?, doctor_id=?, test_name=?, price=?, test_date=?, results=?, status=?, performed_by_staff_id=?, notes=? 
        WHERE id=?");
      $stmt->bind_param(
        "iisds ss sii",
        $patient_id,
        $doctor_id,
        $test_name,
        $price,
        $test_date,
        $results,
        $status,
        $performed_by_staff_id,
        $notes,
        $lab_test_id
      );
      if ($stmt->execute()) {
        $success_message = "Lab Test updated successfully!";
      } else {
        $error_message = "Error updating lab test: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // ✅ Add new test
      $stmt = $conn->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_name, price, test_date, results, status, performed_by_staff_id, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param(
        "iisds ss s",
        $patient_id,
        $doctor_id,
        $test_name,
        $price,
        $test_date,
        $results,
        $status,
        $performed_by_staff_id,
        $notes
      );
      if ($stmt->execute()) {
        $success_message = "Lab Test added successfully!";
      } else {
        $error_message = "Error adding lab test: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// ✅ Fetch all lab tests
$lab_tests = [];
$sql = "SELECT 
          lt.id AS test_id,
          lt.test_name,
          lt.status,
          lt.test_date,
          lt.description,
          p.first_name,
          p.last_name,
          l.staffname AS doctor_name,
          pb.staffname AS performed_by
        FROM lab_tests lt
        LEFT JOIN patients p ON lt.patient_id = p.id
        LEFT JOIN login l ON lt.doctor_id = l.id
        LEFT JOIN login pb ON lt.performed_by_staff_id = pb.id
        ORDER BY lt.test_date DESC";

$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $lab_tests[] = $row;
  }
}

// ✅ Fetch patients for dropdown
$patients = [];
$res = $conn->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $patients[] = $row;
  }
}

// ✅ Fetch doctors for dropdown
$doctors = [];
$res = $conn->query("SELECT * FROM login WHERE role='doctor' ORDER BY staffname ASC");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// ✅ Fetch staff for dropdown
$staff = [];
$res = $conn->query("SELECT id, staffname FROM login WHERE role IN ('admin', 'lab_technician', 'doctor') ORDER BY staffname ASC");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $staff[] = $row;
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
            <h3 class="fw-bold mb-3">Lab Test</h3>
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
                <a href="#">Lab Test</a>
              </li>
            </ul>
          </div>

          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-lab-test.php" class="btn btn-primary btn-round">Add Lab Test</a>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($error_message); ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
          <?php endif; ?>

          <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($success_message); ?>
              <button type="button" class="close" data-dismiss="alert">&times;</button>
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
                          <th>ID</th>
                          <th>Patient Name</th>
                          <th>Doctor</th>
                          <th>Test Name</th>
                          <th>Description</th>
                          <th>Test Date</th>
                          <th>Status</th>
                          <th>Performed By</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($lab_tests) > 0): ?>
                          <?php foreach ($lab_tests as $test): ?>
                            <tr>
                              <td><?= htmlspecialchars($test['test_id']); ?></td>
                              <td><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']); ?></td>
                              <td><?= htmlspecialchars($test['doctor_name'] ?? 'N/A'); ?></td>
                              <td><?= htmlspecialchars($test['test_name']); ?></td>
                              <td><?= htmlspecialchars($test['description']); ?></td>
                              <td><?= htmlspecialchars($test['test_date']); ?></td>
                              <td><?= htmlspecialchars($test['status']); ?></td>
                              <td><?= htmlspecialchars($test['performed_by'] ?? 'N/A'); ?></td>
                              <td class="text-right d-flex">
                                  <a href="edit-lab-test.php?id=<?= $test['test_id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?= $test['test_id']; ?>" class="btn-icon btn-round btn-danger text-white btn-delete-lab-test"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="9" class="text-center">No lab tests found.</td>
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

  <?php include('components/script.php'); ?>

  <!-- ✅ Delete Modal (only one global modal) -->
  <div id="delete_lab_test" class="modal fade delete-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3>Are you sure you want to delete this Lab Test?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteLabTestForm" method="POST" action="lab-tests.php" style="display:inline;">
              <input type="hidden" id="delete-lab-test-id" name="delete_lab_test_id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      $('.btn-delete-lab-test').on('click', function(e) {
        e.preventDefault();
        const labTestId = $(this).data('id');
        $('#delete-lab-test-id').val(labTestId);
        $('#delete_lab_test').modal('show');
      });
    });
  </script>
</body>

</html>