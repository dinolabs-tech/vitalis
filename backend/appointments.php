<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin'])) {
  header("Location: login.php");
  exit;
}

$id = $_SESSION['id'];
$success_message = '';
$error_message = '';
$appointments = [];

// Base SQL query
$sql = "SELECT a.id, p.first_name, p.last_name, l.staffname AS doctor_name, a.appointment_date, a.status, a.reason, b.branch_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        LEFT JOIN login l ON a.doctor_id = l.id
        LEFT JOIN branches b ON a.branch_id = b.branch_id";

$conditions = [];
$params = [];
$types = "";

// Apply doctor filter if logged-in user is a doctor
if (isset($_SESSION['role']) && $_SESSION['role'] == 'doctor' && isset($_SESSION['user_id'])) {
  $conditions[] = "a.doctor_id = ?";
  $params[] = $_SESSION['user_id'];
  $types .= "i";
}

// Apply branch filter if the user is not an admin and has a branch_id
if ($_SESSION['role'] !== 'admin' && isset($_SESSION['branch_id'])) {
    $conditions[] = "a.branch_id = ?";
    $params[] = $_SESSION['branch_id'];
    $types .= "i";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY a.appointment_date DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = intval($_POST['id']);
  $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Appointment deleted successfully!";
    header("Location: appointments.php?success=" . urlencode($success_message));
    exit;
  } else {
    $error_message = "Error deleting appointment!";
    header("Location: appointments.php?error=" . urlencode($error_message));
    exit;
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
            <h3 class="fw-bold mb-3">Appointments</h3>
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
                <a href="#">Appointments</a>
              </li>
            </ul>
            
          </div>

          
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0 mb-3">
                <a href="add-appointment.php" class="btn btn-primary btn-round">Add Appointment</a>
              </div>
            <?php endif; ?>
          

          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo $error_message; ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
          <?php endif; ?>

          <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo $success_message; ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
          <?php endif; ?>

          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-striped" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Patient Name</th>
                        <th>Doctor Name</th>
                        <th>Appointment Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($appointments)): ?>
                        <tr><td colspan="8" class="text-center">No appointments found.</td></tr>
                      <?php else: ?>
                        <?php foreach ($appointments as $appointment): ?>
                          <tr>
                            <td><?= htmlspecialchars($appointment['id']); ?></td>
                            <td><?= htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                            <td><?= htmlspecialchars($appointment['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($appointment['appointment_date']))); ?></td>
                            <td><?= htmlspecialchars($appointment['reason']); ?></td>
                            <td>
                              <?php
                                $status = strtolower($appointment['status']);
                                $badge_class = 'badge-warning';
                                if ($status === 'completed') $badge_class = 'badge-success';
                                elseif ($status === 'cancelled') $badge_class = 'badge-danger';
                              ?>
                              <span class="badge <?= $badge_class; ?>"><?= ucfirst($status); ?></span>
                            </td>
                            <td><?= htmlspecialchars($appointment['branch_name'] ?? 'N/A'); ?></td>
                            <?php if (in_array($_SESSION['role'], ['admin', 'receptionist', 'doctor'])): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-appointment.php?id=<?= $appointment['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
                                    <a href="#" data-id="<?= $appointment['id']; ?>" class="btn-icon btn-round btn-danger text-white btn-delete-appointment"><i class="fas fa-trash"></i></a>
                                  <?php endif; ?>
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

      <!-- Delete Modal -->
      <div id="deleteAppointmentModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-appointment-message">Are you sure you want to delete this appointment?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteAppointmentForm" method="POST" action="appointments.php" style="display:inline;">
                  <input type="hidden" name="id" id="delete-appointment-id">
                  <button type="submit" class="btn btn-danger btn-round">Delete</button>
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
      $('.btn-delete-appointment').on('click', function(e) {
        e.preventDefault();
        var appointmentId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var appointmentDate = $(this).data('appointment-date');
        $('#delete-appointment-id').val(appointmentId);
        $('#delete-appointment-message').text("Are you sure you want to delete the appointment for '" + patientName + "' on " + appointmentDate + "?");
        $('#deleteAppointmentModal').modal('show');
      });
    });
  </script>
</body>
</html>
