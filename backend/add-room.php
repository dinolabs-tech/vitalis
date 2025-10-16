<?php
session_start();
include_once('database/db_connect.php'); // Include your database connection

// Check if user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Fetch branches for the dropdown
$branches_sql = "SELECT branch_id, branch_name FROM branches";
$branches_result = $conn->query($branches_sql);
$branches = [];
if ($branches_result->num_rows > 0) {
  while ($row = $branches_result->fetch_assoc()) {
    $branches[] = $row;
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
            <h4 class="page-title">Add Room</h4>
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
                <a href="rooms.php">Rooms</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Room</a>
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
                  <form action="code.php" method="POST">
                    <div class="form-group">
                      <input type="text" name="room_number" style="border:1px solid red;" class="form-control" placeholder="Enter Room Number" required>
                    </div>
                    <div class="form-group">
                      <input type="text" name="room_type" style="border:1px solid red;" class="form-control" placeholder="Enter Room Type (e.g., Standard, VIP, ICU)" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="capacity" class="form-control" placeholder="Enter Capacity" value="1" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="room_cost" class="form-control" step="0.01" placeholder="Enter Room Cost (0.00)" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="bed_cost" class="form-control" step="0.01" placeholder="Enter Bed Cost (0.00)" required>
                    </div>
                    <div class="form-group">
                      <select name="status" style="border:1px solid red;" class="form-control form-select" required>
                        <option value="" selected disabled>Status</option>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <select name="branch_id" class="form-control form-select">
                        <option value="" selected disabled>Select Branch</option>
                        <?php foreach ($branches as $branch) : ?>
                          <option value="<?php echo $branch['branch_id']; ?>"><?php echo htmlspecialchars($branch['branch_name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-12 text-center">
                      <a href="rooms.php" class="btn btn-danger btn-icon btn-round"><i class="fas fa-times"></i></a>
                      <button type="submit" name="add_room_btn" class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
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