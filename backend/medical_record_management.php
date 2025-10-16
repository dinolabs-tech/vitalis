<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, nurse or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'nurse', 'doctor', 'receptionist'])) {
  header("Location: login.php");
  exit;
}
$success_message = '';
$error_message = '';

// Placeholder for medical record management data retrieval
$medical_records = [];

// Fetch medical records data
$sql = "SELECT mr.*, p.first_name, p.last_name, l.staffname AS doctor_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id
        JOIN login l ON mr.doctor_id = l.id
        ORDER BY mr.record_date DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $medical_records[] = $row;
  }
}

// Handle Delete Medical Record
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_medical_record = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
    $stmt_medical_record->bind_param("i", $delete_id);

    if (!$stmt_medical_record->execute()) {
      throw new Exception("Error deleting medical record: " . $stmt_medical_record->error);
    }

    $stmt_medical_record->close();
    $conn->commit();

    $success_message = "Medical record deleted successfully!";
    header("Location: medical_record_management.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Medical Record: " . $e->getMessage();
    header("Location: medical_record_management.php?error=" . urlencode($error_message));
    exit;
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
            <h3 class="fw-bold mb-3">Medical Record Management</h3>
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
                <a href="#">Medical Record Management</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-medical-record.php" class="btn btn-primary btn-round">Add Medical Record</a>
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
          <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($_GET['success']); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($_GET['error']); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>
          
          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-border table-striped" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Doctor Name</th>
                        <th>Record Date</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Notes</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($medical_records)): ?>
                        <tr>
                          <td colspan="7" class="text-center">No medical records found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($medical_records as $record): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['record_date']); ?></td>
                            <td><?php echo htmlspecialchars($record['diagnosis'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['treatment'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($record['notes'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-medical-record.php?id=<?php echo $record['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $record['id']; ?>" data-patient-name="<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>" data-record-date="<?php echo htmlspecialchars($record['record_date']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-record"><i class="fas fa-trash"></i></a>
                                </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
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
  <div id="delete_medical_record_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-medical-record-message">Are you sure you want to delete this medical record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteMedicalRecordForm" method="POST" action="medical_record_management.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-medical-record-id">
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
      $('.btn-delete-record').on('click', function(e) {
        e.preventDefault();
        var recordId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var recordDate = $(this).data('record-date');
        $('#delete-medical-record-id').val(recordId);
        $('#delete-medical-record-message').text("Are you sure you want to delete the medical record for '" + patientName + "' on " + recordDate + "?");
        $('#delete_medical_record_modal').modal('show');
      });
    });
  </script>
</body>

</html>
