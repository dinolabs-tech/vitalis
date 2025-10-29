<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'receptionist') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Patient Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? '';
  $admission_id = $_POST['admission_id'] ?? null;
  $item_type = $_POST['item_type'] ?? '';
  $item_id = $_POST['item_id'] ?? null;
  $description = $_POST['description'] ?? '';
  $quantity = $_POST['quantity'] ?? 1;
  $unit_price = $_POST['unit_price'] ?? 0.00;
  $status = $_POST['status'] ?? 'pending';
  $bill_date = $_POST['bill_date'] ?? date('Y-m-d H:i:s');
  $patient_bill_id = $_POST['patient_bill_id'] ?? null; // For editing

  $total_amount = $quantity * $unit_price;

  if (empty($patient_id) || empty($item_type) || empty($description) || empty($quantity) || empty($unit_price) || $quantity <= 0 || $unit_price <= 0) {
    $error_message = "Please fill in all required fields and ensure quantity and unit price are positive.";
  } else {
    if ($patient_bill_id) {
      // Update existing patient bill
      $stmt = $conn->prepare("UPDATE patient_bills SET patient_id = ?, admission_id = ?, item_type = ?, item_id = ?, description = ?, quantity = ?, unit_price = ?, total_amount = ?, status = ?, bill_date = ? WHERE id = ?");
      $stmt->bind_param("sisissddssi", $patient_id, $admission_id, $item_type, $item_id, $description, $quantity, $unit_price, $total_amount, $status, $bill_date, $patient_bill_id);
      if ($stmt->execute()) {
        $success_message = "Patient Bill updated successfully!";
      } else {
        $error_message = "Error updating patient bill: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new patient bill
      $stmt = $conn->prepare("INSERT INTO patient_bills (patient_id, admission_id, item_type, item_id, description, quantity, unit_price, total_amount, status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sisissddss", $patient_id, $admission_id, $item_type, $item_id, $description, $quantity, $unit_price, $total_amount, $status, $bill_date);
      if ($stmt->execute()) {
        $success_message = "Patient Bill added successfully!";
      } else {
        $error_message = "Error adding patient bill: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}

// Handle Delete Patient Bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $delete_id = $_POST['delete_id'];

  $conn->begin_transaction();
  try {
    $stmt_patient_bill = $conn->prepare("DELETE FROM patient_bills WHERE id = ?");
    $stmt_patient_bill->bind_param("i", $delete_id);

    if (!$stmt_patient_bill->execute()) {
      throw new Exception("Error deleting patient bill record: " . $stmt_patient_bill->error);
    }

    $stmt_patient_bill->close();
    $conn->commit();

    $success_message = "Patient Bill deleted successfully!";
    header("Location: patient-bills.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Patient Bill: " . $e->getMessage();
    header("Location: patient-bills.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all patient bills
$patient_bills = [];
$current_branch_id = $_SESSION['branch_id'] ?? null; // Assuming branch_id is stored in session

$sql = "SELECT pb.*, CONCAT(p.first_name, ' ' ,p.last_name) AS patient_name, a.id as admission_number, b.branch_name
        FROM patient_bills pb
        LEFT JOIN patients p ON pb.patient_id = p.id
        LEFT JOIN admissions a ON pb.admission_id = a.id
        LEFT JOIN branches b ON pb.branch_id = b.branch_id";

$where_clauses = [];
$params = [];
$param_types = "";

if ($current_branch_id) {
    $where_clauses[] = "pb.branch_id = ?";
    $params[] = $current_branch_id;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY pb.bill_date DESC";

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
    $patient_bills[] = $row;
  }
}

// Fetch patient bill data for editing if ID is provided in GET
$edit_patient_bill_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_patient_bill_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM patient_bills WHERE id = ?");
  $stmt->bind_param("i", $edit_patient_bill_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_patient_bill_data = $result->fetch_assoc();
  } else {
    $error_message = "Patient Bill not found for editing.";
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

// Fetch admissions for dropdown
$admissions = [];
$result_admissions = $conn->query("SELECT id, patient_id, admission_date FROM admissions ORDER BY admission_date DESC");
if ($result_admissions) {
  while ($row = $result_admissions->fetch_assoc()) {
    $admissions[] = $row;
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
            <h3 class="fw-bold mb-3">Patient Bills</h3>
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
                <a href="#">Patient Bills</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-patient-bill.php" class="btn btn-primary btn-round">Add Patient Bill</a>
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
                  <table class="table table-striped custom-table" id="basic-datatables">
                    <thead>
                      <tr>
                        <th>Patient ID</th>
                        <th>Admission ID</th>
                        <th>Item Type</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>Bill Date</th>
                        <th>Status</th>
                        <th>Branch Name</th>
                        <th class="text-right">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($patient_bills) > 0): ?>
                        <?php foreach ($patient_bills as $bill): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($bill['admission_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($bill['item_type']); ?></td>
                            <td><?php echo htmlspecialchars(substr($bill['description'], 0, 50)) . (strlen($bill['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($bill['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($bill['unit_price']); ?></td>
                            <td><?php echo htmlspecialchars($bill['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($bill['bill_date']); ?></td>
                            <td><?php echo htmlspecialchars($bill['status']); ?></td>
                            <td><?php echo htmlspecialchars($bill['branch_name'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
                              <td class="text-right d-flex">
                                  <a href="edit-patient-bill.php?id=<?php echo $bill['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $bill['id']; ?>" data-patient-name="<?php echo htmlspecialchars($bill['patient_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-patient-bill"><i class="fas fa-trash"></i> </a>
                                </td>
                            <?php endif; ?>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="11" class="text-center">No patient bills found.</td>
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
  <div id="delete_patient_bill_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-patient-bill-message">Are you sure you want to delete this patient bill record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deletePatientBillForm" method="POST" action="patient-bills.php" style="display: inline;">
              <input type="hidden" name="delete_id" id="delete-patient-bill-id">
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
      $('.btn-delete-patient-bill').on('click', function(e) {
        e.preventDefault();
        var patientBillId = $(this).data('id');
        var patientName = $(this).data('patient-name');
        $('#delete-patient-bill-id').val(patientBillId);
        $('#delete-patient-bill-message').text("Are you sure you want to delete the patient bill for '" + patientName + "'?");
        $('#delete_patient_bill_modal').modal('show');
      });
    });
  </script>

</body>

</html>
