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
$doctor_data = [];
$staff_id = $_GET['id'] ?? null;

if ($staff_id) {
  // Fetch existing doctor data
  $stmt = $conn->prepare("SELECT l.id as staff_id, l.staffname, l.username, l.email, l.mobile, l.address, l.country, l.state, d.specialization, d.license_number, d.branch_id 
                            FROM login l 
                            JOIN doctors d ON l.id = d.staff_id 
                            WHERE l.id = ? AND l.role = 'doctor'");
  $stmt->bind_param("i", $staff_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $doctor_data = $result->fetch_assoc();
  } else {
    $error_message = "Doctor not found.";
  }
  $stmt->close();
} else {
  $error_message = "No doctor ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $staff_id) {
  $staffname = $_POST['staffname'] ?? '';
  $username = $_POST['username'] ?? '';
  $email = $_POST['email'] ?? '';
  $mobile = $_POST['mobile'] ?? '';
  $address = $_POST['address'] ?? '';
  $country = $_POST['country'] ?? '';
  $state = $_POST['state'] ?? '';
  $specialization = $_POST['specialization'] ?? '';
  $license_number = $_POST['license_number'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($staffname) || empty($username) || empty($email) || empty($specialization) || empty($license_number)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Update login table
      $stmt_login = $conn->prepare("UPDATE login SET staffname = ?, username = ?, email = ?, mobile = ?, address = ?, country = ?, state = ? WHERE id = ? AND role = 'doctor'");
      $stmt_login->bind_param("sssssssi", $staffname, $username, $email, $mobile, $address, $country, $state, $staff_id);

      if (!$stmt_login->execute()) {
        throw new Exception("Error updating login account: " . $stmt_login->error);
      }
      $stmt_login->close();

      // Update doctors table
      $stmt_doctor = $conn->prepare("UPDATE doctors SET specialization = ?, license_number = ?, branch_id = ? WHERE staff_id = ?");
      $stmt_doctor->bind_param("ssii", $specialization, $license_number, $branch_id, $staff_id);

      if (!$stmt_doctor->execute()) {
        throw new Exception("Error updating doctor profile: " . $stmt_doctor->error);
      }
      $stmt_doctor->close();

      $conn->commit();
      $success_message = "Doctor updated successfully!";
      // Refresh doctor data after update
      header("Location: doctors.php");
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update doctor: " . $e->getMessage();
    }
  }
}

// If redirected with success message
if (isset($_GET['success']) && $_GET['success'] === 'true') {
  $success_message = "Doctor updated successfully!";
  // Re-fetch data to show updated values
  $stmt = $conn->prepare("SELECT l.id as staff_id, l.staffname, l.username, l.email, l.mobile, l.address, l.country, l.state, d.specialization, d.license_number, d.branch_id 
                            FROM login l 
                            JOIN doctors d ON l.id = d.staff_id 
                            WHERE l.id = ? AND l.role = 'doctor'");
  $stmt->bind_param("i", $staff_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $doctor_data = $result->fetch_assoc();
  }
  $stmt->close();
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
            <h4 class="page-title">Edit Doctor</h4>
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
                <a href="#">Edit Doctor</a>
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
                        <input class="form-control" type="text" placeholder="Full Name" style="border: 1px solid red;" name="staffname" value="<?php echo htmlspecialchars($doctor_data['staffname'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="username" placeholder="Username" style="border: 1px solid red;" value="<?php echo htmlspecialchars($doctor_data['username'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="email" name="email" placeholder="Email" style="border: 1px solid red;" value="<?php echo htmlspecialchars($doctor_data['email'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="mobile" placeholder="Mobile" value="<?php echo htmlspecialchars($doctor_data['mobile'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="address" placeholder="Address" value="<?php echo htmlspecialchars($doctor_data['address'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Country" name="country" value="<?php echo htmlspecialchars($doctor_data['country'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="State" name="state" value="<?php echo htmlspecialchars($doctor_data['state'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Specialization" style="border: 1px solid red;" name="specialization" value="<?php echo htmlspecialchars($doctor_data['specialization'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="license_number" placeholder="License Number" style="border: 1px solid red;" value="<?php echo htmlspecialchars($doctor_data['license_number'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div class="form-group">
                        <select class="form-control" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($doctor_data['branch_id']) && $doctor_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-2 mt-2">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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