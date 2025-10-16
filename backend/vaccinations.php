<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Vaccination
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaccine_name'])) {
  $patient_id = $_POST['patient_id'] ?? '';
  $vaccine_name = $_POST['vaccine_name'] ?? '';
  $administration_date = $_POST['administration_date'] ?? '';
  $administered_by_staff_id = $_POST['administered_by_staff_id'] ?? null;
  $notes = $_POST['notes'] ?? '';
  $branch_id = $_POST['branch_id'] ?? null;
  $vaccination_id = $_POST['vaccination_id'] ?? null; // For editing

  if (empty($patient_id) || empty($vaccine_name) || empty($administration_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($vaccination_id) {
      // Update existing vaccination
      $stmt = $conn->prepare("UPDATE vaccinations SET patient_id = ?, vaccine_name = ?, administration_date = ?, administered_by_staff_id = ?, notes = ?, branch_id = ? WHERE id = ?");
      $stmt->bind_param("sssisii", $patient_id, $vaccine_name, $administration_date, $administered_by_staff_id, $notes, $branch_id, $vaccination_id);
      if ($stmt->execute()) {
        $success_message = "Vaccination updated successfully!";
      } else {
        $error_message = "Error updating vaccination: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new vaccination
      $stmt = $conn->prepare("INSERT INTO vaccinations (patient_id, vaccine_name, administration_date, administered_by_staff_id, notes, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssis", $patient_id, $vaccine_name, $administration_date, $administered_by_staff_id, $notes, $branch_id);
      if ($stmt->execute()) {
        $success_message = "Vaccination added successfully!";
      } else {
        $error_message = "Error adding vaccination: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// Handle Delete Vaccination
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_vaccination = $conn->prepare("DELETE FROM vaccinations WHERE id = ?");
    $stmt_vaccination->bind_param("i", $delete_id);

    if (!$stmt_vaccination->execute()) {
      throw new Exception("Error deleting vaccination record: " . $stmt_vaccination->error);
    }

    $stmt_vaccination->close();
    $conn->commit();

    $success_message = "Vaccination deleted successfully!";
    header("Location: vaccinations.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Vaccination: " . $e->getMessage();
    header("Location: vaccinations.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all vaccinations
$vaccinations = [];
$sql = "SELECT v.*, p.first_name, p.last_name, s.staffname as administered_by_staff_name, b.branch_name
        FROM vaccinations v
        LEFT JOIN patients p ON v.patient_id = p.patient_id
        LEFT JOIN login s ON v.administered_by_staff_id = s.id
        LEFT JOIN branches b ON v.branch_id = b.branch_id
        ORDER BY v.administration_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $vaccinations[] = $row;
  }
}

// Fetch vaccination data for editing if ID is provided in GET
$edit_vaccination_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_vaccination_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM vaccinations WHERE id = ?");
  $stmt->bind_param("i", $edit_vaccination_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_vaccination_data = $result->fetch_assoc();
  } else {
    $error_message = "Vaccination not found for editing.";
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
            <h3 class="fw-bold mb-3">Vaccinations</h3>
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
                <a href="#">Vaccinations</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-vaccination.php" class="btn btn-primary btn-round">Add Vaccination</a>
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


          <div class="row">
            <div class="col-md-12">
              <div class="card p-3">
                <div class="table-responsive">
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient Name</th>
                        <th>Vaccine Name</th>
                        <th>Administration Date</th>
                        <th>Administered By</th>
                        <th>Notes</th>
                        <th>Branch</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($vaccinations) > 0): ?>
                        <?php foreach ($vaccinations as $vaccination): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($vaccination['first_name'] . ' ' . $vaccination['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['vaccine_name']); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['administration_date']); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['administered_by_staff_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['notes']); ?></td>
                            <td><?php echo htmlspecialchars($vaccination['branch_name'] ?? 'N/A'); ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-vaccination.php?id=<?php echo $vaccination['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $vaccination['id']; ?>" data-patient-name="<?php echo htmlspecialchars($vaccination['first_name'] . ' ' . $vaccination['last_name']); ?>" data-vaccine-name="<?php echo htmlspecialchars($vaccination['vaccine_name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-vaccination"><i class="fas fa-trash"></i></a>
                              </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="7" class="text-center">No vaccinations found.</td>
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
  <div id="delete_vaccination_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-vaccination-message">Are you sure you want to delete this vaccination record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteVaccinationForm" method="POST" action="vaccinations.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-vaccination-id">
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
      $('.btn-delete-vaccination').on('click', function(e) {
        e.preventDefault();
        var vaccinationId = $(this).data('id');
        var patientName = $(this).data('patient-name'); // Assuming you'll add this data attribute
        var vaccineName = $(this).data('vaccine-name'); // Assuming you'll add this data attribute
        $('#delete-vaccination-id').val(vaccinationId);
        $('#delete-vaccination-message').text("Are you sure you want to delete " + vaccineName + " vaccination record for " + patientName + "?");
        $('#delete_vaccination_modal').modal('show');
      });
    });
  </script>
</body>

</html>
