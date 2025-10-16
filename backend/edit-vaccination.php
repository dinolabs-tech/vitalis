<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or nurse role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'nurse')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$edit_vaccination_data = [];

// Fetch vaccination data for editing
if (isset($_GET['id'])) {
  $vaccination_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM vaccinations WHERE id = ?");
  $stmt->bind_param("i", $vaccination_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_vaccination_data = $result->fetch_assoc();
  } else {
    $error_message = "Vaccination record not found for editing.";
  }
  $stmt->close();
} else {
  $error_message = "No Vaccination ID provided for editing.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaccination_id'])) {
  $vaccination_id = $_POST['vaccination_id'];
  $patient_id = $_POST['patient_id'] ?? '';
  $vaccine_name = $_POST['vaccine_name'] ?? '';
  $administration_date = $_POST['administration_date'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($patient_id) || empty($vaccine_name) || empty($administration_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Update vaccinations table
    $stmt = $conn->prepare("UPDATE vaccinations SET patient_id = ?, vaccine_name = ?, administration_date = ?, notes = ?, branch_id = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $patient_id, $vaccine_name, $administration_date, $notes, $branch_id, $vaccination_id);

    if ($stmt->execute()) {
      $success_message = "Vaccination record updated successfully!";
      header("Location: vaccinations.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to update vaccination record: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
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
            <h4 class="page-title">Edit Vaccination</h4>
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
                <a href="vaccinations.php">Vaccinations</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Vaccination</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <dic class="card-body">
                  <form method="POST" action="" class="row">

                    <input type="hidden" name="vaccination_id" value="<?php echo htmlspecialchars($edit_vaccination_data['id'] ?? ''); ?>">

                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                          <option value="">Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($edit_vaccination_data['patient_id']) && $edit_vaccination_data['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name'] . ' (' . $patient['patient_id'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Vaccine Name" type="text" name="vaccine_name" value="<?php echo htmlspecialchars($edit_vaccination_data['vaccine_name'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Administration Date" type="date" name="administration_date" value="<?php echo htmlspecialchars($edit_vaccination_data['administration_date'] ?? ''); ?>" required>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($edit_vaccination_data['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" name="branch_id">
                          <option value="">Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($edit_vaccination_data['branch_id']) && $edit_vaccination_data['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-12 mt-3 text-center">
                      <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>

                  </form>
                </dic>
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