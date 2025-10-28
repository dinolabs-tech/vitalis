<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';


// Handle Delete Operation
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_operation = $conn->prepare("DELETE FROM operations WHERE id = ?");
    $stmt_operation->bind_param("i", $delete_id);

    if (!$stmt_operation->execute()) {
      throw new Exception("Error deleting operation record: " . $stmt_operation->error);
    }

    $stmt_operation->close();
    $conn->commit();

    $success_message = "Operation deleted successfully!";
    header("Location: operations.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Operation: " . $e->getMessage();
    header("Location: operations.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all operations
$operations = [];
$filter_branch_id = $_GET['branch_id'] ?? '';

$sql = "SELECT o.*, p.first_name, p.last_name, d.staffname as doctor_name, r.room_number, b.branch_name
        FROM operations o
        LEFT JOIN patients p ON o.patient_id = p.patient_id
        LEFT JOIN login d ON o.doctor_id = d.id
        LEFT JOIN rooms r ON o.room_id = r.id
        LEFT JOIN branches b ON o.branch_id = b.branch_id
        WHERE 1=1";

if (!empty($filter_branch_id)) {
  $sql .= " AND o.branch_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $filter_branch_id);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $sql .= " ORDER BY o.operation_date DESC";
  $result = $conn->query($sql);
}
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $operations[] = $row;
  }
}

// Fetch patients for dropdown (only if needed for display, not for form submission)
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch doctors for dropdown (only if needed for display)
$doctors = [];
$result_doctors = $conn->query("SELECT d.id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id ORDER BY l.staffname ASC");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
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
            <h3 class="fw-bold mb-3">Operations</h3>
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
                <a href="#">Operations</a>
              </li>
            </ul>
          </div>

          <form method="GET" action="">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="branch_id">Branch</label>
                  <select class="form-control" id="branch_id" name="branch_id">
                    <option value="">All Branches</option>
                    <?php
                    $filter_branch_id = $_GET['branch_id'] ?? '';
                    foreach ($branches as $branch): ?>
                      <option value="<?php echo htmlspecialchars($branch['branch_id']); ?>" <?php echo ($filter_branch_id == $branch['branch_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label>&nbsp;</label><br>
                  <button type="submit" class="btn btn-primary">Filter</button>
                  <a href="operations.php" class="btn btn-secondary">Reset</a>
                </div>
              </div>
            </div>
          </form>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'doctor'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-operation.php" class="btn btn-primary btn-round">Add Operation</a>
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-12">
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
              <div class="card p-3">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Doctor</th>
                        <th>Room</th>
                        <th>Operation Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Procedure Name</th>
                        <th>Status</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($operations) > 0): ?>
                        <?php foreach ($operations as $operation): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($operation['first_name'] . ' ' . $operation['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($operation['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($operation['room_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($operation['operation_date']); ?></td>
                            <td><?php echo htmlspecialchars($operation['start_time'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($operation['end_time'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($operation['procedure_name']); ?></td>
                            <td><?php echo htmlspecialchars($operation['status']); ?></td>
                            <td><?php echo htmlspecialchars($operation['branch_name'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'doctor'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-operation.php?id=<?php echo $operation['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $operation['id']; ?>" data-patient-name="<?php echo htmlspecialchars($operation['first_name'] . ' ' . $operation['last_name']); ?>" data-procedure-name="<?php echo htmlspecialchars($operation['procedure_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-operation"><i class="fas fa-trash"></i> </a>
                                </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="10" class="text-center">No operations found.</td>
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
  <div id="delete_operation_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-operation-message">Are you sure you want to delete this operation record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteOperationForm" method="POST" action="operations.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-operation-id">
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
      $('.btn-delete-operation').on('click', function(e) {
        e.preventDefault();
        var operationId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var procedureName = $(this).data('procedure-name');
        $('#delete-operation-id').val(operationId);
        $('#delete-operation-message').text("Are you sure you want to delete the operation '" + procedureName + "' for patient '" + patientName + "'?");
        $('#delete_operation_modal').modal('show');
      });
    });
  </script>
</body>

</html>
