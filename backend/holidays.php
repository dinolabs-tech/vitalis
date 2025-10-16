<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();

$error_message = '';
$success_message = '';

if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Holiday deleted successfully!";
  } else {
    $error_message = "Failed to delete holiday.";
  }
  $stmt->close();
  header("Location: holidays.php?success=" . urlencode($success_message));
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
            <h3 class="fw-bold mb-3">Holidays</h3>
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
                <a href="#">Holidays</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-holiday.php" class="btn btn-primary btn-round">Add Holiday</a>
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Holiday Name</th>
                          <th>Holiday Date</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT * FROM holidays";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->holidayName; ?></td>
                            <td><?php echo $row->holidayDate; ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-holiday.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" class="btn-icon btn-round btn-danger text-white btn-delete-holiday"><i class="fas fa-trash"></i> </a>
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
  <div id="delete_holiday_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-holiday-message">Are you sure you want to delete this Holiday Record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteHolidayForm" method="POST" action="holidays.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-holiday-id">
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
      $('.btn-delete-holiday').on('click', function(e) {
        e.preventDefault();
        var holidayId = $(this).data('id');
        var holidayName = $(this).data('holiday-name');
        $('#delete-holiday-id').val(holidayId);
        $('#delete-holiday-message').text("Are you sure you want to delete the holiday '" + holidayName + "'?");
        $('#delete_holiday_modal').modal('show');
      });
    });
  </script>
</body>

</html>
