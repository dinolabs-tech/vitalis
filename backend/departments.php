<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin'])) {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';
$departments = [];
$sql = "SELECT id, name, description FROM departments ORDER BY name";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
  }
}

if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Department deleted successfully!";
  } else {
    $error_message = "Error deleting department";
  }
  $stmt->close();
  header("Location: departments.php?success=" . urlencode($success_message));
  exit;
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
            <h3 class="fw-bold mb-3">Departments</h3>
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
                <a href="#">Departments</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'receptionist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-department.php" class="btn btn-primary btn-round">Add Department</a>
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
                          <th>S/N</th>
                          <th>Department Name</th>
                          <th>Description</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($departments)): ?>
                          <tr>
                            <td colspan="4" class="text-center">No departments found.</td>
                          </tr>
                        <?php else: ?>
                          <?php $count = 1;
                          foreach ($departments as $department): ?>
                            <tr>
                              <td><?php echo $count++; ?></td>
                              <td><?php echo htmlspecialchars($department['name']); ?></td>
                              <td><?php echo htmlspecialchars($department['description']); ?></td>
                              <td class="text-right d-flex">
                                  <a href="edit-department.php?id=<?php echo $department['id']; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $department['id']; ?>" data-department-name="<?php echo htmlspecialchars($department['name']); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-department"><i class="fas fa-trash"></i></a>
                             </td>
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
      </div>

      <div id="delete_department_modal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-body text-center">
              <img src="assets/img/sent.png" alt="" width="50" height="46">
              <h3 id="delete-department-message">Are you sure you want to delete this department?</h3>
              <div class="m-t-20">
                <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
                <form id="deleteDepartmentForm" method="POST" action="departments.php" style="display: inline;">
                  <input type="hidden" name="id" id="delete-department-id">
                  <button type="submit" class="btn btn-danger rounded">Delete</button>
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
      $('.btn-delete-department').on('click', function(e) {
        e.preventDefault();
        var departmentId = $(this).data('id');
        var departmentName = $(this).data('department-name');
        $('#delete-department-id').val(departmentId);
        $('#delete-department-message').text("Are you sure you want to delete the department '" + departmentName + "'?");
        $('#delete_department_modal').modal('show');
      });
    });
  </script>
</body>

</html>
