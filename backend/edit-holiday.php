<?php
session_start();
require_once 'database/db_connect.php'; // Standardize database connection

// Assuming checklogin() and config.php are for authentication/session management
// If check_login() is critical, it needs to be adapted or its functionality replaced.
// For now, I'll comment it out to avoid errors if includes/config.php is not found.
// include('includes/checklogin.php');
// check_login();

$holiday = null;
$error_message = '';
$success_message = '';

// Handle form submission for updating holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $holiday_id = $_POST['holiday_id'] ?? null;
    $holiday_name_post = $_POST['holiday_name'] ?? null;
    $holiday_date_post = $_POST['holiday_date'] ?? null; // Already in YYYY-MM-DD from input type="date"

    if ($holiday_id && $holiday_name_post && $holiday_date_post) {
        $stmt = $conn->prepare("UPDATE holidays SET holidayName = ?, holidayDate = ? WHERE id = ?");
        $stmt->bind_param("ssi", $holiday_name_post, $holiday_date_post, $holiday_id);

        if ($stmt->execute()) {
            $success_message = "Holiday updated successfully!";
            // Redirect to refresh the page with success message
            header("Location: holidays.php");
            exit();
        } else {
            $error_message = "Error updating holiday: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "All fields are required.";
    }
}

// Fetch existing holiday data if ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $holiday_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT id, holidayName, holidayDate FROM holidays WHERE id = ?");
    $stmt->bind_param("i", $holiday_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $holiday = $result->fetch_assoc();
    } else {
        $error_message = "Holiday not found.";
    }
    $stmt->close();
} else if (!$_SERVER['REQUEST_METHOD'] === 'POST') {
    $error_message = "No holiday ID provided.";
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
              <h4 class="page-title">Edit Holiday</h4>
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
                  <a href="holidays.php">Holidays</a>
                </li>
                <li class="separator">
                  <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                  <a href="#">Edit Holiday</a>
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
                                <input type="hidden" name="holiday_id" value="<?php echo $holiday['id'] ?? ''; ?>">
                                <div class="form-group">
                                    <input class="form-control" name="holiday_name" style="border: 1px solid red;" placeholder="Holiday Name" value="<?php echo $holiday['holidayName'] ?? ''; ?>" type="text" required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" name="holiday_date" style="border: 1px solid red;" placeholder="Holiday Date" value="<?php echo $holiday['holidayDate'] ?? ''; ?>" type="date" required>
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
