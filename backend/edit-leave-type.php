<?php
session_start();
require_once 'database/db_connect.php'; // Adjust path as necessary

$leave_type = null;
$error_message = '';
$success_message = '';

// Handle form submission for updating leave type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $leave_type_id = $_POST['leave_type_id'] ?? null;
  $leave_type_name = $_POST['leave_type'] ?? null;
  $leave_days = $_POST['leave_days'] ?? null;
  $description = $_POST['description'] ?? null; // Assuming a description field might be added later or exists

  if ($leave_type_id && $leave_type_name && $leave_days !== null) {
    $stmt = $conn->prepare("UPDATE leave_types SET leaveType = ?, leaveDays = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sisi", $leave_type_name, $leave_days, $description, $leave_type_id);

    if ($stmt->execute()) {
      $success_message = "Leave type updated successfully!";
      // Refresh the leave type data after update
      header("Location: leave-type.php");
      exit();
    } else {
      $error_message = "Error updating leave type: " . $stmt->error;
    }
    $stmt->close();
  } else {
    $error_message = "All fields are required.";
  }
}

// Fetch existing leave type data if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $leave_type_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM leave_types WHERE id = ?");
  $stmt->bind_param("i", $leave_type_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $leave_type = $result->fetch_assoc();
  } else {
    $error_message = "Leave type not found.";
  }
  $stmt->close();
} else if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
  $error_message = "No leave type ID provided.";
}

// Check for success message in GET parameters after redirect
if (isset($_GET['success'])) {
  $success_message = htmlspecialchars($_GET['success']);
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
            <h4 class="page-title">Edit Leave Type</h4>
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
                <a href="leave-type.php">Leave Type</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Leave Type</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
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
                <form method="POST" action="">
                  <input type="hidden" name="leave_type_id" value="<?php echo $leave_type['id'] ?? ''; ?>">
                  <div class="form-group">
                    <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Leave Type" name="leave_type" value="<?php echo $leave_type['leaveType'] ?? ''; ?>" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control" type="text" name="leave_days" style="border: 1px solid red;" placeholder="Number of days" value="<?php echo $leave_type['leaveDays'] ?? ''; ?>" required>
                  </div>
                  <div class="form-group">
                    <textarea rows="4" cols="5" class="form-control" placeholder="Description" name="description"><?php echo $leave_type['description'] ?? ''; ?></textarea>
                  </div>
                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round" type="submit"><i class="fas fa-save"></i></button>
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