<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';


// Handle Delete IPD Admission
if (isset($_POST['id'])) {
  $ipd_admission_id_to_delete = $_POST['id'];
  // Before deleting admission, update room status back to available if a room was assigned
  $stmt_get_room = $conn->prepare("SELECT room_id FROM ipd_admissions WHERE id = ?");
  $stmt_get_room->bind_param("i", $ipd_admission_id_to_delete);
  $stmt_get_room->execute();
  $result_get_room = $stmt_get_room->get_result();
  $room_id_to_free = null;
  if ($row = $result_get_room->fetch_assoc()) {
    $room_id_to_free = $row['room_id'];
  }
  $stmt_get_room->close();

  $conn->begin_transaction();
  try {
    $stmt = $conn->prepare("DELETE FROM ipd_admissions WHERE id = ?");
    $stmt->bind_param("i", $ipd_admission_id_to_delete);
    if (!$stmt->execute()) {
      throw new Exception("Error deleting IPD admission: " . $stmt->error);
    }
    $stmt->close();

    if ($room_id_to_free) {
      $stmt_room_free = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
      $stmt_room_free->bind_param("i", $room_id_to_free);
      if (!$stmt_room_free->execute()) {
        throw new Exception("Error freeing room: " . $stmt_room_free->error);
      }
      $stmt_room_free->close();
    }
    $conn->commit();
    $success_message = "IPD Admission deleted successfully!";
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete IPD admission: " . $e->getMessage();
  }
}

// Fetch all IPD admissions
$ipd_admissions = [];
$sql = "SELECT ia.*, p.first_name, p.last_name, r.room_number, l.staffname as doctor_name, b.branch_name
        FROM ipd_admissions ia
        LEFT JOIN patients p ON ia.patient_id = p.patient_id
        LEFT JOIN rooms r ON ia.room_id = r.id
        LEFT JOIN login l ON ia.doctor_id = l.id
        LEFT JOIN branches b ON ia.branch_id = b.branch_id
        ORDER BY ia.admission_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $ipd_admissions[] = $row;
  }
}

// Fetch patients for dropdown (only if needed for display)
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch rooms for dropdown (only if needed for display)
$rooms = [];
$result_rooms = $conn->query("SELECT id, room_number, room_type FROM rooms ORDER BY room_number ASC");
if ($result_rooms) {
  while ($row = $result_rooms->fetch_assoc()) {
    $rooms[] = $row;
  }
}

// Fetch doctors for dropdown (only if needed for display)
$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role='doctor' ORDER BY staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// Fetch branches for dropdown (only if needed for display)
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
            <h3 class="fw-bold mb-3">IPD Admissions</h3>
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
                <a href="#">IPD Admissions</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-ipd-admission.php" class="btn btn-primary btn-round">Add IPD Admission</a>
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
              <div class="card p-3">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Admission Date</th>
                        <th>Discharge Date</th>
                        <th>Room Number</th>
                        <th>Doctor</th>
                        <th>Reason for Admission</th>
                        <th>Diagnosis</th>
                        <th>Status</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($ipd_admissions) > 0): ?>
                        <?php foreach ($ipd_admissions as $admission): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($admission['admission_date']); ?></td>
                            <td><?php echo htmlspecialchars($admission['discharge_date'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($admission['room_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($admission['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($admission['reason_for_admission']); ?></td>
                            <td><?php echo htmlspecialchars($admission['diagnosis'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($admission['status']); ?></td>
                            <td><?php echo htmlspecialchars($admission['branch_name'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse' || $_SESSION['role'] === 'doctor'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-ipd-admission.php?id=<?php echo $admission['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
                                    <a href="#" data-id="<?php echo $admission['id']; ?>" data-patient-name="<?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-ipd"><i class="fas fa-trash"></i> </a>
                                  <?php endif; ?>
                              </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="10" class="text-center">No IPD admissions found.</td>
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
      <!-- Delete Modal -->
      <div id="delete_ipd_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-ipd-message">Are you sure you want to delete this IPD Admission?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteIPDForm" method="POST" action="ipd-admissions.php" style="display: inline;">
                  <input type="hidden" name="id" id="delete-ipd-id">
                  <button type="submit" class="btn btn-danger">Delete</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-ipd').on('click', function(e) {
        e.preventDefault();
        var ipdId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        $('#delete-ipd-id').val(ipdId);
        $('#delete-ipd-message').text("Are you sure you want to delete the IPD Admission for '" + patientName + "'?");
        $('#delete_ipd_modal').modal('show');
      });
    });
  </script>
</body>

</html>
