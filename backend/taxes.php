<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Delete Tax
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_tax = $conn->prepare("DELETE FROM taxes WHERE id = ?");
    $stmt_tax->bind_param("i", $delete_id);

    if (!$stmt_tax->execute()) {
      throw new Exception("Error deleting tax record: " . $stmt_tax->error);
    }

    $stmt_tax->close();
    $conn->commit();

    $success_message = "Tax deleted successfully!";
    header("Location: taxes.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Tax: " . $e->getMessage();
    header("Location: taxes.php?error=" . urlencode($error_message));
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
            <ul class="breadcrumbs">
              <li class="nav-home">
                <a href="index.php">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="taxes.php">Taxes</a>
              </li>

            </ul>
          </div>
          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
              <h4 class="fw-bold mb-3">Taxes</h4>
            </div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-tax.php" class="btn btn-primary btn-round">Add Tax</a>
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
                          <th>Tax Name</th>
                          <th>Tax Rate (%)</th>
                          <th>Status</th>
                          <th>Description</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT * FROM taxes";
                        $stmt = $conn->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->tax_name; ?></td>
                            <td><?php echo $row->tax_rate; ?></td>
                            <td><?php echo $row->status; ?></td>
                            <td><?php echo $row->description; ?></td>
                            <td class="text-right">
                                <div class="d-flex">
                                  <a href="edit-tax.php?id=<?php echo $row->id; ?>" class="btn-primary btn-icon btn-round text-white me-2"><i class="fas fa-edit"></i></a>
                                  <a href="#" data-id="<?php echo $row->id; ?>" data-name="<?php echo htmlspecialchars($row->tax_name); ?>" class="btn-icon btn-danger btn-round text-white btn-delete-tax"><i class="fas fa-trash"></i></a>
                                </div>
                              </td>
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
  <div id="delete_tax_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-tax-message">Are you sure you want to delete this tax record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteTaxForm" method="POST" action="taxes.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-tax-id">
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
      $('.btn-delete-tax').on('click', function(e) {
        e.preventDefault();
        var taxId = $(this).data('id');
        var taxName = $(this).data('name');
        $('#delete-tax-id').val(taxId);
        $('#delete-tax-message').text("Are you sure you want to delete the tax '" + taxName + "'?");
        $('#delete_tax_modal').modal('show');
      });
    });
  </script>
</body>

</html>
