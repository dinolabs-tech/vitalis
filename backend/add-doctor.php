<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or doctor role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $staffname = $_POST['staffname'] ?? '';
  $username = $_POST['username'] ?? '';
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  $mobile = $_POST['mobile'] ?? '';
  $address = $_POST['address'] ?? '';
  $country = $_POST['country'] ?? '';
  $state = $_POST['state'] ?? '';
  $specialization = $_POST['specialization'] ?? '';
  $license_number = $_POST['license_number'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null; // Assuming branch_id can be null or selected from a dropdown

  // Basic validation
  if (empty($staffname) || empty($username) || empty($email) || empty($password) || empty($specialization) || empty($license_number)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'doctor'; // Default role for this page

    // Start transaction
    $conn->begin_transaction();

    try {
      // Insert into login table
      $stmt_login = $conn->prepare("INSERT INTO login (staffname, username, password, email, address, mobile, country, state, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
      $stmt_login->bind_param("sssssssss", $staffname, $username, $hashed_password, $email, $address, $mobile, $country, $state, $role);

      if (!$stmt_login->execute()) {
        throw new Exception("Error creating login account: " . $stmt_login->error);
      }
      $staff_id = $stmt_login->insert_id;
      $stmt_login->close();

      if (!$stmt_doctor->execute()) {
        throw new Exception("Error creating doctor profile: " . $stmt_doctor->error);
      }
      $stmt_doctor->close();

      $conn->commit();
      $success_message = "Doctor added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: doctors.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to add doctor: " . $e->getMessage();
    }
  }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
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
            <h4 class="page-title">Add Doctor</h4>
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
                <a href="doctors.php">Doctors</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Doctor</a>
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
                  <div class="row">
                    <label for="" class="text-danger ms-2">All placeholders with red border are compulsory</label>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" type="text" name="staffname" value="<?php echo htmlspecialchars($_POST['staffname'] ?? ''); ?>" placeholder="Full Name">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Username">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="Email">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" type="password" name="password" placeholder="Password">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" placeholder="Mobile">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Address">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" placeholder="Country">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="state" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>" placeholder="State">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="specialization" value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>" style="border: 1px solid red;" placeholder="Specialization">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="license_number" value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>" style="border: 1px solid red;" placeholder="License Number">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control" name="branch_id" placeholder="Branch">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6 mt-2">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
                    </div>
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