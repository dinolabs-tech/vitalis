<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'doctor') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit OPD Visit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $visit_date = $_POST['visit_date'] ?? date('Y-m-d H:i:s');
  $doctor_id = $_POST['doctor_id'] ?? null;
  $reason_for_visit = $_POST['reason_for_visit'] ?? '';
  $diagnosis = $_POST['diagnosis'] ?? '';
  $treatment = $_POST['treatment'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;
  $opd_visit_id = $_POST['opd_visit_id'] ?? null; // For editing

  if (empty($patient_id) || empty($visit_date) || empty($reason_for_visit)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($opd_visit_id) {
      // Update existing OPD visit
      $stmt = $conn->prepare("UPDATE opd_visits SET patient_id = ?, visit_date = ?, doctor_id = ?, reason_for_visit = ?, diagnosis = ?, treatment = ?, notes = ?, branch_id = ? WHERE id = ?");
      $stmt->bind_param("iissssii", $patient_id, $visit_date, $doctor_id, $reason_for_visit, $diagnosis, $treatment, $notes, $branch_id, $opd_visit_id);
      if ($stmt->execute()) {
        $success_message = "OPD Visit updated successfully!";
      } else {
        $error_message = "Error updating OPD visit: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new OPD visit
      $stmt = $conn->prepare("INSERT INTO opd_visits (patient_id, visit_date, doctor_id, reason_for_visit, diagnosis, treatment, notes, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("iissssii", $patient_id, $visit_date, $doctor_id, $reason_for_visit, $diagnosis, $treatment, $notes, $branch_id);
      if ($stmt->execute()) {
        $success_message = "OPD Visit added successfully!";
      } else {
        $error_message = "Error adding OPD visit: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// Handle Delete OPD Visit
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_opd_visit = $conn->prepare("DELETE FROM opd_visits WHERE id = ?");
    $stmt_opd_visit->bind_param("i", $delete_id);

    if (!$stmt_opd_visit->execute()) {
      throw new Exception("Error deleting OPD visit record: " . $stmt_opd_visit->error);
    }

    $stmt_opd_visit->close();
    $conn->commit();

    $success_message = "OPD Visit deleted successfully!";
    header("Location: opd-visits.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete OPD Visit: " . $e->getMessage();
    header("Location: opd-visits.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all OPD visits
$opd_visits = [];
$sql = "SELECT ov.*, p.first_name, p.last_name, l.staffname as doctor_name, b.branch_name
        FROM opd_visits ov
        LEFT JOIN patients p ON ov.patient_id = p.id
        LEFT JOIN login l ON ov.doctor_id = l.id
        LEFT JOIN branches b ON ov.branch_id = b.branch_id
        ORDER BY ov.visit_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $opd_visits[] = $row;
  }
}

// Fetch OPD visit data for editing if ID is provided in GET
$edit_opd_visit_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_opd_visit_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM opd_visits WHERE id = ?");
  $stmt->bind_param("i", $edit_opd_visit_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_opd_visit_data = $result->fetch_assoc();
  } else {
    $error_message = "OPD Visit not found for editing.";
  }
  $stmt->close();
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT d.id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id ORDER BY l.staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
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
            <h3 class="fw-bold mb-3">OPD Visits</h3>
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
                <a href="#">OPD Visits</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-opd-visit.php" class="btn btn-primary btn-round">Add OPD Visit</a>
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
                        <th>Visit Date</th>
                        <th>Doctor</th>
                        <th>Reason for Visit</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($opd_visits) > 0): ?>
                        <?php foreach ($opd_visits as $visit): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($visit['visit_date']); ?></td>
                            <td><?php echo htmlspecialchars($visit['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($visit['reason_for_visit']); ?></td>
                            <td><?php echo htmlspecialchars($visit['diagnosis']); ?></td>
                            <td><?php echo htmlspecialchars($visit['treatment']); ?></td>
                            <td><?php echo htmlspecialchars($visit['branch_name'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist' || $_SESSION['role'] === 'doctor' || $_SESSION['role'] === 'nurse'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-opd-visit.php?id=<?php echo $visit['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
                                    <a href="#" data-id="<?php echo $visit['id']; ?>" data-patient-name="<?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?>" data-visit-date="<?php echo htmlspecialchars($visit['visit_date']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-opd-visit"><i class="fas fa-trash"></i></a>
                                  <?php endif; ?>
                              </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="text-center">No OPD visits found.</td>
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
  <div id="delete_opd_visit_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-opd-visit-message">Are you sure you want to delete this OPD visit record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteOPDVisitForm" method="POST" action="opd-visits.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-opd-visit-id">
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
      $('.btn-delete-opd-visit').on('click', function(e) {
        e.preventDefault();
        var opdVisitId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var visitDate = $(this).data('visit-date');
        $('#delete-opd-visit-id').val(opdVisitId);
        $('#delete-opd-visit-message').text("Are you sure you want to delete the OPD visit for '" + patientName + "' on " + visitDate + "?");
        $('#delete_opd_visit_modal').modal('show');
      });
    });
  </script>
</body>

</html>
