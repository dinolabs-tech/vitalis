<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$payment_id = $_GET['id'] ?? '';
$payment_record = null;

if (!empty($payment_id)) {
  $stmt = $conn->prepare("SELECT p.*, i.patientId FROM payments p LEFT JOIN invoices i ON p.invoiceId = i.id WHERE p.id = ?");
  $stmt->bind_param("i", $payment_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $payment_record = $result->fetch_assoc();
  } else {
    $error_message = "Payment record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No payment ID provided.";
}

// Fetch invoices for dropdown
$invoices_dropdown = [];
$result_invoices = $conn->query("SELECT id, invoiceId, patientId, totalAmount FROM invoices ORDER BY invoiceId ASC");
if ($result_invoices) {
  while ($row = $result_invoices->fetch_assoc()) {
    $invoices_dropdown[] = $row;
  }
}

// Fetch patients for dropdown
$patients_dropdown = [];
$result_patients = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM patients ORDER BY name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients_dropdown[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payment_record) {
  $invoice_id = $_POST['invoice_id'] ?? null;
  $patient_id = $_POST['patient_id'] ?? null;
  $payment_type = $_POST['payment_type'] ?? '';
  $amount = $_POST['amount'] ?? '';
  $payment_date = $_POST['payment_date'] ?? '';
  $notes = $_POST['notes'] ?? '';

  if (empty($payment_type) || empty($amount) || empty($payment_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Convert date to Y-m-d format for MySQL
    $date_obj = DateTime::createFromFormat('d/m/Y', $payment_date);
    if ($date_obj) {
      $formatted_payment_date = $date_obj->format('Y-m-d');
    } else {
      // Handle cases where the date might already be in Y-m-d or another format
      $formatted_payment_date = date('Y-m-d', strtotime($payment_date));
    }

    // The patient_id is not stored in the payments table. It is linked via the invoice.
    $stmt = $conn->prepare("UPDATE payments SET invoiceId = ?, paymentMethod = ?, amount = ?, paymentDate = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("isdssi", $invoice_id, $payment_type, $amount, $formatted_payment_date, $notes, $payment_id);

    if ($stmt->execute()) {
      $success_message = "Payment updated successfully!";


      // Log audit
      $user_name = $_SESSION['username']; // Assuming username is stored in session
      $action = "Updated payment record with ID: " . $payment_id . " for invoice ID: " . ($invoice_id ?? 'N/A');
      $module = "Payments";
      $ipAddress = $_SERVER['REMOTE_ADDR'];
      $stmt_audit = $conn->prepare("INSERT INTO audit_logs (userName, action, module, ipAddress) VALUES (?, ?, ?, ?)");
      $stmt_audit->bind_param("ssss", $user_name, $action, $module, $ipAddress);
      $stmt_audit->execute();
      $stmt_audit->close();

      header("Location: payments.php");
      exit();
    } else {
      $error_message = "Error updating payment: " . $stmt->error;
    }
    $stmt->close();
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
            <h4 class="page-title">Edit Payment</h4>
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
                <a href="payments.php">Payments</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Payment</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">
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

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <?php if ($payment_record): ?>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select searchable-dropdown mt-5" style="border:1px solid red;" name="invoice_id">
                            <option value="">Select Invoice</option>
                            <?php foreach ($invoices_dropdown as $invoice): ?>
                              <option value="<?php echo $invoice['id']; ?>" data-patient-id="<?php echo $invoice['patientId']; ?>" <?php echo ($payment_record['invoiceId'] == $invoice['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($invoice['invoiceId']); ?> (Total: $<?php echo number_format($invoice['totalAmount'], 2); ?>)
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select searchable-dropdown mt-5" name="patient_id" id="patient_id_select">
                            <option value="">Select Patient (Optional)</option>
                            <?php foreach ($patients_dropdown as $patient): ?>
                              <option value="<?php echo $patient['id']; ?>" <?php echo (isset($payment_record['patientId']) && $payment_record['patientId'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <select class="form-control form-select" style="border:1px solid red;" name="payment_type" required>
                            <option value="">Select Payment Type</option>
                            <option value="Cash" <?php echo ($payment_record['paymentMethod'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Card" <?php echo ($payment_record['paymentMethod'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                            <option value="Bank Transfer" <?php echo ($payment_record['paymentMethod'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Cheque" <?php echo ($payment_record['paymentMethod'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-4">

                        <div class="form-group">
                          <input class="form-control" type="text" placeholder="Amount" style="border:1px solid red;" name="amount" value="<?php echo htmlspecialchars($payment_record['amount']); ?>" required>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <input class="form-control datetimepicker" placeholder="Date" style="border:1px solid red;" type="text" name="payment_date" value="<?php echo htmlspecialchars(date('d/m/Y', strtotime($payment_record['paymentDate']))); ?>" required>
                        </div>
                      </div>

                      <div class="col-md-12">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($payment_record['notes']); ?></textarea>
                        </div>
                      </div>

                      <div class="col-md-12 text-center">
                        <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p>No payment record to edit.</p>
                    <?php endif; ?>
                  </form>
                </div>
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
      $('select[name="invoice_id"]').on('change', function() {
        var selectedInvoice = $(this).find('option:selected');
        var patientId = selectedInvoice.data('patient-id');
        if (patientId) {
          $('#patient_id_select').val(patientId).trigger('change');
        } else {
          $('#patient_id_select').val('').trigger('change');
        }
      });
    });
  </script>
</body>

</html>