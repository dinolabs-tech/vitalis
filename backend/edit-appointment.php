<?php
session_start();
include('database/db_connect.php'); // Include your database connection file

$appointment_id = '';
$patient_id = '';
$department_id = '';
$doctor_id = '';
$appointment_date = '';
$appointment_time = '';
$patient_email = '';
$patient_phone = '';
$message = '';
$status = '';
$success_message = '';
$error_message = '';

// Fetch appointment data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $appointment_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
  $stmt->bind_param("i", $appointment_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    $patient_id = $appointment['patient_id'];
    $doctor_id = $appointment['doctor_id'];
    $appointment_date = $appointment['appointment_date'];
    $status = $appointment['status'];
  } else {
    $error_message = "Appointment not found.";
  }
  $stmt->close();
}

// Handle form submission for updating appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $appointment_id = isset($_POST['appointment_id']) ? $_POST['appointment_id'] : '';
  $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : '';
  $doctor_id = isset($_POST['doctor_id']) ? $_POST['doctor_id'] : '';
  $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
  $status = isset($_POST['status']) ? $_POST['status'] : '';
  $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

  $stmt = $conn->prepare("UPDATE appointments SET patient_id = ?, doctor_id = ?, appointment_date = ?, status = ?, reason = ? WHERE id = ?");
  $stmt->bind_param("ssssss", $patient_id, $doctor_id, $appointment_date, $status, $reason, $appointment_id);

  if ($stmt->execute()) {
    $success_message = "Appointment updated successfully!";
    header("Location: appointments.php");
    exit();
  } else {
    $error_message = "Error updating appointment: " . $conn->error;
  }
  $stmt->close();
}

// Fetch patients, departments, and doctors for dropdowns
$patients = $conn->query("SELECT * FROM patients");
$departments = $conn->query("SELECT *  FROM departments");
$doctors = $conn->query("SELECT * FROM login WHERE role = 'doctor'");


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
            <h4 class="page-title">Edit Appointment</h4>
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
                <a href="appointments.php">Appointments</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Appointment</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
              <?php endif; ?>
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
              <?php endif; ?>
              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger mx-4 mt-3 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row g-3">
                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment_id); ?>">


                    <div class="col-md-6">
                      <div class="form-group">
                        <input class="form-control" type="text" placeholder="Appointment ID" value="APT-<?php echo htmlspecialchars($appointment_id); ?>" readonly>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="patient_id">
                          <option value="" disabled selected>Select Patient</option>
                          <?php while ($patient = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo ($patient['id'] == $patient_id) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                    </div>


                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="doctor_id">
                          <option value="" disabled selected>Select Doctor</option>
                          <?php while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo ($doctor['id'] == $doctor_id) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($doctor['staffname']); ?>
                            </option>
                          <?php endwhile; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <!-- <div class="cal-icon"> -->
                        <input type="datetime-local" style="border: 1px solid red;" class="form-control" placeholder="Date" name="appointment_date" value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>">
                        <!-- </div> -->
                      </div>
                    </div>


                    <div class="col-md-6">
                      <div class="form-group">
                        <select name="status" class="form-control form-select" id="">
                          <option value="" disabled selected>Select Status</option>
                          <option value="scheduled" <?php if ($appointment['status'] === 'scheduled') echo 'selected'; ?>>Scheduled</option>
                          <option value="completed" <?php if ($appointment['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                          <option value="cancelled" <?php if ($appointment['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-5">
                      <div class="form-group">
                        <input class="form-control" placeholder="Reason" type="text" name="reason" value="<?php echo htmlspecialchars($appointment['reason']); ?>">
                      </div>
                    </div>
                    <div class="col-sm-1 mt-3">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
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