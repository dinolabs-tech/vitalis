<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$employee_id = '';
$employee_name = '';
$email = '';
$mobile = '';
$address = '';
$country = '';
$state = '';
$role = '';
$branch = '';
$error_message = '';
$success_message = '';

// Fetch appointment data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $employee_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM login WHERE id = ?");
  $stmt->bind_param("i", $employee_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $employee_id = $employee['id'];
    $staff_name = $employee['staffname'];
    $email = $employee['email'];
    $mobile = $employee['mobile'];
    $country = $employee['country'];
    $role = $employee['role'];
    $address = $employee['address'];
    $state = $employee['state'];
    $branch = $employee['branch_id'];
  } else {
    $error_message = "Employee not found.";
  }
  $stmt->close();
}


// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';
  $employee_name = isset($_POST['staffname']) ? $_POST['staffname'] : '';
  $email = isset($_POST['email']) ? $_POST['email'] : '';
  $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : '';
  $address = isset($_POST['address']) ? $_POST['address'] : '';
  $country = isset($_POST['country']) ? $_POST['country'] : '';
  $state = isset($_POST['state']) ? $_POST['state'] : '';
  $role = isset($_POST['role']) ? $_POST['role'] : '';
  $branch = isset($_POST['branch']) ? $_POST['branch'] : '';

  $stmt = $conn->prepare("UPDATE login SET staffname = ?, email = ?, mobile = ?, address = ?, country = ?, state = ?, role = ?, branch_id = ? WHERE id = ?");
  $stmt->bind_param("ssssssssi", $employee_name, $email, $mobile, $address, $country, $state, $role, $branch, $employee_id);

  if ($stmt->execute()) {
    $success_message = "Employee updated successfully!";
    header("Location: employees.php");
    exit();
  } else {
    $error_message = "Error updating Employee: " . $conn->error;
  }
  $stmt->close();
}

$branches = $conn->query("SELECT * FROM branches");

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
            <h4 class="page-title">Edit Employee</h4>
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
                <a href="#">Edit Employee</a>
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
                <div class="card-title">
                  <h6 class="text-danger mx-4 mt-3"><small>All placeholders with red border are compulsory</small></h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">

                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="staffname" style="border:1px solid red;" placeholder="Full Name" value="<?php echo htmlspecialchars($employee['staffname'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Username" name="username" disabled value="<?php echo htmlspecialchars($employee['username'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="email" style="border:1px solid red;" placeholder="Email" name="email" value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Mobile" name="mobile" value="<?php echo htmlspecialchars($employee['mobile'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Address" name="address" value="<?php echo htmlspecialchars($employee['address'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Country" name="country" value="<?php echo htmlspecialchars($employee['country'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="State" type="text" name="state" value="<?php echo htmlspecialchars($employee['state'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border:1px solid red;" name="role" required>
                          <option value="">Select Role</option>
                          <option value="admin" <?php if ($employee['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                          <option value="doctor" <?php if ($employee['role'] === 'doctor') echo 'selected'; ?>>Doctor</option>
                          <option value="nurse" <?php if ($employee['role'] === 'nurse') echo 'selected'; ?>>Nurse</option>
                          <option value="pharmacist" <?php if ($employee['role'] === 'pharmacist') echo 'selected'; ?>>Pharmacist</option>
                          <option value="lab_technician" <?php if ($employee['role'] === 'lab_technician') echo 'selected'; ?>>Lab Technician</option>
                          <option value="receptionist" <?php if ($employee['role'] === 'receptionist') echo 'selected'; ?>>Receptionist</option>

                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch">
                          <option value="">Select Branch</option>

                          <?php while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php if ($employee['branch_id'] == $branch['branch_id']) echo 'selected'; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mt-3">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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