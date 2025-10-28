<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Handle Delete Salary
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_salary = $conn->prepare("DELETE FROM salary WHERE id = ?");
    $stmt_salary->bind_param("i", $delete_id);

    if (!$stmt_salary->execute()) {
      throw new Exception("Error deleting salary record: " . $stmt_salary->error);
    }

    $stmt_salary->close();
    $conn->commit();

    $success_message = "Salary record deleted successfully!";
    header("Location: salary.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Salary record: " . $e->getMessage();
    header("Location: salary.php?error=" . urlencode($error_message));
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
            <h3 class="fw-bold mb-3">Salary</h3>
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
                <a href="#">Salary</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-salary.php" class="btn btn-primary btn-round">Add Salary</a>
              </div>
            <?php endif; ?>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Employee Name</th>
                          <th>Basic Salary</th>
                          <th>Total Additions</th>
                          <th>Total Deductions</th>
                          <th>Net Salary</th>
                          <th>Branch</th>
                          <th>Salary Date</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sql = "SELECT s.*, e.staffname AS employeeName, b.branch_name 
                                FROM salary s 
                                JOIN login e ON s.employee_id = e.id 
                                LEFT JOIN branches b ON e.branch_id = b.branch_id";
                        
                        $conditions = [];
                        $params = [];
                        $types = "";

                        if ($_SESSION['role'] !== 'admin' && isset($_SESSION['branch_id'])) {
                            $conditions[] = "e.branch_id = ?";
                            $params[] = $_SESSION['branch_id'];
                            $types .= "i";
                        }

                        if (!empty($conditions)) {
                            $sql .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $stmt = $conn->prepare($sql);
                        if (!empty($params)) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->employeeName; ?></td>
                            <td>$<?php echo $row->basic_salary; ?></td>
                            <td>$<?php echo $row->total_additions; ?></td>
                            <td>$<?php echo $row->total_deductions; ?></td>
                            <td>$<?php echo $row->net_salary; ?></td>
                            <td><?php echo htmlspecialchars($row->branch_name ?? 'N/A'); ?></td>
                            <td><?php echo $row->salary_date; ?></td>
                            <td class="text-right d-flex">
                                <a href="salary-view.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white"><i class="fas fa-eye"></i></a>
                                <a href="edit-salary.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-employee-name="<?php echo htmlspecialchars($row->employeeName); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-salary"><i class="fas fa-trash"></i> </a>
                               </td>
                          </tr>
                        <?php } ?>
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
  <div id="delete_salary_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-salary-message">Are you sure you want to delete this salary record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteSalaryForm" method="POST" action="salary.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-salary-id">
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
      $('.btn-delete-salary').on('click', function(e) {
        e.preventDefault();
        var salaryId = $(this).data('id');
        var employeeName = $(this).data('employee-name');
        $('#delete-salary-id').val(salaryId);
        $('#delete-salary-message').text("Are you sure you want to delete the salary record for '" + employeeName + "'?");
        $('#delete_salary_modal').modal('show');
      });
    });
  </script>
</body>

</html>
