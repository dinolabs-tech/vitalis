<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || 
   ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'lab_technician' && $_SESSION['role'] !== 'doctor')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Delete Radiology Record (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $delete_id = intval($_POST['delete_id']);

  $conn->begin_transaction();
  try {
    $stmt_radiology = $conn->prepare("DELETE FROM radiology_records WHERE id = ?");
    $stmt_radiology->bind_param("i", $delete_id);

    if (!$stmt_radiology->execute()) {
      throw new Exception("Error deleting radiology record: " . $stmt_radiology->error);
    }

    $stmt_radiology->close();
    $conn->commit();

    $success_message = "Radiology record deleted successfully!";
    header("Location: radiology-records.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Radiology Record: " . $e->getMessage();
    header("Location: radiology-records.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all radiology records
$radiology_records = [];
$sql = "SELECT rr.*, p.first_name, p.last_name, l.staffname AS doctor_name, b.branch_name
        FROM radiology_records rr
        LEFT JOIN patients p ON rr.patient_id = p.id
        LEFT JOIN doctors d ON rr.doctor_id = d.staff_id
        LEFT JOIN login l ON d.staff_id = l.id
        LEFT JOIN branches b ON rr.branch_id = b.branch_id
        ORDER BY rr.test_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $radiology_records[] = $row;
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
            <h3 class="fw-bold mb-3">Radiology Records</h3>
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
                <a href="#">Radiology Records</a>
              </li>
            </ul>
          </div>

          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-radiology-record.php" class="btn btn-primary btn-round">Add Radiology Record</a>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $error_message; ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $success_message; ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Doctor</th>
                        <th>Test Name</th>
                        <th>Request Date</th>
                        <th>Description</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($radiology_records) > 0): ?>
                        <?php foreach ($radiology_records as $record): ?>
                          <tr>
                            <td><?= htmlspecialchars(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? 'N/A')); ?></td>
                            <td><?= htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($record['test_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($record['test_date'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($record['description'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($record['branch_name'] ?? 'N/A'); ?></td>
                            <td class="text-right">
                              <div class="d-flex">
                                <a href="edit-radiology-record.php?id=<?= $record['id']; ?>" class="btn-icon btn-round btn-primary text-white me-2">
                                  <i class="fas fa-edit"></i>
                                </a>
                                <button data-id="<?= $record['id']; ?>" data-patient-name="<?= htmlspecialchars(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? 'N/A')); ?>" data-test-name="<?= htmlspecialchars($record['test_name'] ?? 'N/A'); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-radiology-record">
                                  <i class="fas fa-trash"></i>
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="7" class="text-center">No radiology records found.</td>
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

      <?php include('components/footer.php'); ?>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="deleteRadiologyRecordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-radiology-record-message">Are you sure you want to delete this Radiology Record?</h3>
          <form method="POST" action="radiology-records.php" id="deleteRadiologyRecordForm">
            <input type="hidden" name="delete_id" id="delete_id">
            <div class="m-t-20">
              <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-radiology-record').on('click', function() {
        var id = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var testName = $(this).data('test-name');
        $('#delete_id').val(id);
        $('#deleteRadiologyRecordModal #delete-radiology-record-message').text("Are you sure you want to delete the radiology record for '" + testName + "' (Patient: " + patientName + ")?");
        $('#deleteRadiologyRecordModal').modal('show');
      });
    });
  </script>
</body>
</html>
