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
$note_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $note_id = $_POST['note_id'] ?? '';
  $patient_id = $_POST['patient_id'] ?? ''; // Should be hidden or read-only
  $doctor_id = $_SESSION['id'] ?? null; // Assuming current logged-in doctor
  $note_title = $_POST['note_title'] ?? '';
  $note_content = $_POST['note_content'] ?? '';

  // Basic validation
  if (empty($note_id) || empty($patient_id) || empty($note_title) || empty($note_content)) {
    $error_message = "Please fill in all required fields and ensure note ID is present.";
  } else {
    // Update doctor_notes table
    $stmt = $conn->prepare("UPDATE doctor_notes SET patient_id=?, doctor_id=?, note_title=?, note_content=? WHERE note_id=?");
    $stmt->bind_param("iissi", $patient_id, $doctor_id, $note_title, $note_content, $note_id);

    if ($stmt->execute()) {
      $success_message = "Doctor note updated successfully!";
      header("Location: doctor-notes.php?success=" . urlencode($success_message));
      exit();
    } else {
      $error_message = "Failed to update doctor note: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch existing doctor note data for editing
if (isset($_GET['id'])) {
  $note_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT dn.*, p.first_name, p.last_name, p.patient_id as patient_unique_id FROM doctor_notes dn JOIN patients p ON dn.patient_id = p.id WHERE dn.note_id = ?");
  $stmt->bind_param("i", $note_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $note_data = $result->fetch_assoc();
  } else {
    $error_message = "Doctor note record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No doctor note ID provided for editing.";
}

// Fetch patients for dropdown (if patient_id needs to be selectable, though for edit it's usually fixed)
// This part is mostly for consistency, but for editing, the patient_id is usually fixed.
$patients = [];
$result_patients = $conn->query("SELECT id, patient_id, first_name, last_name FROM patients");
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
            <h4 class="page-title">Edit Doctor Note</h4>
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
                <a href="doctor-notes.php">Doctor Notes</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Doctor Note</a>
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
                  <form method="POST" action="">

                    <?php if ($note_data): ?>

                      <div class="col-md-12">
                        <div class="form-group">
                          <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($note_data['patient_id']); ?>">
                          <input type="hidden" name="note_id" value="<?php echo htmlspecialchars($note_data['note_id']); ?>">
                          <input type="text" class="form-control" value="<?php echo htmlspecialchars($note_data['first_name'] . ' ' . $note_data['last_name'] . ' (' . $note_data['patient_unique_id'] . ')'); ?>" readonly>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <input class="form-control" style="border: 1px solid red;" placeholder="Note Title" type="text" name="note_title" value="<?php echo htmlspecialchars($note_data['note_title'] ?? ''); ?>">
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Note Content" style="border: 1px solid red;" name="note_content" rows="5"><?php echo htmlspecialchars($note_data['note_content'] ?? ''); ?></textarea>
                        </div>
                      </div>
                      <div class="col-md-12 text-center">
                        <button class="btn btn-primary submit-btn btn-icon btn-round"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p class="text-center">No doctor note record found for editing or an error occurred.</p>
                    <?php endif; ?>
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