<?php
session_start();
include_once('database/db_connect.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['loggedin'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? '';
  $appointment_date = $_POST['appointment_date'] ?? '';
  $reason = $_POST['reason'] ?? '';
  $status = $_POST['status'] ?? 'scheduled';

  if (empty($patient_id) || empty($doctor_id) || empty($appointment_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $patient_id, $doctor_id, $appointment_date, $reason, $status);

    if ($stmt->execute()) {
      $success_message = "Appointment added successfully!";

      // Fetch doctor's details for email
      $doctor_email = '';
      $doctor_name = '';
      $stmt_doctor = $conn->prepare("SELECT staffname, email FROM login WHERE id = ? AND role = 'doctor'");
      $stmt_doctor->bind_param("s", $doctor_id);
      $stmt_doctor->execute();
      $result_doctor = $stmt_doctor->get_result();
      if ($result_doctor->num_rows > 0) {
        $doctor_data = $result_doctor->fetch_assoc();
        $doctor_name = $doctor_data['staffname'];
        $doctor_email = $doctor_data['email'];
      }
      $stmt_doctor->close();

      // Fetch patient's details for email
      $patient_name = '';
      $stmt_patient = $conn->prepare("SELECT first_name, last_name FROM patients WHERE id = ?");
      $stmt_patient->bind_param("s", $patient_id);
      $stmt_patient->execute();
      $result_patient = $stmt_patient->get_result();
      if ($result_patient->num_rows > 0) {
        $patient_data = $result_patient->fetch_assoc();
        $patient_name = $patient_data['first_name'] . ' ' . $patient_data['last_name'];
      }
      $stmt_patient->close();


      if (!empty($doctor_email)) {
        // Prepare email details
        $email_subject = "New Appointment Scheduled: " . $patient_name;
        $email_message = "
            <p>Dear Dr. {$doctor_name},</p>
            <p>A new appointment has been scheduled for you.</p>
            <p><strong>Patient:</strong> {$patient_name}</p>
            <p><strong>Date & Time:</strong> {$appointment_date}</p>
            <p><strong>Reason:</strong> {$reason}</p>
            <p>Please check your schedule for more details.</p>
            <p>Best regards,<br>Vitalis Model Team</p>
        ";

        // Include PHPMailer and send email
        require 'phpmailer/src/Exception.php';
        require 'phpmailer/src/PHPMailer.php';
        require 'phpmailer/src/SMTP.php';

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'mail.dinolabstech.com'; // Use the same host as send-message.php
            $mail->SMTPAuth   = true;
            $mail->Username   = 'enquiries@dinolabstech.com'; // Use the same username as send-message.php
            $mail->Password   = 'Dinolabs@11';     // Use the same password as send-message.php
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('enquiries@dinolabstech.com', 'Vitalis Model Appointments');
            $mail->addAddress($doctor_email);
            $mail->isHTML(true);
            $mail->Subject = $email_subject;
            $mail->Body    = $email_message;
            $mail->send();
            $success_message .= " Email sent to doctor.";
        } catch (Exception $e) {
            $error_message .= " Doctor email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
      } else {
        $error_message .= " Doctor email not found.";
      }

      $_POST = array();
      header("Location: appointments.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to add appointment: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}


// Fetch patients for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT id, staffname FROM login WHERE role = 'doctor'");
if ($result_doctors) {
  while ($row = $result_doctors->fetch_assoc()) {
    $doctors[] = $row;
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
            <h4 class="page-title">Add Appointments</h4>
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
                <a href="#">Add Appointment</a>
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
                  <h6 class="text-danger ms-2 small mx-3 mt-3">All placeholders with red border are compulsory</h6>
                <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row g-3">

                    <div class="col-md-6 mb-2">
                      <select class="form-control form-select" style="border: 1px solid red;" name="patient_id" required>
                        <option value="" selected disabled>Select Patient</option>
                        <?php foreach ($patients as $patient): ?>
                          <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['id']) && $_POST['id'] == $patient['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-6 mb-2">
                      <select class="form-control form-select" style="border: 1px solid red;" name="doctor_id" required>
                        <option value="" selected disabled>Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                          <option value="<?php echo $doctor['id']; ?>" <?php echo (isset($_POST['doctor_id']) && $_POST['doctor_id'] == $doctor['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['staffname']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="col-md-6 mb-2">
                      <input class="form-control" style="border: 1px solid red;" type="datetime-local" placeholder="Appointment Date" name="appointment_date" value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-2">
                      <select class="form-control form-select" name="status">
                        <option value="" selected disabled>Status</option>
                        <option value="scheduled" <?php echo (isset($_POST['status']) && $_POST['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                    </div>

                    <div class="col-md-12 mb-2">
                      <textarea class="form-control" placeholder="Reason" name="reason"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-12 mt-2 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
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
