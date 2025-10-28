<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Handle Delete Provident Fund
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_provident = $conn->prepare("DELETE FROM provident_fund WHERE id = ?");
    $stmt_provident->bind_param("i", $delete_id);

    if (!$stmt_provident->execute()) {
      throw new Exception("Error deleting provident fund record: " . $stmt_provident->error);
    }

    $stmt_provident->close();
    $conn->commit();

    $success_message = "Provident Fund record deleted successfully!";
    header("Location: provident-fund.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Provident Fund record: " . $e->getMessage();
    header("Location: provident-fund.php?error=" . urlencode($error_message));
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
            <h3 class="fw-bold mb-3">Provident Fund</h3>
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
                <a href="#">Provident Fund</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-provident-fund.php" class="btn btn-primary btn-round">Add Provident Fund</a>
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover table-center mb-0" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>Employee Name</th>
                          <th>Provident Fund Amount</th>
                          <th>Employee Share</th>
                          <th>Organization Share</th>
                          <th>Description</th>
                          <th>Branch</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sql = "SELECT pf.*, l.staffname, b.branch_name 
                                FROM provident_fund pf 
                                JOIN login l ON pf.employeeId = l.id
                                LEFT JOIN branches b ON pf.branch_id = b.branch_id";
                        
                        $conditions = [];
                        $params = [];
                        $types = "";

                        if ($_SESSION['role'] !== 'admin' && isset($_SESSION['branch_id'])) {
                            $conditions[] = "pf.branch_id = ?";
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
                            <td><?php echo $row->staffname; ?></td>
                            <td>$<?php echo $row->providentFundAmount; ?></td>
                            <td>$<?php echo $row->employeeShare; ?></td>
                            <td>$<?php echo $row->organizationShare; ?></td>
                            <td><?php echo $row->description; ?></td>
                            <td><?php echo htmlspecialchars($row->branch_name ?? 'N/A'); ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-provident-fund.php?id=<?php echo $row->id; ?>" class="btn-primary btn-icon btn-round text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-employee-name="<?php echo htmlspecialchars($row->staffname); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-provident"><i class="fas fa-trash"></i></a>
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
  <div id="delete_provident_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-provident-message">Are you sure you want to delete this provident fund record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteProvidentForm" method="POST" action="provident-fund.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-provident-id">
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
      $('.btn-delete-provident').on('click', function(e) {
        e.preventDefault();
        var providentId = $(this).data('id');
        var employeeName = $(this).data('employee-name');
        $('#delete-provident-id').val(providentId);
        $('#delete-provident-message').text("Are you sure you want to delete the provident fund record for '" + employeeName + "'?");
        $('#delete_provident_modal').modal('show');
      });
    });
  </script>
</body>

</html>
