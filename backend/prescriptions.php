<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || !in_array($_SESSION['role'], ['admin', 'doctor', 'pharmacist', 'superuser'])) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Prescription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? null;
  $medication_id = $_POST['medication_id'] ?? null; // Changed from drug_id to medication_id
  $dosage = $_POST['dosage'] ?? '';
  $prescription_date = $_POST['prescription_date'] ?? ''; // Added prescription_date
  $notes = $_POST['notes'] ?? '';
  $doctor_id = $_POST['doctor_id'] ?? null;
  $branch_id = $_POST['branch_id'] ?? null; // Added branch_id
  $prescription_id = $_POST['prescription_id'] ?? null; // For editing

  if (empty($patient_id) || empty($medication_id) || empty($dosage) || empty($prescription_date)) { // Removed quantity from validation
    $error_message = "Please fill in all required fields.";
  } else {
    if ($prescription_id) {
      // Update existing prescription
      $stmt = $conn->prepare("UPDATE prescriptions SET patient_id = ?, medication_id = ?, dosage = ?, prescription_date = ?, notes = ?, doctor_id = ?, branch_id = ? WHERE id = ?"); // Changed drug_id to medication_id, removed quantity, added prescription_date, added branch_id
      $stmt->bind_param("iisssiii", $patient_id, $medication_id, $dosage, $prescription_date, $notes, $doctor_id, $branch_id, $prescription_id); // Changed drug_id to medication_id, removed quantity, added prescription_date, added branch_id
      if ($stmt->execute()) {
        $success_message = "Prescription updated successfully!";
      } else {
        $error_message = "Error updating prescription: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new prescription
      $stmt = $conn->prepare("INSERT INTO prescriptions (patient_id, medication_id, dosage, prescription_date, notes, doctor_id, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?)"); // Changed drug_id to medication_id, removed quantity, added prescription_date, added branch_id
      $stmt->bind_param("iisssii", $patient_id, $medication_id, $dosage, $prescription_date, $notes, $doctor_id, $branch_id); // Changed drug_id to medication_id, removed quantity, added prescription_date, added branch_id
      if ($stmt->execute()) {
        $success_message = "Prescription added successfully!";
      } else {
        $error_message = "Error adding prescription: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// Handle Delete Prescription (using POST from modal)
if (isset($_POST['delete_id'])) {
  $prescription_id_to_delete = $_POST['delete_id'];

  $conn->begin_transaction();
  try {
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
    $stmt->bind_param("i", $prescription_id_to_delete);
    if (!$stmt->execute()) {
      throw new Exception("Error deleting prescription: " . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    $success_message = "Prescription deleted successfully!";
    header("Location: prescriptions.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Prescription: " . $e->getMessage();
    header("Location: prescriptions.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all prescriptions
$prescriptions = [];
$current_branch_id = $_SESSION['branch_id'] ?? null; // Assuming branch_id is stored in session

$sql = " SELECT 
        p.id, 
        pat.first_name, 
        pat.last_name, 
        prod.name AS drug_name, 
        p.dosage, 
        p.prescription_date AS admin_date,
        l.staffname AS doctor_name,
        br.branch_name
    FROM prescriptions p
    JOIN patients pat ON p.patient_id = pat.id
    JOIN medications m ON p.medication_id = m.id
    JOIN products prod ON m.product_id = prod.id
    JOIN login l ON p.doctor_id = l.id
    LEFT JOIN branches br ON p.branch_id = br.branch_id";

$where_clauses = [];
$params = [];
$param_types = "";

if ($_SESSION['role'] !== 'admin' && $current_branch_id) {
    $where_clauses[] = "p.branch_id = ?";
    $params[] = $current_branch_id;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $prescriptions[] = $row;
  }
}

// Fetch prescription data for editing if ID is provided in GET
$edit_prescription_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_prescription_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id = ?");
  $stmt->bind_param("i", $edit_prescription_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_prescription_data = $result->fetch_assoc();
  } else {
    $error_message = "Prescription not found for editing.";
  }
  $stmt->close();
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $conn->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

// Fetch drugs (products with type 'medication') for dropdown
$drugs = [];
$result_drugs = $conn->query("SELECT id, name FROM products WHERE product_type = 'medication' ORDER BY name ASC");
if ($result_drugs) {
  while ($row = $result_drugs->fetch_assoc()) {
    $drugs[] = $row;
  }
}

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT d.id, l.staffname FROM doctors d JOIN login l ON d.staff_id = l.id ORDER BY l.staffname ASC");
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
            <h3 class="fw-bold mb-3">Prescriptions</h3>
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
                <a href="#">Prescriptions</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist' || $_SESSION['role'] === 'doctor'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-prescription.php" class="btn btn-primary btn-round">Add Prescription</a>
              </div>
            <?php endif; ?>
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
              <div class="card p-3">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Medication Name</th>
                        <th>Dosage</th>
                        <th>Prescription Date</th>
                        <th>Doctor</th>
                        <th>Branch Name</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($prescriptions) > 0): ?>
                        <?php foreach ($prescriptions as $prescription): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['drug_name']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['admin_date']); ?></td>
                            <td><?php echo htmlspecialchars(!empty($prescription['doctor_name']) ? $prescription['doctor_name'] : 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($prescription['branch_name'] ?? 'N/A'); ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-prescription.php?id=<?php echo $prescription['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $prescription['id']; ?>" data-patient-name="<?php echo htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']); ?>" data-medication-name="<?php echo htmlspecialchars($prescription['drug_name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-prescription"><i class="fas fa-trash"></i></a>
                              </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="7" class="text-center">No prescriptions found.</td>
                        </tr>
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
  <div id="delete_prescription_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-prescription-message">Are you sure you want to delete this prescription record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deletePrescriptionForm" method="POST" action="prescriptions.php" style="display: inline;">
              <input type="hidden" name="delete_id" id="delete-prescription-id">
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
      $('.btn-delete-prescription').on('click', function(e) {
        e.preventDefault();
        var prescriptionId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var medicationName = $(this).data('medication-name');
        $('#delete-prescription-id').val(prescriptionId);
        $('#delete-prescription-message').text("Are you sure you want to delete the prescription for '" + medicationName + "' for patient '" + patientName + "'?");
        $('#delete_prescription_modal').modal('show');
      });
    });
  </script>
</body>

</html>
