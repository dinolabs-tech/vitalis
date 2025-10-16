<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();

if (isset($_POST['submit'])) {
  $leaveType = $_POST['leaveType'];
  $leaveDays = $_POST['leaveDays'];
  $status = 'Active'; // Default status for new leave types

  $query = "INSERT INTO leave_types (leaveType, leaveDays, status) VALUES (?, ?, ?)";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('sis', $leaveType, $leaveDays, $status);
  $stmt->execute();
  $stmt->close();

  if ($stmt) {
    header("Location: leave-type.php?success=" . urlencode("Leave Type Added Successfully"));
    exit;
  } else {
    echo "<script>alert('Something went wrong. Please try again.');</script>";
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
            <h4 class="page-title">Add Leave Type</h4>
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
                <a href="leave-type.php">Leave Type</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Leave Type</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <label for="" class="text-danger ms-2">All placeholders with red border are compulsory</label>
              <div class="col-12">
                <form method="POST" action="">
                  <div class="form-group">
                    <input class="form-control" placeholder="Leave Type" style="border:1px solid red;" type="text" name="leaveType" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control" type="text" placeholder="Number of days" style="border:1px solid red;" name="leaveDays" required>
                  </div>
                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round" name="submit"><i class="fas fa-plus"></i></button>
                  </div>
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
</body>

</html>