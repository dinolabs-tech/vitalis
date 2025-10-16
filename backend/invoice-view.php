<?php
session_start();
include 'includes/config.php';
include 'includes/checklogin.php';
check_login();

$invoiceId = $_GET['id'] ?? '';
if (!$invoiceId) {
  header("Location: invoices.php?error=" . urlencode("Invalid invoice ID"));
  exit;
}

// Fetch invoice header
$stmt = $conn->prepare("
    SELECT i.*, 
           CONCAT(p.first_name, ' ', p.last_name) AS patientName, 
           p.email AS patientEmail, 
           p.phone AS patientPhone,
            l.staffname AS doctorName
    FROM invoices i
    JOIN patients p ON i.patientId = p.id
    LEFT JOIN login l ON i.doctorId = l.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
  header("Location: invoices.php?error=" . urlencode("Invoice not found"));
  exit;
}

// Fetch items
$stmt = $conn->prepare("SELECT * FROM invoice_items WHERE invoiceId = ?");
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$invoice_items_result = $stmt->get_result();
$stmt->close();

$all_items = [];
$current_subtotal = 0;

// Add existing invoice items
while ($item = $invoice_items_result->fetch_assoc()) {
    $all_items[] = [
        'itemName' => $item['itemName'],
        'description' => $item['description'],
        'unitCost' => $item['unitCost'],
        'quantity' => $item['quantity'],
        'total' => $item['unitCost'] * $item['quantity']
    ];
    $current_subtotal += ($item['unitCost'] * $item['quantity']);
}

// Fetch patient_id from the invoice
$patientId = $invoice['patientId'];

// Fetch Lab Tests from patient_bills
$stmt = $conn->prepare("
    SELECT pb.description, pb.quantity, pb.unit_price
    FROM patient_bills pb
    WHERE pb.patient_id = ? AND pb.item_type = 'lab_test'
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$lab_tests_result = $stmt->get_result();
$stmt->close();

while ($lab_test = $lab_tests_result->fetch_assoc()) {
    $all_items[] = [
        'itemName' => 'Lab Test',
        'description' => $lab_test['description'],
        'unitCost' => $lab_test['unit_price'],
        'quantity' => $lab_test['quantity'],
        'total' => $lab_test['unit_price'] * $lab_test['quantity']
    ];
    $current_subtotal += ($lab_test['unit_price'] * $lab_test['quantity']);
}

// Fetch Radiology Records from patient_bills
$stmt = $conn->prepare("
    SELECT pb.description, pb.quantity, pb.unit_price
    FROM patient_bills pb
    WHERE pb.patient_id = ? AND pb.item_type = 'radiology_record'
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$radiology_records_result = $stmt->get_result();
$stmt->close();

while ($radiology_record = $radiology_records_result->fetch_assoc()) {
    $all_items[] = [
        'itemName' => 'Radiology Record',
        'description' => $radiology_record['description'],
        'unitCost' => $radiology_record['unit_price'],
        'quantity' => $radiology_record['quantity'],
        'total' => $radiology_record['unit_price'] * $radiology_record['quantity']
    ];
    $current_subtotal += ($radiology_record['unit_price'] * $radiology_record['quantity']);
}

// Fetch General Expenses from patient_bills
$stmt = $conn->prepare("
    SELECT pb.description, pb.quantity, pb.unit_price
    FROM patient_bills pb
    WHERE pb.patient_id = ? AND pb.item_type = 'expense'
");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$expenses_result = $stmt->get_result();
$stmt->close();

while ($expense = $expenses_result->fetch_assoc()) {
    $all_items[] = [
        'itemName' => 'General Expense',
        'description' => $expense['description'],
        'unitCost' => $expense['unit_price'],
        'quantity' => $expense['quantity'],
        'total' => $expense['unit_price'] * $expense['quantity']
    ];
    $current_subtotal += ($expense['unit_price'] * $expense['quantity']);
}

// Calculate total amount including tax (assuming tax is a percentage of subtotal)
$tax_rate = 0; // Assuming 0% tax for now, or fetch from config if available
$calculated_tax = $current_subtotal * $tax_rate;
$calculated_total_amount = $current_subtotal + $calculated_tax;


// Fetch payment details
$stmt = $conn->prepare("SELECT * FROM invoice_payments WHERE invoiceId = ?");
$stmt->bind_param("i", $invoiceId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
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
            <h4 class="page-title">Invoice View</h4>
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
                <a href="#">Invoice View</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">

                  <!-- Invoice Header -->
                  <div class="row custom-invoice">
                    <div class="col-6 m-b-20">
                      <img src="assets/img/logo-dark.png" class="inv-logo" alt="">
                      <ul class="list-unstyled">
                        <li><strong>Vitalis</strong></li>
                        <li>5th Floor Wing-B</li>
                        <li>TISCO Building Alagbaka Akure</li>
                        <li>Ondo State, Nigeria</li>
                      </ul>
                    </div>
                    <div class="col-6 m-b-20">
                      <div class="invoice-details">
                        <h3 class="text-uppercase">Invoice #<?php echo htmlspecialchars($invoice['invoiceId']); ?></h3>
                        <ul class="list-unstyled">
                          <li>Date: <span><?php echo date("F j, Y", strtotime($invoice['invoiceDate'])); ?></span></li>
                          <li>Due date: <span><?php echo date("F j, Y", strtotime($invoice['dueDate'])); ?></span></li>
                        </ul>
                      </div>
                    </div>
                  </div>

                  <!-- Patient + Payment Info -->
                  <div class="row">
                    <div class="col-sm-6 m-b-20">
                      <h5>Invoice to:</h5>
                      <ul class="list-unstyled">
                        <li>
                          <h5><strong><?php echo htmlspecialchars($invoice['patientName']); ?></strong></h5>
                        </li>
                        <li><?php echo htmlspecialchars($invoice['patientEmail']); ?></li>
                        <li><?php echo htmlspecialchars($invoice['patientPhone']); ?></li>
                      </ul>
                    </div>
                    <div class="col-sm-6 m-b-20">
                      <div class="invoices-view">
                        <span class="text-muted">Payment Details:</span>
                        <ul class="list-unstyled invoice-payment-details">
                          <li>
                            <h5>Total Due: <span class="text-right">$<?php echo number_format($invoice['totalAmount'], 2); ?></span></h5>
                          </li>
                          <?php if ($payment): ?>
                            <li>Bank name: <span><?php echo htmlspecialchars($payment['bankName']); ?></span></li>
                            <li>Country: <span><?php echo htmlspecialchars($payment['country']); ?></span></li>
                            <li>City: <span><?php echo htmlspecialchars($payment['city']); ?></span></li>
                            <li>Address: <span><?php echo htmlspecialchars($payment['address']); ?></span></li>
                            <li>IBAN: <span><?php echo htmlspecialchars($payment['iban']); ?></span></li>
                            <li>SWIFT code: <span><?php echo htmlspecialchars($payment['swiftCode']); ?></span></li>
                          <?php else: ?>
                            <li>No payment details available</li>
                          <?php endif; ?>
                        </ul>
                      </div>
                    </div>
                  </div>

                  <hr>

                  <!-- Items -->
                  <div class="table-responsive">
                    <table class="table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>ITEM</th>
                          <th>DESCRIPTION</th>
                          <th>UNIT COST</th>
                          <th>QUANTITY</th>
                          <th>TOTAL</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $counter = 1;
                        foreach ($all_items as $item): ?>
                          <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($item['itemName']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>$<?php echo number_format($item['unitCost'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['total'], 2); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Totals -->
                  <div class="row invoice-payment">
                    <div class="col-sm-7"></div>
                    <div class="col-sm-5">
                      <div class="m-b-20">
                        <h6>Total due</h6>
                        <div class="table-responsive no-border">
                          <table class="table mb-0">
                            <tbody>
                              <tr>
                                <th>Subtotal:</th>
                                <td class="text-right">$<?php echo number_format($current_subtotal, 2); ?></td>
                              </tr>
                              <tr>
                                <th>Tax:</th>
                                <td class="text-right">$<?php echo number_format($calculated_tax, 2); ?></td>
                              </tr>
                              <tr>
                                <th>Total:</th>
                                <td class="text-right text-primary">
                                  <h5>$<?php echo number_format($calculated_total_amount, 2); ?></h5>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>

                  <hr>

                  <!-- Notes -->
                  <div class="invoice-info">
                    <h5>Other information</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($invoice['notes'] ?? '')); ?></p>
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
  <?php include('components/script.php'); ?>
</body>

</html>
