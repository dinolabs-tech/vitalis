<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php';
session_start();
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
            <h4 class="page-title">Salary View</h4>
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
                <a href="salary.php">Salary</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Salary View</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <?php
                  if (!isset($_GET['id'])) { ?>
                    <script>alert('No salary ID provided.');</script>
                    <script>window.location.href='salary.php';</script>
                    <?php exit(); ?>
                 <?php }
                  $sid = intval($_GET['id']);
                  $ret = "SELECT s.*, e.staffname AS employeeName, e.username AS empId, e.email, e.mobile AS phone, e.address FROM salary s JOIN login e ON s.employee_id = e.id WHERE s.id=?";
                  $stmt = $mysqli->prepare($ret);
                  $stmt->bind_param('i', $sid);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  while ($row = $res->fetch_object()) {
                  ?>
                    <div class="invoice-content">
                      <div class="invoice-item">
                        <div class="row">
                          <div class="col-md-6">
                            <div class="invoice-info">
                              <strong class="card-title">Employee Information</strong><hr>
                              <p class="invoice-details invoice-details-two">
                                <?php echo $row->employeeName; ?><br>
                                Employee ID: <?php echo $row->empId; ?><br>
                                Email: <?php echo $row->email; ?><br>
                                Phone: <?php echo $row->phone; ?><br>
                                Address: <?php echo $row->address; ?><br>
                              </p>
                            </div>
                          </div>
                          
                          
                          <div class="col-md-6">
                            <div class="invoice-info invoice-info-2">
                              <strong class="card-title">Salary Details</strong><hr>
                              <p class="invoice-details">
                                Payment Date: <?php echo $row->salary_date; ?><br>
                                Basic Salary: $<?php echo $row->basic_salary; ?><br>
                                Allowances: $<?php echo $row->total_additions; ?><br>
                                Deductions: $<?php echo $row->total_deductions; ?><br>
                                Net Salary: $<?php echo $row->net_salary; ?><br>
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php } ?>
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