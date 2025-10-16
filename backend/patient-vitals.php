<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse') {
  // header("Location: login.php");
  // exit;
}

$error_message = '';
$success_message = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'delete') {
    // Handle Delete Patient Vital
    if (isset($_POST['id'])) {
      $delete_id = $_POST['id'];

      $conn->begin_transaction();
      try {
        $stmt_vital = $conn->prepare("DELETE FROM patient_vitals WHERE id = ?");
        $stmt_vital->bind_param("i", $delete_id);

        if (!$stmt_vital->execute()) {
          throw new Exception("Error deleting patient vital record: " . $stmt_vital->error);
        }

        $stmt_vital->close();
        $conn->commit();

        $success_message = "Patient Vital deleted successfully!";
        header("Location: patient-vitals.php?success=" . urlencode($success_message));
        exit;
      } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to delete Patient Vital: " . $e->getMessage();
        header("Location: patient-vitals.php?error=" . urlencode($error_message));
        exit;
      }
    }
  } else {
    // Handle Add/Edit Patient Vital
    $patient_id = $_POST['patient_id'] ?? '';
    $recorded_by_staff_id = $_POST['recorded_by_staff_id'] ?? null;
    $temperature = $_POST['temperature'] ?? null;
    $blood_pressure_systolic = $_POST['blood_pressure_systolic'] ?? null;
    $blood_pressure_diastolic = $_POST['blood_pressure_diastolic'] ?? null;
    $heart_rate = $_POST['heart_rate'] ?? null;
    $respiration_rate = $_POST['respiration_rate'] ?? null;
    $weight_kg = $_POST['weight_kg'] ?? null;
    $height_cm = $_POST['height_cm'] ?? null;
    $blood_oxygen_saturation = $_POST['blood_oxygen_saturation'] ?? null;
    $notes = $_POST['notes'] ?? '';
    $recorded_at = $_POST['recorded_at'] ?? date('Y-m-d H:i:s');
    $patient_vital_id = $_POST['patient_vital_id'] ?? null; // For editing

    if (empty($patient_id) || empty($recorded_at)) {
      $error_message = "Please fill in all required fields.";
    } else {
      if ($patient_vital_id) {
        // Update existing patient vital
        $stmt = $conn->prepare("UPDATE patient_vitals SET patient_id = ?, recorded_by_staff_id = ?, temperature = ?, blood_pressure_systolic = ?, blood_pressure_diastolic = ?, heart_rate = ?, respiration_rate = ?, weight_kg = ?, height_cm = ?, blood_oxygen_saturation = ?, notes = ?, recorded_at = ? WHERE id = ?");
        $stmt->bind_param("sidiiiiiddssi", $patient_id, $recorded_by_staff_id, $temperature, $blood_pressure_systolic, $blood_pressure_diastolic, $heart_rate, $respiration_rate, $weight_kg, $height_cm, $blood_oxygen_saturation, $notes, $recorded_at, $patient_vital_id);
        if ($stmt->execute()) {
          $success_message = "Patient Vital updated successfully!";
        } else {
          $error_message = "Error updating patient vital: " . $stmt->error;
        }
        $stmt->close();
      } else {
        // Add new patient vital
        $stmt = $conn->prepare("INSERT INTO patient_vitals (patient_id, recorded_by_staff_id, temperature, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, respiration_rate, weight_kg, height_cm, blood_oxygen_saturation, notes, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidiiiiiddss", $patient_id, $recorded_by_staff_id, $temperature, $blood_pressure_systolic, $blood_pressure_diastolic, $heart_rate, $respiration_rate, $weight_kg, $height_cm, $blood_oxygen_saturation, $notes, $recorded_at);
        if ($stmt->execute()) {
          $success_message = "Patient Vital added successfully!";
        } else {
          $error_message = "Error adding patient vital: " . $stmt->error;
        }
        $stmt->close();
      }
    }
  }
}

// Fetch all patient vitals
$patient_vitals = [];
$sql = "SELECT pv.*, p.first_name, p.last_name, s.staffname as recorded_by_staff_name
        FROM patient_vitals pv
        LEFT JOIN patients p ON pv.patient_id = p.patient_id
        LEFT JOIN login s ON pv.recorded_by_staff_id = s.id
        ORDER BY pv.recorded_at DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $patient_vitals[] = $row;
  }
}

// Fetch patient vital data for editing if ID is provided in GET
$edit_patient_vital_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_patient_vital_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM patient_vitals WHERE id = ?");
  $stmt->bind_param("i", $edit_patient_vital_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_patient_vital_data = $result->fetch_assoc();
  } else {
    $error_message = "Patient Vital not found for editing.";
  }
  $stmt->close();
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch staff for dropdown (admins, doctors, nurses)
$staff = [];
$result_staff = $conn->query("SELECT id, staffname FROM login WHERE role IN ('admin', 'doctor', 'nurse') ORDER BY staffname ASC");
if ($result_staff) {
  while ($row = $result_staff->fetch_assoc()) {
    $staff[] = $row;
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
            <h3 class="fw-bold mb-3">Patient Vitals</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Patient Vitals</a>
              </li>
            </ul>
          </div>
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-patient-vital.php" class="btn btn-primary btn-round">Add Patient Vital</a>
              </div>
            <?php endif; ?>
          </div>

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

          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-striped" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Recorded At</th>
                        <th>Temperature (°C)</th>
                        <th>BP (Systolic)</th>
                        <th>BP (Diastolic)</th>
                        <th>Heart Rate</th>
                        <th>Respiration Rate</th>
                        <th>Weight (kg)</th>
                        <th>Height (cm)</th>
                        <th>SpO2 (%)</th>
                        <th>Recorded By</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($patient_vitals) > 0): ?>
                        <?php foreach ($patient_vitals as $vital): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($vital['first_name'] . ' ' . $vital['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($vital['recorded_at']); ?></td>
                            <td><?php echo htmlspecialchars($vital['temperature'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['blood_pressure_systolic'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['blood_pressure_diastolic'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['heart_rate'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['respiration_rate'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['weight_kg'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['height_cm'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['blood_oxygen_saturation'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vital['recorded_by_staff_name'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse' || $_SESSION['role'] === 'doctor'): ?>
                              <td class="text-right d-flex">
                                <a href="edit-patient-vital.php?editid=<?php echo $vital['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
                                  <a href="#" data-id="<?php echo $vital['id']; ?>" data-patient-name="<?php echo htmlspecialchars($vital['first_name'] . ' ' . $vital['last_name']); ?>" data-recorded-at="<?php echo htmlspecialchars($vital['recorded_at']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-vital"><i class="fas fa-trash"></i> </a>
                                <?php endif; ?>
                              </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="12" class="text-center">No patient vitals found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card p-3">
                <h3>Vitals History</h3>
                <!-- Form to select a patient for viewing their vitals history -->
                <form action="patient-vitals.php" method="GET" class="form-inline mb-3">
                  <label for="history_patient_id" class="mr-2">Select Patient for History:</label>
                  <select class="form-control form-select searchable-dropdown mb-5" name="history_patient_id" onchange="this.form.submit()">
                    <option value="" selected disabled>Select Patient</option>
                    <?php
                    // Fetch patients again for the history dropdown
                    $patients_result_history = $conn->query("SELECT patient_id, first_name, last_name FROM Patients");
                    // Loop through patients and populate the dropdown
                    while ($patient_history = $patients_result_history->fetch_assoc()):
                    ?>
                      <option value="<?php echo $patient_history['patient_id']; ?>" <?php echo (isset($_GET['history_patient_id']) && $_GET['history_patient_id'] == $patient_history['patient_id']) ? 'selected' : ''; ?>>
                        <?php echo $patient_history['first_name'] . ' ' . $patient_history['last_name']; ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </form>

                <?php
                // Check if a patient ID for history is selected
                if (isset($_GET['history_patient_id']) && !empty($_GET['history_patient_id'])):
                  $selected_patient_id = $_GET['history_patient_id'];
                  // Prepare statement to fetch vitals history for the selected patient
                  // $vitals_history_stmt = $conn->prepare("SELECT pv.*, l.staffname FROM patient_vitals pv INNER JOIN login l ON pv.recorded_by_staff_id = l.id WHERE pv.patient_id = ? ORDER BY pv.recorded_at DESC");
                  $vitals_history_stmt = $conn->prepare("SELECT * FROM patient_vitals WHERE patient_id = ? ORDER BY recorded_at DESC");
                  // Bind the selected patient ID
                  $vitals_history_stmt->bind_param("s", $selected_patient_id);
                  // Execute the statement
                  $vitals_history_stmt->execute();
                  // Get the result set
                  $vitals_history_result = $vitals_history_stmt->get_result();
                ?>
                  <h3 class="mt-5">Vitals History for <?php
                                                      // Fetch patient's name to display in the heading
                                                      $patient_name_stmt = $conn->prepare("SELECT first_name, last_name FROM Patients WHERE patient_id = ?");
                                                      $patient_name_stmt->bind_param("i", $selected_patient_id);
                                                      $patient_name_stmt->execute();
                                                      $patient_name_result = $patient_name_stmt->get_result();
                                                      $patient_name = $patient_name_result->fetch_assoc();
                                                      // Display the patient's full name
                                                      echo $patient_name['first_name'] . ' ' . $patient_name['last_name'];
                                                      // Close the patient name statement
                                                      $patient_name_stmt->close();
                                                      ?></h3>
                  <!-- Table to display the vitals history -->
                  <div class="table-responsive">
                    <table class="table table-bordered" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Date/Time</th>
                          <th>Temp (°C)</th>
                          <th>Heart Rate (bpm)</th>
                          <th>BP Systolic</th>
                          <th>BP Diastolic</th>
                          <th>Resp Rate</th>
                          <th>Weight (kg)</th>
                          <th>Height (cm)</th>
                          <!-- <th>Recorded By</th> -->
                        </tr>
                      </thead>
                      <tbody>
                        <?php if ($vitals_history_result->num_rows > 0): ?>
                          <?php while ($vitals = $vitals_history_result->fetch_assoc()): ?>
                            <tr>
                              <td><?php echo $vitals['recorded_at']; ?></td>
                              <td><?php echo $vitals['temperature']; ?></td>
                              <td><?php echo $vitals['heart_rate']; ?></td>
                              <td><?php echo $vitals['blood_pressure_systolic']; ?></td>
                              <td><?php echo $vitals['blood_pressure_diastolic']; ?></td>
                              <td><?php echo $vitals['respiration_rate']; ?></td>
                              <td><?php echo $vitals['weight_kg']; ?></td>
                              <td><?php echo $vitals['height_cm']; ?></td>
                              <!-- <td><?php echo $vitals['staffname']; ?></td> -->
                            </tr>
                          <?php endwhile; ?>
                        <?php else: ?>
                          <!-- Message if no vitals are found for the patient -->
                          <tr>
                            <td colspan="8">No vitals recorded for this patient.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                <?php
                  // Close the vitals history statement
                  $vitals_history_stmt->close();
                endif;
                ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.searchable-dropdown').select2({
        placeholder: "Select an option",
        allowClear: true,
        width: '100%'
      });
    });
  </script>
  <!-- Delete Modal -->
  <div id="delete_vital_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-vital-message">Are you sure you want to delete this patient vital record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteVitalForm" method="POST" action="patient-vitals.php" style="display: inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" id="delete-vital-id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      $('.btn-delete-vital').on('click', function(e) {
        e.preventDefault();
        var vitalId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var recordedAt = $(this).data('recorded-at');
        $('#delete-vital-id').val(vitalId);
        $('#delete-vital-message').text("Are you sure you want to delete the vital record for '" + patientName + "' recorded on " + recordedAt + "?");
        $('#delete_vital_modal').modal('show');
      });
    });
  </script>
</body>

</html>
