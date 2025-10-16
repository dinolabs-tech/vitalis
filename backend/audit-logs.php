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
            <h4 class="page-title">Audit Logs</h4>
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
                <a href="#">Audit Logs</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>User</th>
                          <th>Action</th>
                          <th>Module</th>
                          <th>Action Date</th>
                          <th>IP Address</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT * FROM audit_logs";
                        $stmt = $mysqli->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->userName; ?></td>
                            <td><?php echo $row->action; ?></td>
                            <td><?php echo $row->module; ?></td>
                            <td><?php echo $row->actionDate; ?></td>
                            <td><?php echo $row->ipAddress; ?></td>
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
  <?php include('components/script.php'); ?>
</body>

</html>