<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'pharmacist' && $_SESSION['role'] !== 'doctor') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Medication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $product_id = $_POST['product_id'] ?? null;
  $dosage = $_POST['dosage'] ?? '';
  $administration_method = $_POST['administration_method'] ?? '';
  $side_effects = $_POST['side_effects'] ?? '';
  $expiry_date = $_POST['expiry_date'] ?? '';
  $storage_conditions = $_POST['storage_conditions'] ?? '';
  $medication_id = $_POST['medication_id'] ?? null; // For editing

  if (empty($product_id) || empty($dosage) || empty($expiry_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($medication_id) {
      // Update existing medication
      $stmt = $conn->prepare("UPDATE medications SET product_id = ?, dosage = ?, administration_method = ?, side_effects = ?, expiry_date = ?, storage_conditions = ? WHERE id = ?");
      $stmt->bind_param("isssssi", $product_id, $dosage, $administration_method, $side_effects, $expiry_date, $storage_conditions, $medication_id);
      if ($stmt->execute()) {
        $success_message = "Medication updated successfully!";
      } else {
        $error_message = "Error updating medication: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new medication
      $stmt = $conn->prepare("INSERT INTO medications (product_id, dosage, administration_method, side_effects, expiry_date, storage_conditions) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isssss", $product_id, $dosage, $administration_method, $side_effects, $expiry_date, $storage_conditions);
      if ($stmt->execute()) {
        $success_message = "Medication added successfully!";
      } else {
        $error_message = "Error adding medication: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}


// Handle Delete Medication
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_medication = $conn->prepare("DELETE FROM medications WHERE id = ?");
    $stmt_medication->bind_param("i", $delete_id);

    if (!$stmt_medication->execute()) {
      throw new Exception("Error deleting medication record: " . $stmt_medication->error);
    }

    $stmt_medication->close();
    $conn->commit();

    $success_message = "Medication deleted successfully!";
    header("Location: medications.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Medication: " . $e->getMessage();
    header("Location: medications.php?error=" . urlencode($error_message));
    exit;
  }
}


// Fetch all medications
$medications = [];
$sql = "SELECT m.*, p.name as product_name FROM medications m JOIN products p ON m.product_id = p.id ORDER BY p.name ASC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $medications[] = $row;
  }
}

// Fetch medication data for editing if ID is provided in GET
$edit_medication_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_medication_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM medications WHERE id = ?");
  $stmt->bind_param("i", $edit_medication_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_medication_data = $result->fetch_assoc();
  } else {
    $error_message = "Medication not found for editing.";
  }
  $stmt->close();
}

// Fetch products for dropdown (only those with product_type 'medication')
$products = [];
$result_products = $conn->query("SELECT id, name FROM products WHERE product_type = 'medication' ORDER BY name ASC");
if ($result_products) {
  while ($row = $result_products->fetch_assoc()) {
    $products[] = $row;
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
            <h3 class="fw-bold mb-3">Medications</h3>
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
                <a href="#">Medications</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-medication.php" class="btn btn-primary btn-round">Add Medications</a>
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
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped custom-table" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Product Name</th>
                          <th>Dosage</th>
                          <th>Administration Method</th>
                          <th>Side Effects</th>
                          <th>Expiry Date</th>
                          <th>Storage Conditions</th>
                          <th>Created At</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($medications) > 0): ?>
                          <?php foreach ($medications as $medication): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($medication['product_name']); ?></td>
                              <td><?php echo htmlspecialchars($medication['dosage']); ?></td>
                              <td><?php echo htmlspecialchars($medication['administration_method']); ?></td>
                              <td><?php echo htmlspecialchars($medication['side_effects']); ?></td>
                              <td><?php echo htmlspecialchars($medication['expiry_date']); ?></td>
                              <td><?php echo htmlspecialchars($medication['storage_conditions']); ?></td>
                              <td><?php echo htmlspecialchars($medication['created_at']); ?></td>
                              <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
                                <td class="text-right d-flex">
                                    <a href="edit-medication.php?id=<?php echo $medication['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                    <a href="#" data-id="<?php echo $medication['id']; ?>" data-product-name="<?php echo htmlspecialchars($medication['product_name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-medication"><i class="fas fa-trash"></i> </a>
                                </td>
                              <?php endif; ?>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="8" class="text-center">No medications found.</td>
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
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <!-- Delete Modal -->
  <div id="delete_medication_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-medication-message">Are you sure you want to delete this medication record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteMedicationForm" method="POST" action="medications.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-medication-id">
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
      $('.btn-delete-medication').on('click', function(e) {
        e.preventDefault();
        var medicationId = $(this).data('id');
        var productName = $(this).data('product-name');
        $('#delete-medication-id').val(medicationId);
        $('#delete-medication-message').text("Are you sure you want to delete the medication '" + productName + "'?");
        $('#delete_medication_modal').modal('show');
      });
    });
  </script>
</body>

</html>
