<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist' && $_SESSION['role'] !== 'nurse')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Delete Admission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $admission_id_to_delete = $_POST['id'];
  $stmt_get_room = $conn->prepare("SELECT room_id FROM admissions WHERE id = ?");
  $stmt_get_room->bind_param("i", $admission_id_to_delete);
  $stmt_get_room->execute();
  $result_get_room = $stmt_get_room->get_result();
  $room_id_to_free = null;
  if ($row = $result_get_room->fetch_assoc()) {
    $room_id_to_free = $row['room_id'];
  }
  $stmt_get_room->close();

  $conn->begin_transaction();
  try {
    $stmt = $conn->prepare("DELETE FROM admissions WHERE id = ?");
    $stmt->bind_param("i", $admission_id_to_delete);
    if (!$stmt->execute()) {
      throw new Exception("Error deleting admission: " . $stmt->error);
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
    $success_message = "Admission deleted successfully!";
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete admission: " . $e->getMessage();
  }
}

// Fetch all admissions
$admissions = [];
$sql = "SELECT a.*, p.first_name, p.last_name, r.room_number, s.staffname AS admitted_by_staff_name
        FROM admissions a
        LEFT JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN rooms r ON a.room_id = r.id
        LEFT JOIN login s ON a.admitted_by_staff_id = s.id
        ORDER BY a.admission_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $admissions[] = $row;
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
            <h3 class="fw-bold mb-3">Admissions</h3>
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
                <a href="#">Admissions</a>
              </li>
            </ul>
          </div>

          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-admission.php" class="btn btn-primary btn-round">Add Admission</a>
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
            <div class="card p-3">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Room Number</th>
                        <th>Admission Date</th>
                        <th>Discharge Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Admitted By</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($admissions) > 0): ?>
                        <?php foreach ($admissions as $admission): ?>
                          <tr>
                            <td><?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></td>
                            <td><?= htmlspecialchars($admission['room_number']); ?></td>
                            <td><?= htmlspecialchars($admission['admission_date']); ?></td>
                            <td><?= htmlspecialchars($admission['discharge_date'] ?? 'N/A'); ?></td>
                            <td><?= htmlspecialchars($admission['reason']); ?></td>
                            <td><?= htmlspecialchars($admission['status']); ?></td>
                            <td><?= htmlspecialchars($admission['admitted_by_staff_name'] ?? 'N/A'); ?></td>
                            <td class="text-right d-flex">
                              <a href="edit-admission.php?id=<?= $admission['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2">
                                <i class="fas fa-edit"></i>
                              </a>
                              <a href="#"
                                data-id="<?= $admission['id']; ?>"
                                data-name="<?= htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>"
                                class="btn-icon btn-danger btn-round text-white btn-delete-admission">
                                <i class="fas fa-trash"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="text-center">No admissions found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Delete Modal -->
          <div id="deleteAdmissionModal" class="modal fade delete-modal" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-body text-center">
                  <img src="assets/img/sent.png" alt="" width="50" height="46">
                  <h3 id="delete-message">Are you sure you want to delete this admission?</h3>
                  <div class="m-t-20">
                    <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                    <form id="deleteAdmissionForm" method="POST" action="admissions.php" style="display: inline;">
                      <input type="hidden" name="id" id="delete-admission-id">
                      <button type="submit" class="btn btn-danger rounded">Delete</button>
                    </form>
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
  <script>
    $(document).ready(function() {
      $('.btn-delete-admission').on('click', function(e) {
        e.preventDefault();
        var admissionId = $(this).data('id');
        var admissionName = $(this).data('name');
        $('#delete-admission-id').val(admissionId);
        $('#delete-message').text("Are you sure you want to delete " + admissionName + "'s admission?");
        $('#deleteAdmissionModal').modal('show');
      });
    });
  </script>
</body>

</html>