<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin, doctor, or receptionist role
if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'receptionist', 'pharmacist'])) {
  header("Location: login.php");
  exit;
}
$success_message = '';
$error_message = '';

// Placeholder for drug consultation data retrieval
$drug_consultations = [];
$sql = "SELECT dc.*,
            p.first_name,
            p.last_name,
            l.staffname AS doctor_name,
            pr.name AS medication_name
        FROM drug_consultations dc
        LEFT JOIN patients p ON dc.patient_id = p.patient_id
        LEFT JOIN login l ON dc.doctor_id = l.id
        LEFT JOIN products pr ON dc.drug_id = pr.id -- Corrected join: drug_id in dc directly links to products.id
        ORDER BY dc.consultation_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $drug_consultations[] = $row;
  }
}

// Handle delete request
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM drug_consultations WHERE id = ?");
  $stmt->bind_param("i", $delete_id);

  if ($stmt->execute()) {
    $_SESSION['success_message'] = "Drug consultation record deleted successfully.";
  } else {
    $_SESSION['error_message'] = "Error deleting record: " . $stmt->error;
  }
  $stmt->close();
  header("Location: drug_consultation.php");
  exit;
}

// Retrieve messages from session
if (isset($_SESSION['success_message'])) {
  $success_message = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
  $error_message = $_SESSION['error_message'];
  unset($_SESSION['error_message']);
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
            <h4 class="fw-bold mb-3">Drug Consultations</h4>
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
                <a href="#">Drug Consultations</a>
              </li>
            </ul>
          </div>
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-drug-consultation.php" class="btn btn-primary btn-round">Add Drug Consultation</a>
              </div>
            <?php endif; ?>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-md-12">
                <div class="table-responsive">
                  <table class="table table-border table-striped" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Doctor Name</th>
                        <th>Drug Name</th>
                        <th>Consultation Date</th>
                        <th>Notes</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($drug_consultations)): ?>
                        <tr>
                          <td colspan="6" class="text-center">No drug consultations found.</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($drug_consultations as $consult): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($consult['first_name'] . ' ' . $consult['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($consult['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($consult['medication_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($consult['consultation_date']); ?></td>
                            <td><?php echo htmlspecialchars($consult['consultation_notes']); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist' || $_SESSION['role'] === 'receptionist'): ?>
                              <td class="text-right d-flex">
                                <a href="edit-drug-consultation.php?id=<?php echo $consult['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $consult['id']; ?>" data-patient-name="<?php echo htmlspecialchars($consult['first_name'] . ' ' . $consult['last_name']); ?>" data-medication-name="<?php echo htmlspecialchars($consult['medication_name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-consultation"><i class="fas fa-trash"></i></a>
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
  <div id="delete_drug_consultation_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-drug-consultation-message">Are you sure you want to delete this Drug Consultation Record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteDrugConsultationForm" method="POST" action="drug_consultation.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-drug-consultation-id">
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
      $('.btn-delete-consultation').on('click', function(e) {
        e.preventDefault();
        var consultationId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var medicationName = $(this).data('medication-name');
        $('#delete-drug-consultation-id').val(consultationId);
        $('#delete-drug-consultation-message').text("Are you sure you want to delete the drug consultation for '" + patientName + "' regarding '" + medicationName + "'?");
        $('#delete_drug_consultation_modal').modal('show');
      });
    });
  </script>
</body>

</html>
