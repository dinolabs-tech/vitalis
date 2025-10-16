<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist'])) {
  header("Location: login.php");
  exit;
}
$success_message = '';
$error_message = '';

// Placeholder for ER visits data retrieval
$er_visits = [];

// Fetch ER visits data
$sql = "SELECT ev.*, p.first_name, p.last_name 
        FROM er_visits ev
        JOIN patients p ON ev.patient_id = p.patient_id
        ORDER BY ev.arrival_time DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $er_visits[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $stmt = $conn->prepare("DELETE FROM er_visits WHERE id = ?");
  $stmt->bind_param("i", $delete_id);

  if ($stmt->execute()) {
    $success_message = "ER visit record deleted successfully!";
    header("Location: er_visits.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error deleting ER visit record: " . $stmt->error;
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
            <h3 class="fw-bold mb-3">ER Visits</h3>
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
                <a href="#">ER Visits</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-er-visit.php" class="btn btn-primary btn-round">Add ER Visit</a>
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
                        <th>Arrival Time</th>
                        <th>Chief Complaint</th>
                        <th>Triage Level</th>
                        <th>Discharge Time</th>
                        <th>Initial Findings</th>
                        <th>Subsequent Care</th>
                        <th>Outcome</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($er_visits)): ?>
                        <tr>
                          <td colspan="9" class="text-center">No ER visits found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($er_visits as $visit): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($visit['arrival_time']); ?></td>
                            <td><?php echo htmlspecialchars($visit['chief_complaint']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $visit['triage_level']))); ?></td>
                            <td><?php echo htmlspecialchars($visit['discharge_time'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['initial_findings'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['subsequent_care'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['outcome'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-er-visit.php?id=<?php echo $visit['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $visit['id']; ?>" class="btn-icon btn-danger btn-round text-white btn-delete-visit"><i class="fas fa-trash"></i></a>
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
  <div id="delete_er_visit_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-er-visit-message">Are you sure you want to delete this ER Visit Record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteERVisitForm" method="POST" action="er_visits.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-er-visit-id">
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
      $('.btn-delete-visit').on('click', function(e) {
        e.preventDefault();
        var visitId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var arrivalTime = $(this).data('arrival-time');
        $('#delete-er-visit-id').val(visitId);
        $('#delete-er-visit-message').text("Are you sure you want to delete the ER visit record for '" + patientName + "' on " + arrivalTime + "?");
        $('#delete_er_visit_modal').modal('show');
      });
    });
  </script>
</body>

</html>
