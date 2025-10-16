<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();
// error_reporting(0); // Temporarily enable error reporting for debugging

if (isset($_POST['submit'])) {
  $employeeId = $_POST['employeeId']; // Get employee ID from form
  $leaveTypeId = $_POST['leaveType'];
  $fromDate = $_POST['fromDate'];
  $toDate = $_POST['toDate'];
  $reason = $_POST['reason'];
  $status = 'Pending'; // Default status for new leave requests

  // Calculate number of days
  $date1 = new DateTime($fromDate);
  $date2 = new DateTime($toDate);
  $interval = $date1->diff($date2);
  $numDays = $interval->days + 1; // +1 to include both start and end dates

  $query = "INSERT INTO leaves (employeeId, leaveTypeId, fromDate, toDate, reason, status, numDays) VALUES (?, ?, ?, ?, ?, ?, ?)";
  $stmt = $mysqli->prepare($query);
  if ($stmt === false) {
    die('MySQL prepare error: ' . $mysqli->error);
  }
  $stmt->bind_param('iissssi', $employeeId, $leaveTypeId, $fromDate, $toDate, $reason, $status, $numDays);

  if ($stmt->execute()) {
    header("Location: leaves.php?success=" . urlencode("Leave Request Sent Successfully"));
    exit;
  } else {
    echo "<script>alert('Something went wrong. Please try again: ' + $stmt->error);</script>";
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
            <h4 class="page-title">Add Leave</h4>
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
                <a href="leaves.php">Leaves</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Leave</a>
              </li>
            </ul>
          </div>


          <div class="row">

            <div class="col-md-12">
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="">
                    <div class="form-group">
                      <select class="form-control form-select" style="border:1px solid red;" name="employeeId" required>
                        <option value="" selected disabled>Select Employee</option>
                        <?php
                        // $ret = "SELECT id, staffname FROM login WHERE userType = 'employee'"; // Assuming 'login' table has staffname and userType
                        $ret = "SELECT id, staffname FROM login"; // Assuming 'login' table has staffname and userType
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <option value="<?php echo $row->id; ?>"><?php echo $row->staffname; ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" style="border:1px solid red;" name="leaveType" required>
                        <option value="" selected disabled>Select Leave Type</option>
                        <?php
                        $ret = "SELECT id, leaveType FROM leave_types";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <option value="<?php echo $row->id; ?>"><?php echo $row->leaveType; ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" type="date" style="border:1px solid red;" placeholder="From" name="fromDate" required>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" type="date" style="border:1px solid red;" placeholder="To" name="toDate" required>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" readonly="" style="border:1px solid red;" placeholder="Number of days" type="text" name="numDays" id="numDays">
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input class="form-control" readonly="" style="border:1px solid red;" placeholder="Remaining Leaves" value="12" type="text" name="remainingLeaves">
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <textarea rows="4" cols="5" class="form-control" name="reason" style="border:1px solid red;" placeholder="Leave Reason" required></textarea>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary submit-btn btn-round" name="submit">Send Leave Request</button>
                    </div>
                  </form>
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
      function calculateDays() {
        var fromDate = $('input[name="fromDate"]').val();
        var toDate = $('input[name="toDate"]').val();

        if (fromDate && toDate) {
          var start = moment(fromDate, 'DD/MM/YYYY');
          var end = moment(toDate, 'DD/MM/YYYY');
          if (start.isValid() && end.isValid()) {
            var days = end.diff(start, 'days') + 1;
            if (days < 0) {
              days = 0; // Ensure non-negative days
            }
            $('#numDays').val(days);

            // Calculate remaining leaves (assuming 12 total leaves for now)
            var totalLeaves = 12; // This should ideally come from the database
            var remainingLeaves = totalLeaves - days;
            $('input[name="remainingLeaves"]').val(remainingLeaves);
          }
        }
      }

      $('input[name="fromDate"], input[name="toDate"]').on('change', function() {
        calculateDays();
      });

      // Initial calculation if dates are pre-filled (e.g., on edit)
      calculateDays();
    });
  </script>
</body>

</html>