<?php
session_start();
include 'includes/config.php';
include 'includes/checklogin.php';

// Handle delete
if (isset($_POST['id'])) {
  $delete_id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
  $stmt->bind_param("i", $delete_id);
  if ($stmt->execute()) {
    $success_message = "Invoice deleted successfully!";
    header("Location: invoices.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error Deleting Invoice!";
    header("Location: invoices.php?error=" . urlencode($error_message));
    exit();
  }
  $stmt->close();
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
            <h3 class="fw-bold mb-3">Invoices</h3>
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
                <a href="#">Invoices</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-invoice.php" class="btn btn-primary btn-round">Add Invoice</a>
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
                          <th>Invoice #</th>
                          <th>Patient Name</th>
                          <th>Doctor Name</th>
                          <th>Invoice Date</th>
                          <th>Due Date</th>
                          <th>Total Amount</th>
                          <th>Status</th>
                          <th class="text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sql = "SELECT i.*, l.staffname AS doctorStaffName, 
                                                           CONCAT(p.first_name, ' ', p.last_name) AS patientName                                                          
                                                    FROM invoices i
                                                    JOIN patients p ON i.patientId = p.id
                                                    LEFT JOIN login l ON i.doctorId = l.id
                                                    ORDER BY i.created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        while ($row = $res->fetch_object()) {
                        ?>
                          <tr>
                            <td>
                              <a href="invoice-view.php?id=<?php echo $row->id; ?>">
                                <?php echo htmlspecialchars($row->invoiceId); ?>
                              </a>
                            </td>
                            <td><?php echo htmlspecialchars($row->patientName); ?></td>
                            <td><?php echo htmlspecialchars($row->doctorStaffName ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($row->invoiceDate); ?></td>
                            <td><?php echo htmlspecialchars($row->dueDate); ?></td>
                            <td>$<?php echo number_format($row->totalAmount, 2); ?></td>
                            <td>
                              <span class="badge 
                                <?php echo $row->status == 'Paid' ? 'badge-success' : ($row->status == 'Pending' ? 'badge-warning' : 'badge-danger'); ?>">
                                <?php echo htmlspecialchars($row->status); ?>
                              </span>
                            </td>

                            <td class="text-right d-flex">
                                <a  href="invoice-view.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white"><i class="fas fa-eye"></i></a>
                                <a  href="edit-invoice.php?id=<?php echo $row->id; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                <a href="#" data-id="<?php echo $row->id; ?>" data-invoice-number="<?php echo htmlspecialchars($row->invoiceId); ?>" data-patient-name="<?php echo htmlspecialchars($row->patientName); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-invoice"><i class="fas fa-trash"></i> </a>
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
  <div id="delete_invoice_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-invoice-message">Are you sure you want to delete this invoice?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteInvoiceForm" method="POST" action="invoices.php" style="display: inline;">
              <input type="hidden" name="id" id="delete-invoice-id">
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
      $('.btn-delete-invoice').on('click', function(e) {
        e.preventDefault();
        var invoiceId = $(this).data('id');
        var invoiceNumber = $(this).data('invoice-number');
        var patientName = $(this).data('patient-name');
        $('#delete-invoice-id').val(invoiceId);
        $('#delete-invoice-message').text("Are you sure you want to delete invoice '" + invoiceNumber + "' for '" + patientName + "'?");
        $('#delete_invoice_modal').modal('show');
      });
    });
  </script>
</body>

</html>
