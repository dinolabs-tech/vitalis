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

  $conn->begin_transaction();
  try {
    $query = "INSERT INTO services (service_name, description, price, department_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdi', $service_name, $description, $price, $department_id);
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

  $conn->begin_transaction();
  try {
    $query = "UPDATE services SET service_name=?, description=?, price=?, department_id=? WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssdsi', $service_name, $description, $price, $department_id, $id);
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
                          <th>Created At</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $query = "SELECT s.*, d.name as department_name FROM services s LEFT JOIN departments d ON s.department_id = d.id";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_object()):
                        ?>
                          <tr>
                            <td><?php echo htmlentities($row->service_name); ?></td>
                            <td><?php echo htmlentities($row->description); ?></td>
                            <td><?php echo htmlentities($row->price); ?></td>
                            <td><?php echo htmlentities($row->department_name); ?></td>
                            <td><?php echo htmlentities(date('Y-m-d H:i', strtotime($row->created_at))); ?></td>
                            <td class="text-right">
                              <div class="d-flex">
                                <a href="edit-service.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white me-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-name="<?php echo htmlspecialchars($row->service_name); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-service"><i class="fas fa-trash"></i> </a>
                              </div>
                            </td>
                          </tr>
                        <?php endwhile; ?>
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
