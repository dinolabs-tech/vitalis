<?php include 'includes/config.php'; ?>
<?php include 'includes/checklogin.php'; ?>
<?php
session_start();

// Ensure only admin can access this page
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Fetch invoices for dropdown
$invoices_dropdown = [];
$result_invoices = $mysqli->query("SELECT id, invoiceId, patientId, totalAmount FROM invoices ORDER BY invoiceId ASC");
if ($result_invoices) {
  while ($row = $result_invoices->fetch_assoc()) {
    $invoices_dropdown[] = $row;
  }
}

// Fetch patients for dropdown
$patients_dropdown = [];
$result_patients = $mysqli->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM patients ORDER BY name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients_dropdown[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $invoice_id = $_POST['invoice_id'] ?? null;
  $patient_id = $_POST['patient_id'] ?? null;
  $payment_method = $_POST['payment_type'] ?? ''; // Renamed to payment_method to match schema
  $amount = $_POST['amount'] ?? '';
  $payment_date_raw = $_POST['payment_date'] ?? '';
  $notes = $_POST['notes'] ?? '';

  if (empty($payment_method) || empty($amount) || empty($payment_date_raw)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Convert payment_date from DD/MM/YYYY to YYYY-MM-DD for database
    $date_obj = DateTime::createFromFormat('d/m/Y', $payment_date_raw);
    if ($date_obj) {
      $payment_date = $date_obj->format('Y-m-d');
    } else {
      $error_message = "Invalid payment date format. Please use DD/MM/YYYY.";
    }
  }

  // Only proceed with database insertion if there are no errors
  if (empty($error_message)) {
    // If invoice_id is provided, try to get patient_id from invoice
    if (!empty($invoice_id)) {
      $stmt_invoice_patient = $mysqli->prepare("SELECT patientId FROM invoices WHERE id = ?");
      $stmt_invoice_patient->bind_param("i", $invoice_id);
      $stmt_invoice_patient->execute();
      $result_invoice_patient = $stmt_invoice_patient->get_result();
      if ($result_invoice_patient && $result_invoice_patient->num_rows > 0) {
        $invoice_data = $result_invoice_patient->fetch_assoc();
        $patient_id = $invoice_data['patientId'];
      }
      $stmt_invoice_patient->close();
    }

    // Generate a unique paymentId
    $paymentId = 'PAY-' . uniqid();

    $stmt = $mysqli->prepare("INSERT INTO payments (paymentId, invoiceId, paymentMethod, amount, paymentDate, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisdss", $paymentId, $invoice_id, $payment_method, $amount, $payment_date, $notes);

    if ($stmt->execute()) {
      $success_message = "Payment added successfully!";
      // Log audit
      $user_id = $_SESSION['id']; // Use $_SESSION['id']
      $action = "Added payment ID: " . $paymentId . " for invoice ID: " . ($invoice_id ?? 'N/A') . " (Patient ID: " . ($patient_id ?? 'N/A') . ")";
      $details = json_encode($_POST);
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $mysqli->query("INSERT INTO audit_logs (userName, action, module, ipAddress) VALUES ('{$_SESSION['username']}', '$action', 'Payments', '$ip_address')"); // Assuming username is in session

      // Redirect to payments.php
      header("Location: payments.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Error adding payment: " . $stmt->error;
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
            <h4 class="page-title">Add Payment</h4>
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
                <a href="#">Add Payment</a>
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
              <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  Payment added successfully!
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
                    <div class="col-md-6">
                      <div class="form-group">
                        <select class="form-control form-select searchable-dropdown mt-5" style="border:1px solid red;" name="invoice_id">
                          <option value="" selected disabled>Select Invoice</option>
                          <?php foreach ($invoices_dropdown as $invoice): ?>
                            <option value="<?php echo $invoice['id']; ?>" data-patient-id="<?php echo $invoice['patientId']; ?>" <?php echo (isset($_POST['invoice_id']) && $_POST['invoice_id'] == $invoice['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($invoice['invoiceId']); ?> (Total: $<?php echo number_format($invoice['totalAmount'], 2); ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select class="form-control form-select searchable-dropdown mt-5" name="patient_id" id="patient_id_select">
                            <option value="" selected disabled>Select Patient (Optional)</option>
                            <?php foreach ($patients_dropdown as $patient): ?>
                              <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['name']); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <select class="form-control form-select" style="border:1px solid red;" name="payment_type" required>
                            <option value="" selected disabled>Select Payment Method</option>
                            <option value="Cash" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Card" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                            <option value="Bank Transfer" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Cheque" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <input class="form-control" type="text" style="border:1px solid red;" placeholder="Amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <div class="cal-icon">
                            <input class="form-control datetimepicker" placeholder="Payment Date" style="border:1px solid red;" type="text" name="payment_date" value="<?php echo htmlspecialchars($_POST['payment_date'] ?? date('d/m/Y')); ?>" required>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                      </div>
                        <div class="col-md-12 text-center">
                          <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
                        </div>
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
      // Initialize datetimepicker with DD/MM/YYYY format
      $('.datetimepicker').datetimepicker({
        format: 'DD/MM/YYYY'
      });

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