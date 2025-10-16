<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$tax_id = $_GET['id'] ?? '';
$tax_record = null;

if (!empty($tax_id)) {
  $stmt = $conn->prepare("SELECT * FROM taxes WHERE id = ?");
  $stmt->bind_param("i", $tax_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $tax_record = $result->fetch_assoc();
  } else {
    $error_message = "Tax record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No tax ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tax_record) {
  $tax_name = $_POST['tax_name'] ?? '';
  $tax_rate = $_POST['tax_rate'] ?? '';
  $status = $_POST['status'] ?? 'active';
  $description = $_POST['description'] ?? '';

  if (empty($tax_name) || empty($tax_rate)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE taxes SET tax_name = ?, tax_rate = ?, status = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sdssi", $tax_name, $tax_rate, $status, $description, $tax_id);

    if ($stmt->execute()) {
      $success_message = "Tax updated successfully!";


      // Log audit
      $user_id = $_SESSION['user_id'] ?? null;
      $user_name = $_SESSION['username'] ?? 'Guest';
      $action = "Updated tax record with ID: " . $tax_id . " for tax name: " . $tax_name;
      $details = json_encode($_POST);
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $module = "Taxes";
      $conn->query("INSERT INTO audit_logs (user_id, userName, action, module, ipAddress, details) VALUES ('$user_id', '$user_name', '$action', '$module', '$ip_address', '$details')");

      header("Location: taxes.php");
      exit();
    } else {
      $error_message = "Error updating tax: " . $stmt->error;
    }
    $stmt->close();
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
            <h4 class="page-title">Edit Tax</h4>
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
                <a href="#">Edit Tax</a>
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

                  <?php if ($tax_record): ?>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Tax Name" type="text" name="tax_name" value="<?php echo htmlspecialchars($tax_record['tax_name']); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Tax Rate (%)" type="text" name="tax_rate" value="<?php echo htmlspecialchars($tax_record['tax_rate']); ?>" required>
                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" name="status">
                        <option value="active" <?php echo ($tax_record['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($tax_record['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <textarea class="form-control" placeholder="Description" name="description" rows="3"><?php echo htmlspecialchars($tax_record['description']); ?></textarea>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  <?php else: ?>
                    <p>No tax record to edit.</p>
                  <?php endif; ?>
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