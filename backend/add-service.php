<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Add Service
if (isset($_POST['add_service'])) {
  $service_name = $_POST['service_name'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $department_id = $_POST['department_id'];

  $query = "INSERT INTO services (service_name, description, price, department_id) VALUES (?, ?, ?, ?)";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ssdi', $service_name, $description, $price, $department_id);
  if ($stmt->execute()) {
    $_SESSION['msg'] = "Service added successfully!";
    header('location:services.php');
    exit();
  } else {
    $_SESSION['error'] = "Error adding service: " . $stmt->error;
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
            <h4 class="page-title">Add Service</h4>
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
                <a href="services.php">Services</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Service</a>
              </li>
            </ul>
          </div>

          <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['msg'];
                                              unset($_SESSION['msg']); ?></div>
          <?php endif; ?>
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
          <?php endif; ?>


          <div class="row">

            <div class="col-md-12">
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders wioth red border are compulsory</h6><hr>
                </div>
                <div class="card-body">
                  <form method="POST">
                    <div class="form-group">
                      <input class="form-control" style="border:1px solid red;" placeholder="Service Name" type="text" name="service_name" required>
                    </div>
                    <div class="form-group">
                      <textarea class="form-control" placeholder="Description" name="description"></textarea>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border:1px solid red;" placeholder="Price" type="text" name="price" required>
                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" name="department_id">
                        <option value="" selected disabled>Select Department</option>
                        <?php
                        $dept_query = "SELECT id, name FROM departments";
                        $dept_stmt = $mysqli->prepare($dept_query);
                        $dept_stmt->execute();
                        $dept_result = $dept_stmt->get_result();
                        while ($dept_row = $dept_result->fetch_object()):
                        ?>
                          <option value="<?php echo htmlentities($dept_row->id); ?>"><?php echo htmlentities($dept_row->name); ?></option>
                        <?php endwhile; ?>
                      </select>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round" name="add_service"><i class="fas fa-plus"></i></button>
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