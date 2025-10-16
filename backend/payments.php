<?php
session_start();
include_once('database/db_connect.php'); // Assuming db_connect.php handles $conn

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$success_message = '';
$error_message = '';

// Handle Delete Payment
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];

  $conn->begin_transaction();
  try {
    $stmt_payment = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $stmt_payment->bind_param("i", $delete_id);

    if (!$stmt_payment->execute()) {
      throw new Exception("Error deleting payment record: " . $stmt_payment->error);
    }

    $stmt_payment->close();
    $conn->commit();

    $success_message = "Payment deleted successfully!";
    header("Location: payments.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Payment: " . $e->getMessage();
    header("Location: payments.php?error=" . urlencode($error_message));
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
            <h3 class="fw-bold mb-3">Payments</h3>
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
                <a href="#">Payments</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-payment.php" class="btn btn-primary btn-round">Add Payment</a>
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
                          <th>Payment ID</th>
                          <th>Invoice ID</th>
                          <th>Patient Name</th>
                          <th>Payment Date</th>
                          <th>Amount</th>
                          <th>Payment Method</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $ret = "SELECT p.*, i.invoiceId, CONCAT(pat.first_name, ' ', pat.last_name) AS patientName FROM payments p JOIN invoices i ON p.invoiceId = i.id JOIN patients pat ON i.patientId = pat.id";
                        $stmt = $conn->prepare($ret);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td><?php echo $row->id; ?></td>
                            <td><?php echo $row->invoiceId; ?></td>
                            <td><?php echo $row->patientName; ?></td>
                            <td><?php echo $row->paymentDate; ?></td>
                            <td>$<?php echo $row->amount; ?></td>
                            <td><?php echo $row->paymentMethod; ?></td>
                            <td class="text-right d-flex">
                                <a href="edit-payment.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-invoice-id="<?php echo htmlspecialchars($row->invoiceId); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-payment"><i class="fas fa-trash"></i> </a>
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
  <div id="delete_payment_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-payment-message">Are you sure you want to delete this payment record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deletePaymentForm" method="POST" action="payments.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-payment-id">
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
      $('.btn-delete-payment').on('click', function(e) {
        e.preventDefault();
        var paymentId = $(this).data('id');
        var invoiceId = $(this).data('invoice-id');
        $('#delete-payment-id').val(paymentId);
        $('#delete-payment-message').text("Are you sure you want to delete the payment for Invoice ID '" + invoiceId + "'?");
        $('#delete_payment_modal').modal('show');
      });
    });
  </script>
</body>

</html>
