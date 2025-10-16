<?php
include 'includes/config.php';
include 'includes/checklogin.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tax_name = $_POST['tax_name'] ?? '';
  $tax_rate = $_POST['tax_rate'] ?? '';
  $status = $_POST['status'] ?? 'active';
  $description = $_POST['description'] ?? '';

  if (empty($tax_name) || empty($tax_rate)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Validate tax_rate is a number
    if (!is_numeric($tax_rate) || $tax_rate <= 0) {
      $error_message = "Tax rate must be a positive number.";
    } else {
      $stmt = $mysqli->prepare("INSERT INTO taxes (tax_name, tax_rate, status, description) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("sdss", $tax_name, $tax_rate, $status, $description);

      if ($stmt->execute()) {
        $success_message = "Tax added successfully!";
        // Log audit
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['username'] ?? 'Guest';
        $action = "Added tax: " . $tax_name . " (Rate: " . $tax_rate . "%)";
        $details = json_encode($_POST);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $module = "Taxes";
        $mysqli->query("INSERT INTO audit_logs (user_id, userName, action, module, ipAddress, details) VALUES ('$user_id', '$user_name', '$action', '$module', '$ip_address', '$details')");

        // Clear form fields after successful submission
        $_POST = array();
        header("Location: taxes.php?success=" . urlencode($success_message));
        exit();
      } else {
        $error_message = "Error adding tax: " . $stmt->error;
      }
      $stmt->close();
    }
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
            <h4 class="page-title">Add Tax</h4>
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
                <a href="taxes.php">Taxes</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Tax</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
                <form method="POST" action="">
                  <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <?php echo $error_message; ?>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                  <?php endif; ?>
                  <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <?php echo $success_message; ?>
                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                      </button>
                    </div>
                  <?php endif; ?>
                  <label for="" class="text-danger ms-2">All placeholders with red border are compulsory</label>
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Tax Name" type="text" name="tax_name" value="<?php echo htmlspecialchars($_POST['tax_name'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Tax Rate (%)" type="text" name="tax_rate" value="<?php echo htmlspecialchars($_POST['tax_rate'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <select class="form-control form-select" name="status">
                      <option value="" selected disabled>Status</option>
                      <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                      <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <textarea class="form-control" placeholder="Description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                  </div>
                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round" name="add_tax"><i class="fas fa-plus"></i></button>
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