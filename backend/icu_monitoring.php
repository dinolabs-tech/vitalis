<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or nurse role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'nurse'])) {
  header("Location: login.php");
  exit;
}
$success_message = '';
$error_message = '';

// Placeholder for ICU monitoring data retrieval
$icu_monitorings = [];

// Fetch ICU monitoring data
$sql = "SELECT icu.*, p.first_name, p.last_name, a.patient_id AS admission_patient_id
        FROM icu_patient_monitoring icu
        JOIN admissions a ON icu.admission_id = a.id
        JOIN patients p ON a.patient_id = p.patient_id
        ORDER BY icu.timestamp DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $icu_monitorings[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $stmt = $conn->prepare("DELETE FROM icu_patient_monitoring WHERE id = ?");
  $stmt->bind_param("i", $delete_id);

  if ($stmt->execute()) {
    $success_message = "ICU monitoring record deleted successfully!";
    header("Location: icu_monitoring.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error deleting ICU monitoring record: " . $stmt->error;
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
            <h3 class="fw-bold mb-3">ICU Monitoring</h3>
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
                <a href="#">ICU Monitoring</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse' || $_SESSION['role'] === 'doctor'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-icu-monitoring.php" class="btn btn-primary btn-round">Add ICU Monitoring</a>
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
                        <th>Admission ID</th>
                        <th>Parameter Name</th>
                        <th>Value</th>
                        <th>Timestamp</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($icu_monitorings)): ?>
                        <tr>
                          <td colspan="6" class="text-center">No ICU monitoring records found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($icu_monitorings as $monitor): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($monitor['first_name'] . ' ' . $monitor['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['admission_id']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['parameter_name']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['value']); ?></td>
                            <td><?php echo htmlspecialchars($monitor['timestamp']); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse' || $_SESSION['role'] === 'doctor'): ?>
                            <td class="text-right">
                              <div class="d-flex">
                                <a href="edit-icu-monitoring.php?id=<?php echo $monitor['id']; ?>" class="btn-primary btn-icon btn-round text-white me-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $monitor['id']; ?>" data-patient-name="<?php echo htmlspecialchars($monitor['first_name'] . ' ' . $monitor['last_name']); ?>" data-parameter-name="<?php echo htmlspecialchars($monitor['parameter_name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-monitor"><i class="fas fa-trash"></i></a>
                              </div>
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
  <div id="delete_icu_monitoring_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-icu-monitoring-message">Are you sure you want to delete this ICU Monitoring Record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteICUMonitoringForm" method="POST" action="icu_monitoring.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-icu-monitoring-id">
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
      $('.btn-delete-monitor').on('click', function(e) {
        e.preventDefault();
        var monitorId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var parameterName = $(this).data('parameter-name');
        $('#delete-icu-monitoring-id').val(monitorId);
        $('#delete-icu-monitoring-message').text("Are you sure you want to delete the ICU monitoring record for '" + patientName + "' (Parameter: " + parameterName + ")?");
        $('#delete_icu_monitoring_modal').modal('show');
      });
    });
  </script>
</body>

</html>
