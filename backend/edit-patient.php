<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or receptionist role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$patient_data = [];
$patient_id = $_GET['id'] ?? null;

if ($patient_id) {
  // Fetch existing patient data
  $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
  $stmt->bind_param("s", $patient_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $patient_data = $result->fetch_assoc();
  } else {
    $error_message = "Patient not found.";
  }
  $stmt->close();
} else {
  $error_message = "No patient ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
  $first_name = $_POST['first_name'] ?? '';
  $last_name = $_POST['last_name'] ?? '';
  $date_of_birth = $_POST['date_of_birth'] ?? '';
  $gender = $_POST['gender'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $address = $_POST['address'] ?? '';
  $country = $_POST['country'] ?? '';
  $state = $_POST['state'] ?? '';
  $blood_group = $_POST['blood_group'] ?? '';
  $genotype = $_POST['genotype'] ?? '';
  $allergies = $_POST['allergies'] ?? '';
  $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
  $emergency_contact_phone = $_POST['emergency_contact_phone'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender) || empty($phone) || empty($address)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Update patients table
      $stmt_patient = $conn->prepare("UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, email = ?, phone = ?, address = ?, country = ?, state = ?, blood_group = ?, genotype = ?, allergies = ?, emergency_contact_name = ?, emergency_contact_phone = ?, branch_id = ? WHERE patient_id = ?");
      $stmt_patient->bind_param(
        "ssssssssssssssis",
        $first_name,
        $last_name,
        $date_of_birth,
        $gender,
        $email,
        $phone,
        $address,
        $country,
        $state,
        $blood_group,
        $genotype,
        $allergies,
        $emergency_contact_name,
        $emergency_contact_phone,
        $branch_id,
        $patient_id
      );

      if (!$stmt_patient->execute()) {
        throw new Exception("Error updating patient record: " . $stmt_patient->error);
      }
      $stmt_patient->close();

      $conn->commit();
      $success_message = "Patient updated successfully!";
      // Refresh patient data after update
      header("Location: edit-patient.php?id=" . $patient_id . "&success=true");
      exit;
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to update patient: " . $e->getMessage();
    }
  }
}

// If redirected with success message
if (isset($_GET['success']) && $_GET['success'] === 'true') {
  $success_message = "Patient updated successfully!";
  header("Location: patients.php");
  exit();
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
            <h4 class="page-title">Edit Patient</h4>
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
                <a href="patients.php">Patients</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Patient</a>
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
                    <h6 class="text-danger mx-4 mt-3 small">All placeholders with red border are compulsory</h6>
                    <hr>
                  </div>
                  <div class="card-body">
                    <form method="POST" action="" class="row g-3">

                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" name="first_name" placeholder="First Name" style="border: 1px solid red;" value="<?php echo htmlspecialchars($patient_data['first_name'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" name="last_name" placeholder="Last Name" style="border: 1px solid red;" value="<?php echo htmlspecialchars($patient_data['last_name'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input type="date" class="form-control" placeholder="Date of Birth" name="date_of_birth" style="border: 1px solid red;" value="<?php echo htmlspecialchars($patient_data['date_of_birth'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <select name="gender" id="" style="border: 1px solid red;" class="form-control form-select">
                            <option value="" selected disabled>Select Gender</option>
                            <option value="Male" <?php echo (isset($patient_data['gender']) && $patient_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($patient_data['gender']) && $patient_data['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($patient_data['email'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Phone" style="border: 1px solid red;" name="phone" value="<?php echo htmlspecialchars($patient_data['phone'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Address" style="border: 1px solid red;" name="address" value="<?php echo htmlspecialchars($patient_data['address'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Country" name="country" value="<?php echo htmlspecialchars($patient_data['country'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="State" name="state" value="<?php echo htmlspecialchars($patient_data['state'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Blood Group" name="blood_group" value="<?php echo htmlspecialchars($patient_data['blood_group'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" placeholder="Genotype" type="text" name="genotype" value="<?php echo htmlspecialchars($patient_data['genotype'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Allergies" name="allergies"><?php echo htmlspecialchars($patient_data['allergies'] ?? ''); ?></textarea>
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Emergency Contact Name" name="emergency_contact_name" value="<?php echo htmlspecialchars($patient_data['emergency_contact_name'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Emergency Contact Phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($patient_data['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-sm-6">
                        <div class="form-group">
                          <select class="form-control form-select" name="branch_id">
                            <option value="" selected disabled>Select Branch</option>
                            <?php foreach ($branches as $branch): ?>
                              <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($patient_data['branch_id']) && $patient_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($branch['branch_name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-12 mt-3 text-center">
                        <button class="btn btn-primary btn-icon btn-round text-white"><i class="fas fa-save"></i></button>
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