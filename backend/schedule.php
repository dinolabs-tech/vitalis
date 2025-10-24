<?php
session_start();
require_once 'database/db_connect.php';

$success_message = '';
$error_message = '';

// Handle Delete Schedule
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_schedule = $conn->prepare("DELETE FROM doctor_schedules WHERE id = ?");
    $stmt_schedule->bind_param("i", $delete_id);

    if (!$stmt_schedule->execute()) {
      throw new Exception("Error deleting doctor schedule record: " . $stmt_schedule->error);
    }

    $stmt_schedule->close();
    $conn->commit();

    $success_message = "Doctor's Schedule deleted successfully!";
    header("Location: schedule.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Doctor's Schedule: " . $e->getMessage();
    header("Location: schedule.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch schedule data
$sql = "SELECT ds.id, l.specialization, l.staffname AS doctor_name, ds.day_of_week, ds.start_time, ds.end_time, ds.status
        FROM doctor_schedules ds
        JOIN login l ON ds.doctor_id = l.id
        ";
$result = $conn->query($sql);

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
            <h3 class="fw-bold mb-3">Doctor Schedule</h3>
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
                <a href="#">Doctor Schedule</a>
              </li>
            </ul>
          </div>

          <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
            <div class="ms-md-auto py-2 py-md-0 mb-3">
              <a href="add-schedule.php" class="btn btn-primary btn-round">Add Schedule</a>
            </div>
          <?php endif; ?>

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

          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-border table-striped custom-table mb-0" id="basic-datatables">
                  <thead>
                    <tr>
                      <th>Doctor Name</th>
                      <th>Department</th>
                      <th>Available Day</th>
                      <th>Available Time</th>
                      <th>Status</th>
                      <th class="text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $status_class = ($row['status'] == 'active') ? 'status-green' : 'status-red';
                    ?>
                        <tr>
                          <td><img width="28" height="28" src="assets/img/user.jpg" class="rounded-circle m-r-5" alt=""> <?php echo $row['doctor_name']; ?></td>
                          <td><?php echo $row['specialization']; ?></td>
                          <td><?php echo $row['day_of_week']; ?></td>
                          <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                          <td><span class="custom-badge <?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                          <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist' || $_SESSION['role'] === 'doctor'): ?>
                            <td class="text-right d-flex">
                              <a href="edit-schedule.php?id=<?php echo $row['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                              <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
                                <a href="#" data-id="<?php echo $row['id']; ?>" data-doctor-name="<?php echo htmlspecialchars($row['doctor_name']); ?>" data-day-of-week="<?php echo htmlspecialchars($row['day_of_week']); ?>" data-start-time="<?php echo htmlspecialchars(date('h:i A', strtotime($row['start_time']))); ?>" data-end-time="<?php echo htmlspecialchars(date('h:i A', strtotime($row['end_time']))); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-schedule"><i class="fas fa-trash"></i></a>
                              <?php endif; ?>

                            </td>
                          <?php endif; ?>
                        </tr>
                      <?php
                      }
                    } else { ?>
                      <tr>
                        <td colspan='6' class='text-center'>No schedules found.</td>
                      </tr>
                    <?php  }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="delete_schedule_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-schedule-message">Are you sure you want to delete this doctor's schedule record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteScheduleForm" method="POST" action="schedule.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-schedule-id">
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
      $('.btn-delete-schedule').on('click', function(e) {
        e.preventDefault();
        var scheduleId = $(this).data('id');
        var doctorName = $(this).data('doctor-name');
        var dayOfWeek = $(this).data('day-of-week');
        var startTime = $(this).data('start-time');
        var endTime = $(this).data('end-time');
        $('#delete-schedule-id').val(scheduleId);
        $('#delete-schedule-message').text("Are you sure you want to delete the schedule for Dr. " + doctorName + " on " + dayOfWeek + " from " + startTime + " to " + endTime + "?");
        $('#delete_schedule_modal').modal('show');
      });
    });
  </script>
</body>

</html>
