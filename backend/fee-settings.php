<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Use the global mysqli connection from config.php
global $mysqli;

// Handle form submission for updating fees
if (isset($_POST['submit'])) {
    $bed_space_fee = $_POST['bed_space_fee'];
    $room_fee = $_POST['room_fee'];
    // Add other service fees here

    // Update fees in the database
    // For simplicity, we'll use a single settings table.
    // In a real application, you might have a more complex service/fee structure.
    $stmt = $mysqli->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

    // Bed Space Fee
    $setting_key_bed = 'bed_space_fee';
    $stmt->bind_param('sss', $setting_key_bed, $bed_space_fee, $bed_space_fee);
    $stmt->execute();

    // Room Fee
    $setting_key_room = 'room_fee';
    $stmt->bind_param('sss', $setting_key_room, $room_fee, $room_fee);
    $stmt->execute();

    // Close statement
    $stmt->close();

    $_SESSION['msg'] = "Fees updated successfully!";
}

// Fetch current fee settings
$bed_space_fee = 0;
$room_fee = 0;

$result = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('bed_space_fee', 'room_fee')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] == 'bed_space_fee') {
            $bed_space_fee = $row['setting_value'];
        } elseif ($row['setting_key'] == 'room_fee') {
            $room_fee = $row['setting_value'];
        }
    }
    $result->free();
}

// No need to close $mysqli here as it's a global connection managed by db_connect.php
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
            <h4 class="page-title">Fee Settings</h4>
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
                <a href="#">Fee Settings</a>
              </li>
            </ul>
          </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Manage Service Fees</div>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['msg'])) { ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
                                        </div>
                                    <?php } ?>
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="bed_space_fee">Bed Space Fee</label>
                                            <input type="number" class="form-control" id="bed_space_fee" name="bed_space_fee" value="<?php echo htmlspecialchars($bed_space_fee); ?>" step="0.01" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="room_fee">Room Fee</label>
                                            <input type="number" class="form-control" id="room_fee" name="room_fee" value="<?php echo htmlspecialchars($room_fee); ?>" step="0.01" required>
                                        </div>
                                        <!-- Add more service fee fields here -->
                                        <button type="submit" name="submit" class="btn btn-primary">Update Fees</button>
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
