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
            <h4 class="page-title">Add Provident Fund</h4>
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
                <a href="provident-fund.php">Provident Fund</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Provident Fund</a>
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
                  <form action="create-provident-fund.php" method="post" class="row">

                    <div class="form-group col-md-12">
                      <select name="employeeId" style="border: 1px solid red;" class="form-control form-select searchable-dropdown mt-5" required>
                        <option value="" selected disabled>Select Employee</option>
                        <?php
                        $ret = "SELECT * FROM login";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <option value="<?php echo $row->id; ?>"><?php echo $row->staffname; ?></option>
                        <?php } ?>
                      </select>
                    </div>


                    <div class="form-group col-md-3">
                      <input type="text" class="form-control" name="providentFundAmount" style="border: 1px solid red;" placeholder="Provodent Fund Amount">
                    </div>


                    <div class="form-group col-md-3">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Employee Share" type="text" name="employee_share" value="" required>
                    </div>


                    <div class="form-group col-md-3">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Organization Share" type="text" name="organization_share" value="" required>
                    </div>


                    <div class="form-group col-md-3">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Total Share" type="text" name="total_share" value="" required>
                    </div>


                    <div class="form-group col-md-12">
                      <textarea class="form-control" placeholder="Notes" name="notes" rows="3"></textarea>
                    </div>


                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary btn-icon btn-round" type="submit"><i class="fas fa-plus"></i></button>
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