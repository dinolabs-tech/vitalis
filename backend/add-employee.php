<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
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
  $role = $_POST['role'] ?? '';
  $specialization = $_POST['specialization'] ?? '';
  $license_number = $_POST['license_number'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  if (empty($staffname) || empty($username) || empty($email) || empty($password) || empty($role)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO login (staffname, username, password, email, address, mobile, country, state, role, specialization, license_number, branch_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssssssssssi", $staffname, $username, $hashed_password, $email, $address, $mobile, $country, $state, $role, $specialization, $license_number, $branch_id);

    if ($stmt->execute()) {
      $success_message = "Employee added successfully!";
      $_POST = array();
      header("Location: employees.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Failed to add employee: " . $stmt->error;
    }
    $stmt->close();
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
            <h4 class="page-title">Add Employee</h4>
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
                <a href="employees.php">Employees</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Employee</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
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
              <div class="card">
                <div class="card-title m-3">
                  <div class="text-danger ms-5"><small>All placeholders with red border are compulsory</small></div>
                  <hr>
                </div>

                <div class="card-body">
                  <form method="POST" action="" class="row g-3">

                    <div class="col-md-6 mb-3">
                      <input class="form-control" type="text" name="staffname" placeholder="Full Name" style="border: 1px solid red;" value="<?php echo htmlspecialchars($_POST['staffname'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" type="text" placeholder="Username" style="border: 1px solid red;" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" type="email" placeholder="Email" style="border: 1px solid red;" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" type="password" placeholder="Password" style="border: 1px solid red;" name="password" required>
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" placeholder="Mobile" type="text" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" placeholder="Address" type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" placeholder="Country" type="text" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                      <input class="form-control" type="text" placeholder="State" name="state" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                      <select class="form-control form-select" style="border:1px solid red;" name="role" required>
                        <option value="" selected disabled>Select Role</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="doctor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'doctor') ? 'selected' : ''; ?>>Doctor</option>
                        <option value="nurse" <?php echo (isset($_POST['role']) && $_POST['role'] == 'nurse') ? 'selected' : ''; ?>>Nurse</option>
                        <option value="pharmacist" <?php echo (isset($_POST['role']) && $_POST['role'] == 'pharmacist') ? 'selected' : ''; ?>>Pharmacist</option>
                        <option value="lab_technician" <?php echo (isset($_POST['role']) && $_POST['role'] == 'lab_technician') ? 'selected' : ''; ?>>Lab Technician</option>
                        <option value="receptionist" <?php echo (isset($_POST['role']) && $_POST['role'] == 'receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                      </select>
                    </div>

                    <div class="col-md-6 mb-3">
                      <select name="specialization" class="form-control form-select">
                        <option value="" selected disabled>Select Specialization</option>
                        <option value="cardiology">Cardiology</option>
                        <option value="dermatology">Dermatology</option>
                        <option value="endocrinology">Endocrinology</option>
                        <option value="gastronterology">Gastronterology</option>
                        <option value="neurology">Neurology</option>
                        <option value="oncology">Oncology</option>
                        <option value="pediatrics">Pediatrics</option>
                        <option value="psychiatry">Psychiatry</option>
                        <option value="radiology">Radiology</option>
                        <option value="surgery">Surgery</option>
                        <option value="gynaecology">Gynaecology</option>
                        <option value="entymology">Entymology</option>
                        <option value="ophthalmology">Ophthalmology</option>
                        <option value="dentist">Dentist</option>
                        <option value="general_practice">General Practice</option>
                      </select>
                    </div>

                    <div class="col-md-6 mb-3">
                      <input type="text" placeholder="License Number" class="form-control" name="license_number">
                    </div>

                    <div class="col-md-6 mb-3">
                      <select class="form-control form-select" name="branch_id">
                        <option value="" selected disabled>Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                          <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-12 text-center">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-plus"></i></button>
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