<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

$error_message = '';
$success_message = '';

// Handle Delete Leave
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_leave = $conn->prepare("DELETE FROM leaves WHERE id = ?");
    $stmt_leave->bind_param("i", $delete_id);

    if (!$stmt_leave->execute()) {
      throw new Exception("Error deleting leave record: " . $stmt_leave->error);
    }

    $stmt_leave->close();
    $conn->commit();

    $success_message = "Leave record deleted successfully!";
    header("Location: leaves.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Leave record: " . $e->getMessage();
    header("Location: leaves.php?error=" . urlencode($error_message));
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
            <h3 class="fw-bold mb-3">Leaves</h3>
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
                <a href="#">Leaves</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-leave.php" class="btn btn-primary btn-round">Add Leave</a>
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
            <div class="col-sm-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Employee Name</th>
                          <th>Leave Type</th>
                          <th>From Date</th>
                          <th>To Date</th>
                          <th>Reason</th>
                          <th>Status</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT l.*, e.staffname, lt.leaveType FROM leaves l JOIN login e ON l.employeeId = e.id JOIN leave_types lt ON l.leaveTypeId = lt.id";
                        $stmt = $conn->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->staffname; ?></td>
                            <td><?php echo $row->leaveType; ?></td>
                            <td><?php echo $row->fromDate; ?></td>
                            <td><?php echo $row->toDate; ?></td>
                            <td><?php echo $row->reason; ?></td>
                            <td><?php echo $row->status; ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-leave.php?id=<?php echo $row->id; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-employee-name="<?php echo htmlspecialchars($row->staffname); ?>" data-leave-type="<?php echo htmlspecialchars($row->leaveType); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-leave"><i class="fas fa-trash"></i></a>
                            </td>
                          </tr>
                        <?php } ?>
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
  <!-- Delete Modal -->
  <div id="delete_leave_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-leave-message">Are you sure you want to delete this leave record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteLeaveForm" method="POST" action="leaves.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-leave-id">
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
      $('.btn-delete-leave').on('click', function(e) {
        e.preventDefault();
        var leaveId = $(this).data('id');
        var employeeName = $(this).data('employee-name');
        var leaveType = $(this).data('leave-type');
        $('#delete-leave-id').val(leaveId);
        $('#delete-leave-message').text("Are you sure you want to delete the '" + leaveType + "' leave record for '" + employeeName + "'?");
        $('#delete_leave_modal').modal('show');
      });
    });
  </script>
</body>

</html>
