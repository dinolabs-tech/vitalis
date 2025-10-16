<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();

$error_message = '';
$success_message = '';

if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM leave_types WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Leave Type deleted successfully!";
  } else {
    $error_message = "Failed to delete Leave Type.";
  }
  $stmt->close();
  header("Location: leave-type.php?success=" . urlencode($success_message));
  exit;
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
            <ul class="breadcrumbs">
              <li class="nav-home">
                <a href="index.php">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Leave Type</a>
              </li>
            </ul>
          </div>
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h4 class="fw-bold mb-3">Leave Type</h4>
            </div>
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-leave-type.php" class="btn btn-primary btn-round">Add Leave Type</a>
              </div>
            <?php endif; ?>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>S/N</th>
                        <th>Leave Type</th>
                        <th>Leave Days</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $ret = "SELECT id, leaveType, leaveDays, status FROM leave_types";
                      $stmt = $mysqli->prepare($ret);
                      $stmt->execute();
                      $res = $stmt->get_result();
                      $cnt = 1;
                      while ($row = $res->fetch_object()) {
                      ?>
                        <tr>
                          <td><?php echo $cnt; ?></td>
                          <td><?php echo $row->leaveType; ?></td>
                          <td><?php echo $row->leaveDays; ?> Days</td>
                          <td>
                            <div class="dropdown action-label">
                              <a class="custom-badge <?php echo ($row->status == 'Active') ? 'status-green' : 'status-red'; ?>" href="#" data-toggle="dropdown" aria-expanded="false">
                                <?php echo $row->status; ?>
                              </a>
                            </div>
                          </td>
                          <td class="text-right">
                            <div class="d-flex">
                              <a href="edit-leave-type.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white me-2"><i class="fas fa-edit"></i></a>
                              <a href="#" data-id="<?php echo $row->id; ?>" data-leave-type-name="<?php echo htmlspecialchars($row->leaveType); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-leave-type"><i class="fas fa-trash"></i> </a>
                            </div>
                          </td>
                        </tr>
                      <?php
                        $cnt = $cnt + 1;
                      } ?>
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
  <div id="delete_leave_type_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-leave-type-message">Are you sure you want to delete this Leave Type?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteLeaveTypeForm" method="POST" action="leave-type.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-leave-type-id">
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
      $('.btn-delete-leave-type').on('click', function(e) {
        e.preventDefault();
        var leaveTypeId = $(this).data('id');
        var leaveTypeName = $(this).data('leave-type-name');
        $('#delete-leave-type-id').val(leaveTypeId);
        $('#delete-leave-type-message').text("Are you sure you want to delete the leave type '" + leaveTypeName + "'?");
        $('#delete_leave_type_modal').modal('show');
      });
    });
  </script>
</body>

</html>
