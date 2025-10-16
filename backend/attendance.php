<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$attendance_records = [];
$filter_employee_id = $_POST['filter_employee_id'] ?? '';
$filter_date = $_POST['filter_date'] ?? '';

$sql = "SELECT a.*, e.staffname AS employee_name, e.username AS employee_unique_id
        FROM staff_attendance a
        JOIN login e ON a.staff_id = e.id
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_employee_id)) {
  $sql .= " AND a.staff_id = ?";
  $params[] = $filter_employee_id;
  $types .= "i";
}
if (!empty($filter_date)) {
  $sql .= " AND DATE(a.date) = ?";
  $params[] = $filter_date;
  $types .= "s";
}

$sql .= " ORDER BY a.date DESC, e.staffname ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $attendance_records[] = $row;
  }
}
$stmt->close();

// Fetch employees for dropdown
$employees = [];
$result_employees = $conn->query("SELECT id, staffname AS name, username AS employee_id FROM login WHERE role != 'patient' ORDER BY name ASC");
if ($result_employees) {
  while ($row = $result_employees->fetch_assoc()) {
    $employees[] = $row;
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
            <h3 class="fw-bold mb-3">Attendance</h3>
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
                <a href="#">Attendance</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-attendance.php" class="btn btn-primary btn-round">Add Attendance</a>
              </div>
            <?php endif; ?>
          </div>


          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped custom-table mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Employee</th>
                          <th>Date</th>
                          <th>Punch In</th>
                          <th>Punch Out</th>
                          <th>Production</th>
                          <th>Break</th>
                          <th>Overtime</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($attendance_records)): ?>
                          <?php foreach ($attendance_records as $record): ?>
                            <tr>
                              <td>
                                <h6 class="table-avatar">
                                  <a href="profile.php">
                                    <?php echo htmlspecialchars($record['employee_name']); ?>
                                    <span>(<?php echo htmlspecialchars($record['employee_unique_id']); ?>)</span>
                                  </a>
                                </h6>
                              </td>
                              <td><?php echo htmlspecialchars(date('d M Y', strtotime($record['date']))); ?></td>
                              <td><?php echo htmlspecialchars(date('h:i A', strtotime($record['punch_in']))); ?></td>
                              <td><?php echo htmlspecialchars(date('h:i A', strtotime($record['punch_out']))); ?></td>
                              <td><?php echo htmlspecialchars($record['production_time']); ?></td>
                              <td><?php echo htmlspecialchars($record['break_time']); ?></td>
                              <td><?php echo htmlspecialchars($record['overtime']); ?></td>
                              <td class="text-right d-flex">
                                <a href="edit-attendance.php?id=<?php echo $record['id']; ?>" class="btn-icon btn-round btn-primary text-white mt-5 mx-1"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $record['id']; ?>" data-employee-name="<?php echo htmlspecialchars($record['employee_name']); ?>" data-attendance-date="<?php echo htmlspecialchars(date('d M Y', strtotime($record['date']))); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-attendance mt-5"><i class="fas fa-trash"></i> </a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="8" class="text-center">No attendance records found.</td>
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
      </div>
      <!-- Delete Modal -->
      <div id="delete_attendance_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-attendance-message">Are you sure you want to delete this Attendance Record?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteAttendanceForm" method="POST" action="delete-attendance.php" style="display: inline;">
                  <input type="hidden" name="attendance_id" id="delete-attendance-id">
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
      $('.btn-delete-attendance').on('click', function(e) {
        e.preventDefault();
        var attendanceId = $(this).data('id');
        var employeeName = $(this).data('employee-name');
        var attendanceDate = $(this).data('attendance-date');
        $('#delete-attendance-id').val(attendanceId);
        $('#delete-attendance-message').text("Are you sure you want to delete the attendance record for '" + employeeName + "' on " + attendanceDate + "?");
        $('#delete_attendance_modal').modal('show');
      });
    });
  </script>
</body>

</html>
