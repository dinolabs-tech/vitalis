<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();

if (isset($_POST['submit'])) {
  $holidayName = $_POST['holidayName'];
  $holidayDate = $_POST['holidayDate'];

  $query = "INSERT INTO holidays (holidayName, holidayDate) VALUES (?,?)";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ss', $holidayName, $holidayDate);
  $stmt->execute();
  $stmt->close();

  header("Location: holidays.php?success=" . urlencode("Holiday Added Successfully"));
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
            <h4 class="page-title">Add Holiday</h4>
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
                <a href="holidays.php">Holidays</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Holiday</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6><hr>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Holiday Name" type="text" name="holidayName" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Holiday Date" type="date" name="holidayDate" required>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round" type="submit" name="submit"><i class="fas fa-plus"></i></button>
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
</body>

</html>