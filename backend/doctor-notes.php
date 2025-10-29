<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'nurse') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Doctor Note
// Handle Add/Edit Doctor Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['note_title']) || isset($_POST['note_content']))) {
  $patient_id = $_POST['patient_id'] ?? null;
  $doctor_id = $_POST['doctor_id'] ?? null;
  $note_title = $_POST['note_title'] ?? '';
  $note_content = $_POST['note_content'] ?? '';
  $note_id = $_POST['id'] ?? null; // For editing (using 'id' as primary key)

  if (empty($patient_id) || empty($doctor_id) || empty($note_title) || empty($note_content)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($note_id) {
      // Update existing doctor note
      $stmt = $conn->prepare("UPDATE doctor_notes SET patient_id = ?, doctor_id = ?, note_title = ?, note_content = ? WHERE id = ?");
      $stmt->bind_param("iissi", $patient_id, $doctor_id, $note_title, $note_content, $note_id);
      if ($stmt->execute()) {
        $success_message = "Doctor Note updated successfully!";
      } else {
        $error_message = "Error updating doctor note: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new doctor note
      $stmt = $conn->prepare("INSERT INTO doctor_notes (patient_id, doctor_id, note_title, note_content) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("iiss", $patient_id, $doctor_id, $note_title, $note_content);
      if ($stmt->execute()) {
        $success_message = "Doctor Note added successfully!";
      } else {
        $error_message = "Error adding doctor note: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// Handle Delete Doctor Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['note_title']) && !isset($_POST['note_content'])) {
  $note_id_to_delete = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM doctor_notes WHERE note_id = ?");
  $stmt->bind_param("i", $note_id_to_delete);
  if ($stmt->execute()) {
    $success_message = "Doctor Note deleted successfully!";
  } else {
    $error_message = "Error deleting doctor note: " . $stmt->error;
  }
  $stmt->close();
}

// Fetch all doctor notes
$doctor_notes = [];
$current_branch_id = $_SESSION['branch_id'] ?? null; // Assuming branch_id is stored in session

$sql = "SELECT dn.note_id, dn.patient_id, dn.doctor_id, dn.note_title, dn.note_content, dn.created_at, p.first_name, p.last_name, l.staffname as doctor_name, b.branch_name
        FROM doctor_notes dn
        LEFT JOIN patients p ON dn.patient_id = p.id
        LEFT JOIN login l ON dn.doctor_id = l.id
        LEFT JOIN branches b ON dn.branch_id = b.branch_id";

$where_clauses = [];
$params = [];
$param_types = "";

if ($_SESSION['role'] !== 'admin' && $current_branch_id) {
    $where_clauses[] = "dn.branch_id = ?";
    $params[] = $current_branch_id;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY dn.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $doctor_notes[] = $row;
  }
}

// Fetch doctor note data for editing if ID is provided in GET
$edit_doctor_note_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_note_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM doctor_notes WHERE id = ?");
  $stmt->bind_param("i", $edit_note_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_doctor_note_data = $result->fetch_assoc();
  } else {
    $error_message = "Doctor Note not found for editing.";
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

// Fetch doctors for dropdown
$doctors = [];
$result_doctors = $conn->query("SELECT * FROM login WHERE role='doctor' ORDER BY staffname ASC");
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
            <h3 class="fw-bold mb-3">Doctor Notes</h3>
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
                <a href="#">Doctor Notes</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'doctor'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-doctor-note.php" class="btn btn-primary btn-round">Add Doctor Note</a>
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
                        <th>Doctor Name</th>
                        <th>Branch Name</th>
                        <th>Note Title</th>
                        <th>Note Content</th>
                        <th>Created At</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($doctor_notes) > 0): ?>
                        <?php foreach ($doctor_notes as $note): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($note['doctor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($note['branch_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($note['note_title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($note['note_content'], 0, 100)) . (strlen($note['note_content']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($note['created_at']); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'doctor'): ?>
                              <td class="text-right d-flex">
                                <a href="edit-doctor-note.php?id=<?php echo $note['note_id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $note['note_id']; ?>" data-patient-name="<?php echo htmlspecialchars($note['first_name'] . ' ' . $note['last_name']); ?>" data-note-title="<?php echo htmlspecialchars($note['note_title']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-note"><i class="fas fa-trash"></i></a>
                              </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="7" class="text-center">No doctor notes found.</td>
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
      <!-- Delete Modal -->
      <div id="delete_doctor_note_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-doctor-note-message">Are you sure you want to delete this Doctor's Note?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteDoctorNoteForm" method="POST" action="doctor-notes.php" style="display: inline;">
                  <input type="hidden" name="id" id="delete-doctor-note-id">
                  <button type="submit" class="btn btn-danger">Delete</button>
                </form>
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
      $('.btn-delete-note').on('click', function(e) {
        e.preventDefault();
        var noteId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        var noteTitle = $(this).data('note-title');
        $('#delete-doctor-note-id').val(noteId);
        $('#delete-doctor-note-message').text("Are you sure you want to delete the note '" + noteTitle + "' for '" + patientName + "'?");
        $('#delete_doctor_note_modal').modal('show');
      });
    });
  </script>
</body>

</html>
