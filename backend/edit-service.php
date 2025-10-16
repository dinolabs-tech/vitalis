<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

$service_id = 0;
$service_name = '';
$description = '';
$price = '';
$department_id = '';

if (isset($_GET['id'])) {
  $service_id = intval($_GET['id']);
  $query = "SELECT * FROM services WHERE id = ?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $service_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $service = $result->fetch_object();
    $service_name = $service->service_name;
    $description = $service->description;
    $price = $service->price;
    $department_id = $service->department_id;
  } else {
    $_SESSION['error'] = "Service not found!";
    header('location:services.php');
    exit();
  }
  $stmt->close();
}

// Edit Service
if (isset($_POST['edit_service'])) {
  $id = $_POST['id'];
  $service_name = $_POST['service_name'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $department_id = $_POST['department_id'];

  $query = "UPDATE services SET service_name=?, description=?, price=?, department_id=? WHERE id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ssdsi', $service_name, $description, $price, $department_id, $id);
  if ($stmt->execute()) {
    $_SESSION['msg'] = "Service updated successfully!";
    header('location:services.php');
    exit();
  } else {
    $_SESSION['error'] = "Error updating service: " . $stmt->error;
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
            <h4 class="page-title">Edit Service</h4>
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
                <a href="#">Edit Service</a>
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
                  <form method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlentities($service_id); ?>">
                    <div class="form-group">
                      <input class="form-control" placeholder="Service Name" style="border:1px solid red;" type="text" name="service_name" value="<?php echo htmlentities($service_name); ?>" required>
                    </div>
                    <div class="form-group">
                      <textarea class="form-control" placeholder="Description" name="description"><?php echo htmlentities($description); ?></textarea>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border:1px solid red;" placeholder="Price" type="text" name="price" value="<?php echo htmlentities($price); ?>" required>
                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" name="department_id">
                        <option value="" disabled>Select Department</option>
                        <?php
                        $dept_query = "SELECT id, name FROM departments";
                        $dept_stmt = $mysqli->prepare($dept_query);
                        $dept_stmt->execute();
                        $dept_result = $dept_stmt->get_result();
                        while ($dept_row = $dept_result->fetch_object()):
                        ?>
                          <option value="<?php echo htmlentities($dept_row->id); ?>" <?php if ($dept_row->id == $department_id) echo 'selected'; ?>><?php echo htmlentities($dept_row->name); ?></option>
                        <?php endwhile; ?>
                      </select>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round" name="edit_service"><i class="fas fa-save"></i></button>
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