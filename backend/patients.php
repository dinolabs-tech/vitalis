<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has permission
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist', 'nurse'])) {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Fetch patients
$patients = [];
$sql = "SELECT patient_id, first_name, last_name, date_of_birth, gender, email, phone, address, country, state 
        FROM patients";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_patient = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt_patient->bind_param("s", $delete_id);

    if (!$stmt_patient->execute()) {
      throw new Exception("Error deleting patient record: " . $stmt_patient->error);
    }

    $stmt_patient->close();
    $conn->commit();

    $success_message = "Patient deleted successfully!";
    header("Location: patients.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Patient: " . $e->getMessage();
    header("Location: patients.php?error=" . urlencode($error_message));
    exit;
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
            <h3 class="fw-bold mb-3">Patients</h3>
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
                <a href="#">Patients</a>
              </li>
            </ul>
            
          </div>

          <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            
            <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-patient.php" class="btn btn-primary btn-round">Add Patient</a>
              </div>
            <?php endif; ?>
          </div>

          

          <div class="row">
            <div class="col-md-12">
              <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($error_message); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
          <?php endif; ?>

          <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($success_message); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
          <?php endif; ?>
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table id="basic-datatables" class="display table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Age</th>
                          <th>Address</th>
                          <th>Phone</th>
                          <th>Email</th>
                          <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
                            <th class="text-right">Action</th>
                          <?php endif; ?>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($patients)): ?>
                          <tr><td colspan="6" class="text-center">No patients found.</td></tr>
                        <?php else: ?>
                          <?php foreach ($patients as $patient): ?>
                            <tr>
                              <td>
                                <img width="28" height="28" src="assets/img/user.jpg" class="rounded-circle m-r-5" alt="">
                                <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                              </td>
                              <td><?= date_diff(date_create($patient['date_of_birth']), date_create('now'))->y; ?></td>
                              <td><?= htmlspecialchars($patient['address'] . ', ' . $patient['state'] . ', ' . $patient['country']); ?></td>
                              <td><?= htmlspecialchars($patient['phone']); ?></td>
                              <td><?= htmlspecialchars($patient['email']); ?></td>

                              <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
                                <td class="text-right d-flex">
                                    <a href="edit-patient.php?id=<?= $patient['patient_id']; ?>" class="btn-icon btn-round btn-primary text-white mt-3"><i class="fas fa-edit"></i></a>
                                    <a href="#" data-id="<?= $patient['patient_id']; ?>" data-name="<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-patient mx-2 mt-3"><i class="fas fa-trash"></i></a>
                                  
                                </td>
                              <?php endif; ?>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>

  <!-- Delete Modal -->
  <div id="delete_patient" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-message">Are you sure you want to delete this patient's record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deletePatientForm" method="POST" action="patients.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-patient-id">
              <button type="submit" class="btn btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include('components/script.php'); ?>
  <script>
    $(document).ready(function() {
      $('.btn-delete-patient').on('click', function(e) {
        e.preventDefault();
        var patientId = $(this).data('id');
        var patientName = $(this).data('name');
        $('#delete-patient-id').val(patientId);
        $('#delete-message').text("Are you sure you want to delete " + patientName + "'s record?");
        $('#delete_patient').modal('show');
      });
    });
  </script>
</body>
</html>
