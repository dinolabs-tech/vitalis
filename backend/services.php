<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Add Service
if (isset($_POST['add_service'])) {
  $service_name = $_POST['service_name'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $department_id = $_POST['department_id'];
  $branch_id = $_SESSION['branch_id'] ?? null; // Get branch_id from session

  $conn->begin_transaction();
  try {
    $query = "INSERT INTO services (service_name, description, price, department_id, branch_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdis', $service_name, $description, $price, $department_id, $branch_id);
    if (!$stmt->execute()) {
      throw new Exception("Error adding service: " . $stmt->error);
    }
    $stmt->close();
    $conn->commit();
    $success_message = "Service added successfully!";
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to add Service: " . $e->getMessage();
  }
}

// Edit Service
if (isset($_POST['edit_service'])) {
  $id = $_POST['id'];
  $service_name = $_POST['service_name'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $department_id = $_POST['department_id'];
  $branch_id = $_SESSION['branch_id'] ?? null; // Get branch_id from session

  $conn->begin_transaction();
  try {
    $query = "UPDATE services SET service_name=?, description=?, price=?, department_id=?, branch_id=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdisi', $service_name, $description, $price, $department_id, $branch_id, $id);
    if (!$stmt->execute()) {
      throw new Exception("Error updating service: " . $stmt->error);
    }
    $stmt->close();
    $conn->commit();
    $success_message = "Service updated successfully!";
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to update Service: " . $e->getMessage();
  }
}

// Delete Service
if (isset($_POST['id']) && !isset($_POST['add_service']) && !isset($_POST['edit_service'])) { // Ensure it's a delete request
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_service = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt_service->bind_param("i", $delete_id);

    if (!$stmt_service->execute()) {
      throw new Exception("Error deleting service record: " . $stmt_service->error);
    }

    $stmt_service->close();
    $conn->commit();

    $success_message = "Service deleted successfully!";
    header("Location: services.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Service: " . $e->getMessage();
    header("Location: services.php?error=" . urlencode($error_message));
    exit;
  }
}

// Fetch all services with branch filtering
$services = [];
$sql = "SELECT s.id, s.service_name, s.description, s.price, s.created_at, d.name AS department_name, b.branch_name 
        FROM services s
        LEFT JOIN departments d ON s.department_id = d.id
        LEFT JOIN branches b ON s.branch_id = b.branch_id";

$conditions = [];
$params = [];
$types = "";

// Apply branch filter if the user is not an admin or an admin with a specific branch_id
if ($_SESSION['role'] !== 'admin' || ($_SESSION['role'] === 'admin' && isset($_SESSION['branch_id']) && $_SESSION['branch_id'] !== null)) {
    $conditions[] = "s.branch_id = ?";
    $params[] = $_SESSION['branch_id'];
    $types .= "i";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY s.service_name ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $result = false;
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} else {
    $result = $conn->query($sql);
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
            <h3 class="fw-bold mb-3">Services</h3>
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
                <a href="#">Services</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-service.php" class="btn btn-primary btn-round">Add Service</a>
              </div>
            <?php endif; ?>
          </div>

          <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['msg'];
                                              unset($_SESSION['msg']); ?></div>
          <?php endif; ?>
          <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
          <?php endif; ?>


          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Service Name</th>
                          <th>Description</th>
                          <th>Price</th>
                          <th>Department</th>
                          <th>Branch</th>
                          <th>Created At</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if ($result->num_rows > 0): ?>
                          <?php while ($row = $result->fetch_object()): ?>
                            <tr>
                              <td><?php echo htmlentities($row->service_name); ?></td>
                              <td><?php echo htmlentities($row->description); ?></td>
                              <td><?php echo htmlentities($row->price); ?></td>
                              <td><?php echo htmlentities($row->department_name); ?></td>
                              <td><?php echo htmlentities($row->branch_name ?? 'N/A'); ?></td>
                              <td><?php echo htmlentities(date('Y-m-d H:i', strtotime($row->created_at))); ?></td>
                              <td class="text-right">
                                <div class="d-flex">
                                  <a href="edit-service.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white me-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $row->id; ?>" data-name="<?php echo htmlspecialchars($row->service_name); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-service"><i class="fas fa-trash"></i> </a>
                                </div>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="6" class="text-center">No services found.</td>
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
  <div id="delete_service_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-service-message">Are you sure you want to delete this service record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteServiceForm" method="POST" action="services.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-service-id">
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
      $('.btn-delete-service').on('click', function(e) {
        e.preventDefault();
        var serviceId = $(this).data('id');
        var serviceName = $(this).data('name');
        $('#delete-service-id').val(serviceId);
        $('#delete-service-message').text("Are you sure you want to delete the service '" + serviceName + "'?");
        $('#delete_service_modal').modal('show');
      });
    });
  </script>
</body>

</html>
</final_file_content>

IMPORTANT: For any future changes to this file, use the final_file_content shown above as your reference. This content reflects the current state of the file, including any auto-formatting (e.g., if you used single quotes but the formatter converted them to double quotes). Always base your SEARCH/REPLACE operations on this final version to ensure accuracy.

<environment_details>
# Visual Studio Code Visible Files
backend/services.php

# Visual Studio Code Open Tabs
backend/add-branch.php
backend/edit-branch.php
backend/patients.php
backend/doctors.php
backend/database/database_schema.php
backend/employees.php
backend/backup.php
backend/appointments.php
backend/departments.php
backend/branches.php
backend/products.php
backend/index.php
backend/invoices.php
backend/services.php

# Current Time
10/28/2025, 1:25:02 PM (Africa/Lagos, UTC+1:00)

# Context Window Usage
231,900 / 1,048.576K tokens used (22%)

# Current Mode
ACT MODE
