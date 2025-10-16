<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist', 'nurse'])) {
  header("Location: login.php");
  exit;
}
$success_message = '';
$error_message = '';

// Placeholder for drug administration data retrieval
$drug_administrations = [];

// Fetch prescription data
$sql = "
    SELECT 
        p.id, 
        pat.first_name, 
        pat.last_name, 
        prod.name AS drug_name, 
        p.dosage, 
        p.prescription_date AS admin_date,
        l.staffname AS doctor_name
    FROM prescriptions p
    JOIN patients pat ON p.patient_id = pat.id
    JOIN medications m ON p.medication_id = m.id
    JOIN products prod ON m.product_id = prod.id
    JOIN login l ON p.doctor_id = l.id
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $drug_administrations[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  try {
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
      $success_message = "Prescription record deleted successfully!";
      // Redirect to refresh the page and show success message
      header("Location: drug_administration.php?success=" . urlencode($success_message));
      exit();
    } else {
      throw new Exception("Error deleting prescription record: " . $stmt->error);
    }
    $stmt->close();
  } catch (Exception $e) {
    $error_message = "Failed to delete prescription record: " . $e->getMessage();
  }
}

// If redirected with success message
if (isset($_GET['success'])) {
  $success_message = urldecode($_GET['success']);
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
            <h3 class="fw-bold mb-3">Drug Administration</h3>
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
                <a href="#">Drug Administration</a>
              </li>
            </ul>
          </div>

          <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
            <div class="ms-md-auto py-2 py-md-0 mb-3">
              <a href="add-drug-administration.php" class="btn btn-primary btn-round">Add Drug Administration</a>
            </div>
          <?php endif; ?>

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
                  <table class="table table-border table-striped" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Drug Name</th>
                        <th>Dosage</th>
                        <th>Administration Date</th>
                        <th>Doctor Name</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($drug_administrations)): ?>
                        <tr>
                          <td colspan="6" class="text-center">No prescriptions found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($drug_administrations as $admin): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['drug_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($admin['admin_date']); ?></td>
                            <td><?php echo htmlspecialchars($admin['doctor_name']); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'nurse'): ?>
                              <td class="text-right d-flex">
                                <a href="edit-drug-administration.php?id=<?php echo $admin['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $admin['id']; ?>" class="btn-icon btn-danger btn-round text-white btn-delete-admin"><i class="fas fa-trash"></i></a>
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

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="delete_drug_administration_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-drug-administration-message">Are you sure you want to delete this Drug Administration Record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteDrugAdministrationForm" method="POST" action="drug_administration.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-drug-administration-id">
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
      $('.btn-delete-admin').on('click', function(e) {
        e.preventDefault();
        var adminId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var drugName = $(this).data('drug-name');
        $('#delete-drug-administration-id').val(adminId);
        $('#delete-drug-administration-message').text("Are you sure you want to delete the drug administration record for '" + patientName + "' (Drug: " + drugName + ")?");
        $('#delete_drug_administration_modal').modal('show');
      });
    });
  </script>
</body>

</html>
