<?php
session_start();
include_once('database/db_connect.php'); // Include your database connection

// Check if user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$room_id = $_GET['id'] ?? null;
$room = null;

if ($room_id) {
  $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
  $stmt->bind_param("i", $room_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();
  }
  $stmt->close();
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

if (!$room) {
  echo "<div class='container-fluid'><p class='text-danger'>Room not found!</p></div>";
  include('components/script.php'); // Only include script and close body/html if room not found
  echo "</body></html>";
  exit();
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
            <h4 class="page-title">Edit Room</h4>
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
                <a href="#">Edit Room</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form action="code.php" method="POST">
                    <input type="hidden" name="edit_room_id" value="<?php echo $room['id']; ?>">
                    <div class="form-group">
                      <input type="text" name="room_number" style="border:1px solid red;" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>" placeholder="Enter Room Number" required>
                    </div>
                    <div class="form-group">
                      <input type="text" name="room_type" style="border:1px solid red;" class="form-control" value="<?php echo htmlspecialchars($room['room_type']); ?>" placeholder="Enter Room Type (e.g., Standard, VIP, ICU)" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($room['capacity']); ?>" placeholder="Enter Capacity" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="room_cost" class="form-control" step="0.01" value="<?php echo htmlspecialchars($room['room_cost']); ?>" placeholder="Enter Room Cost" required>
                    </div>
                    <div class="form-group">
                      <input type="number" name="bed_cost" class="form-control" step="0.01" value="<?php echo htmlspecialchars($room['bed_cost']); ?>" placeholder="Enter Bed Cost" required>
                    </div>
                    <div class="form-group">
                      <select name="status" style="border:1px solid red;" class="form-control form-select" required>
                        <option value="available" <?php echo ($room['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo ($room['status'] == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo ($room['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <select name="branch_id" class="form-control form-select">
                        <option value="" selected disabled>Select Branch</option>
                        <?php foreach ($branches as $branch) : ?>
                          <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($room['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="m-t-20 text-center">
                      <a href="rooms.php" class="btn btn-danger btn-icon btn-round"><i class="fas fa-window-close"></i></a>
                      <button type="submit" name="update_room_btn" class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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