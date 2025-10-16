<?php
session_start();
include_once('database/db_connect.php');
include_once('includes/config.php'); // Ensure $conn is defined here

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$invoice_id_param = $_GET['id'] ?? '';
$invoice_record = null;
$invoice_items = [];
$invoice_bank_details = null;
$invoice_transactions = [];

// Fetch products
$products = [];
$ret_products = "SELECT id, name, sell_price FROM products";
$stmt_products = $conn->prepare($ret_products);
$stmt_products->execute();
$res_products = $stmt_products->get_result();
while ($row_product = $res_products->fetch_object()) {
  $products[] = $row_product;
}
$stmt_products->close();

// Fetch services
$services = [];
$ret_services = "SELECT id, service_name, price FROM services";
$stmt_services = $conn->prepare($ret_services);
$stmt_services->execute();
$res_services = $stmt_services->get_result();
while ($row_service = $res_services->fetch_object()) {
  $services[] = $row_service;
}
$stmt_services->close();

// Fetch patients
$patients = [];
$ret_patients = "SELECT id, first_name, last_name FROM patients";
$stmt_patients = $conn->prepare($ret_patients);
$stmt_patients->execute();
$res_patients = $stmt_patients->get_result();
while ($row_patient = $res_patients->fetch_object()) {
  $patients[] = $row_patient;
}
$stmt_patients->close();

// Fetch doctors
$doctors = [];
$ret_doctors = "SELECT* FROM login WHERE role='doctor'";
$stmt_doctors = $conn->prepare($ret_doctors);
$stmt_doctors->execute();
$res_doctors = $stmt_doctors->get_result();
while ($row_doctor = $res_doctors->fetch_object()) {
  $doctors[] = $row_doctor;
}
$stmt_doctors->close();

if (!empty($invoice_id_param)) {
  // Fetch invoice details
  $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
  $stmt->bind_param("i", $invoice_id_param);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $invoice_record = $result->fetch_assoc();
  } else {
    $error_message = "Invoice record not found.";
  }
  $stmt->close();

  // Fetch invoice items
  if ($invoice_record) {
    $stmt_items = $conn->prepare("SELECT * FROM invoice_items WHERE invoiceId = ?");
    $stmt_items->bind_param("i", $invoice_id_param);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row_item = $result_items->fetch_assoc()) {
      $invoice_items[] = $row_item;
    }
    $stmt_items->close();

    // Fetch invoice bank details
    $stmt_bank_details = $conn->prepare("SELECT * FROM invoice_payments WHERE invoiceId = ?");
    $stmt_bank_details->bind_param("i", $invoice_id_param);
    $stmt_bank_details->execute();
    $result_bank_details = $stmt_bank_details->get_result();
    if ($result_bank_details && $result_bank_details->num_rows > 0) {
      $invoice_bank_details = $result_bank_details->fetch_assoc();
    }
    $stmt_bank_details->close();

    // Fetch payment transactions
    $stmt_transactions = $conn->prepare("SELECT * FROM payments WHERE invoiceId = ? ORDER BY paymentDate DESC");
    $stmt_transactions->bind_param("i", $invoice_id_param);
    $stmt_transactions->execute();
    $result_transactions = $stmt_transactions->get_result();
    while ($row_transaction = $result_transactions->fetch_assoc()) {
      $invoice_transactions[] = $row_transaction;
    }
    $stmt_transactions->close();
  }
} else {
  $error_message = "No invoice ID provided.";
}

/* ----------------------
   Handle invoice update
-------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_invoice']) && $invoice_record) {
  $patientId   = $_POST['patientId'] ?? '';
  $doctorId    = $_POST['doctorId'] ?? '';
  $invoiceDate = $_POST['invoiceDate'] ?? '';
  $dueDate     = $_POST['dueDate'] ?? '';
  $status      = $_POST['status'] ?? 'Pending';
  $notes       = $_POST['notes'] ?? '';

  $subtotal = 0;
  if (isset($_POST['item_total'])) {
    foreach ($_POST['item_total'] as $item_total) {
      $subtotal += (float)$item_total;
    }
  }
  $tax         = $subtotal * 0.07;
  $totalAmount = $subtotal + $tax;

  if (empty($patientId) || empty($invoiceDate) || empty($dueDate)) {
    $error_message = "Please fill in all required invoice fields (Patient, Invoice Date, Due Date).";
  } else {
    $query_invoice = "UPDATE invoices SET patientId=?, doctorId=?, invoiceDate=?, dueDate=?, subtotal=?, tax=?, totalAmount=?, status=?, notes=? WHERE id=?";
    $stmt_invoice = $conn->prepare($query_invoice);
    $stmt_invoice->bind_param('iissddsssi', $patientId, $doctorId, $invoiceDate, $dueDate, $subtotal, $tax, $totalAmount, $status, $notes, $invoice_id_param);

    if ($stmt_invoice->execute()) {
      $conn->query("DELETE FROM invoice_items WHERE invoiceId=$invoice_id_param");
      if (isset($_POST['item_type'])) {
        $item_types   = $_POST['item_type'];
        $item_ids     = $_POST['item_id'];
        $quantities   = $_POST['quantity'];
        $unit_prices  = $_POST['unit_price'];

        for ($i = 0; $i < count($item_types); $i++) {
          $item_type  = $item_types[$i];
          $item_id    = $item_ids[$i];
          $quantity   = $quantities[$i];
          $unit_price = $unit_prices[$i];

          $description = '';
          if ($item_type == 'product') {
            foreach ($products as $product) {
              if ($product->id == $item_id) {
                $description = $product->name;
                break;
              }
            }
          } elseif ($item_type == 'service') {
            foreach ($services as $service) {
              if ($service->id == $item_id) {
                $description = $service->service_name;
                break;
              }
            }
          }

          $query_item = "INSERT INTO invoice_items (invoiceId, itemName, description, unitCost, quantity) VALUES (?,?,?,?,?)";
          $stmt_item  = $conn->prepare($query_item);
          $stmt_item->bind_param('issdi', $invoice_id_param, $description, $description, $unit_price, $quantity);
          $stmt_item->execute();
          $stmt_item->close();
        }
      }

      // Bank details
      $bankName  = $_POST['bankName'] ?? '';
      $country   = $_POST['country'] ?? '';
      $city      = $_POST['city'] ?? '';
      $address   = $_POST['address'] ?? '';
      $iban      = $_POST['iban'] ?? '';
      $swiftCode = $_POST['swiftCode'] ?? '';

      if ($invoice_bank_details) {
        $query_bank = "UPDATE invoice_payments SET bankName=?, country=?, city=?, address=?, iban=?, swiftCode=? WHERE invoiceId=?";
        $stmt_bank  = $conn->prepare($query_bank);
        $stmt_bank->bind_param('ssssssi', $bankName, $country, $city, $address, $iban, $swiftCode, $invoice_id_param);
      } else {
        $query_bank = "INSERT INTO invoice_payments (invoiceId, bankName, country, city, address, iban, swiftCode) VALUES (?,?,?,?,?,?,?)";
        $stmt_bank  = $conn->prepare($query_bank);
        $stmt_bank->bind_param('issssss', $invoice_id_param, $bankName, $country, $city, $address, $iban, $swiftCode);
      }
      $stmt_bank->execute();
      $stmt_bank->close();

      $success_message = "Invoice updated successfully!";
      header("Location: invoices.php?id=$invoice_id_param&success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Error updating invoice: " . $stmt_invoice->error;
    }
    $stmt_invoice->close();
  }
}

/* ----------------------
   Handle payment entry
-------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment']) && $invoice_record) {
  $amount = $_POST['amount'] ?? 0;
  $payment_method = $_POST['payment_method'] ?? 'Cash';
  $payment_date = $_POST['payment_date'] ?? date('Y-m-d');

  if ($amount > 0) {
    $stmt_payment = $conn->prepare("INSERT INTO payments (invoiceId, amount, paymentMethod, paymentDate) VALUES (?,?,?,?)");
    $stmt_payment->bind_param("idss", $invoice_id_param, $amount, $payment_method, $payment_date);
    if ($stmt_payment->execute()) {
      $sum_q = $conn->prepare("SELECT SUM(amount) AS paid FROM payments WHERE invoiceId=?");
      $sum_q->bind_param("i", $invoice_id_param);
      $sum_q->execute();
      $res_sum = $sum_q->get_result()->fetch_assoc();
      $paid = $res_sum['paid'] ?? 0;
      $sum_q->close();

      $new_status = ($paid >= $invoice_record['totalAmount']) ? 'Paid' : 'Partially Paid';
      $conn->query("UPDATE invoices SET status='$new_status' WHERE id=$invoice_id_param");

      $success_message = "Payment recorded successfully!";
      header("Location: edit-invoice.php?id=$invoice_id_param");
      exit;
    } else {
      $error_message = "Failed to record payment!";
    }
    $stmt_payment->close();
  } else {
    $error_message = "Invalid payment amount.";
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
            <h4 class="page-title">Edit Invoice</h4>
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
                <a href="invoices.php">Invoices</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Invoice</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
              <?php endif; ?>
              <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($invoice_record): ?>
            <div class="row">
              <div class="col-md-8">
                <div class="card">
                  <div class="card-body">
                    <!-- Invoice form -->
                    <form method="post">
                      <input type="hidden" name="update_invoice" value="1">
                      <!-- Example fields -->
                      <div class="form-group">
                        <select name="patientId" class="form-control form-select searchable-dropdown mt-5">
                          <?php foreach ($patients as $p): ?>
                            <option value="">Select Patient</option>
                            <option value="<?php echo $p->id; ?>" <?php if ($p->id == $invoice_record['patientId']) echo 'selected'; ?>>
                              <?php echo $p->first_name . ' ' . $p->last_name; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <select name="doctorId" class="form-control form-select searchable-dropdown mt-5">
                          <?php foreach ($doctors as $d): ?>
                            <option value="">Select Doctor</option>
                            <option value="<?php echo $d->id; ?>" <?php if ($d->id == $invoice_record['doctorId']) echo 'selected'; ?>>
                              <?php echo $d->staffname; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label>Invoice Date</label>
                        <input type="date" name="invoiceDate" value="<?php echo $invoice_record['invoiceDate']; ?>" class="form-control">
                      </div>
                      <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="dueDate" value="<?php echo $invoice_record['dueDate']; ?>" class="form-control">
                      </div>
                      <div class="form-group">
                        <select name="status" class="form-control form-select">
                          <option value="">Select Payment Method</option>
                          <option value="Pending" <?php if ($invoice_record['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                          <option value="Partially Paid" <?php if ($invoice_record['status'] == 'Partially Paid') echo 'selected'; ?>>Partially Paid</option>
                          <option value="Paid" <?php if ($invoice_record['status'] == 'Paid') echo 'selected'; ?>>Paid</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <textarea name="notes" placeholder="Notes" class="form-control"><?php echo $invoice_record['notes']; ?></textarea>
                      </div>
                      <div class="col-md-12 text-center">
                        <div class="form-group">
                          <button type="submit" class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Payments Section -->
              <div class="col-md-4">
                <div class="card">
                  <div class="card-header">
                    <h5>Payments</h5>
                  </div>
                  <div class="card-body">
                    <form method="post">
                      <input type="hidden" name="add_payment" value="1">
                      <div class="form-group">
                        <input type="number" placeholder="Amount" name="amount" step="0.01" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <select name="payment_method" class="form-control form-select">
                          <option value="" selected disabled>Payment Method</option>
                          <option value="Cash">Cash</option>
                          <option value="Card">Card</option>
                          <option value="Transfer">Transfer</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <input type="date" placeholder="Payment Date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                      </div>
                      <div class="text-center">
                        <button type="submit" class="btn btn-success btn-icon btn-round"><i class="fas fa-plus"></i></button>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="card mt-3">
                  <div class="card-header">
                    <h6>Payment History</h6>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm" id="basic-datatables">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Amount</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($invoice_transactions as $pay): ?>
                            <tr>
                              <td><?php echo $pay['paymentDate']; ?></td>
                              <td><?php echo $pay['paymentMethod']; ?></td>
                              <td><?php echo number_format($pay['amount'], 2); ?></td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (empty($invoice_transactions)): ?>
                            <tr>
                              <td colspan="3">No payments yet.</td>
                            </tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php include('components/footer.php'); ?>
    </div>
  </div>
  <?php include('components/script.php'); ?>
</body>

</html>