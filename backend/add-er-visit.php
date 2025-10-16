<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $arrival_time = $_POST['arrival_time'] ?? '';
  $chief_complaint = $_POST['chief_complaint'] ?? '';
  $triage_level = $_POST['triage_level'] ?? 'non_urgent'; // Default value
  $discharge_time = !empty($_POST['discharge_time']) ? $_POST['discharge_time'] : null;
  $initial_findings = $_POST['initial_findings'] ?? null;
  $subsequent_care = $_POST['subsequent_care'] ?? null;
  $outcome = $_POST['outcome'] ?? null;
  $branch_id = $_SESSION['branch_id'] ?? null; // Assuming branch_id is in session

  // Basic validation
  if (empty($patient_id) || empty($arrival_time) || empty($chief_complaint)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Start transaction
    $conn->begin_transaction();

    try {
      // Insert into er_visits table
      $stmt = $conn->prepare("INSERT INTO er_visits (patient_id, arrival_time, chief_complaint, triage_level, discharge_time, initial_findings, subsequent_care, outcome, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param(
        "ssssssssi", // s for string, i for integer (for branch_id)
        $patient_id,
        $arrival_time,
        $chief_complaint,
        $triage_level,
        $discharge_time,
        $initial_findings,
        $subsequent_care,
        $outcome,
        $branch_id
      );

      if (!$stmt->execute()) {
        throw new Exception("Error adding ER visit record: " . $stmt->error);
      }
      $stmt->close();

      $conn->commit();
      $success_message = "ER visit record added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: er_visits.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to add ER visit record: " . $e->getMessage();
    }
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
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
            <h4 class="page-title">Add ER Visit</h4>
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
                <a href="er_visits.php">ER Visits</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add ER Visit</a>
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
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="patient_id">
                          <option value="" selected disabled>Select Patient</option>
                          <?php foreach ($patients as $patient): ?>
                            <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="datetime-local" class="form-control" style="border: 1px solid red;" placeholder="Arrival Time" name="arrival_time" value="<?php echo htmlspecialchars($_POST['arrival_time'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Chief Complaint" name="chief_complaint"><?php echo htmlspecialchars($_POST['chief_complaint'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control" style="border: 1px solid red;" name="triage_level">
                          <option value="" selected disabled>Triage Level</option>
                          <option value="resuscitation" <?php echo (isset($_POST['triage_level']) && $_POST['triage_level'] == 'resuscitation') ? 'selected' : ''; ?>>Resuscitation</option>
                          <option value="emergency" <?php echo (isset($_POST['triage_level']) && $_POST['triage_level'] == 'emergency') ? 'selected' : ''; ?>>Emergency</option>
                          <option value="urgency" <?php echo (isset($_POST['triage_level']) && $_POST['triage_level'] == 'urgency') ? 'selected' : ''; ?>>Urgency</option>
                          <option value="less_urgent" <?php echo (isset($_POST['triage_level']) && $_POST['triage_level'] == 'less_urgent') ? 'selected' : ''; ?>>Less Urgent</option>
                          <option value="non_urgent" <?php echo (isset($_POST['triage_level']) && $_POST['triage_level'] == 'non_urgent') ? 'selected' : ''; ?>>Non Urgent</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <input type="datetime-local" placeholder="Discharge Time" class="form-control" name="discharge_time" value="<?php echo htmlspecialchars($_POST['discharge_time'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Initial Findings" name="initial_findings"><?php echo htmlspecialchars($_POST['initial_findings'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <input type="text" class="form-control" placeholder="Subsequent Care" name="subsequent_care" value="<?php echo htmlspecialchars($_POST['subsequent_care'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-md-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Outcome" name="outcome"><?php echo htmlspecialchars($_POST['outcome'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-md-12 text-center">
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