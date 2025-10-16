<?php
include 'includes/config.php';
include 'includes/checklogin.php';
session_start();

// Fetch products
$products = [];
$ret_products = "SELECT id, name, sell_price FROM products";
$stmt_products = $mysqli->prepare($ret_products);
$stmt_products->execute();
$res_products = $stmt_products->get_result();
while ($row_product = $res_products->fetch_object()) {
  $products[] = $row_product;
}
$stmt_products->close();

// Fetch services
$services = [];
$ret_services = "SELECT id, service_name, price FROM services";
$stmt_services = $mysqli->prepare($ret_services);
$stmt_services->execute();
$res_services = $stmt_services->get_result();
while ($row_service = $res_services->fetch_object()) {
  $services[] = $row_service;
}
$stmt_services->close();

// Fetch bed space and room fees from settings
$bed_space_fee = 0;
$room_fee = 0;

$result_fees = $mysqli->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('bed_space_fee', 'room_fee')");
if ($result_fees) {
    while ($row_fee = $result_fees->fetch_assoc()) {
        if ($row_fee['setting_key'] == 'bed_space_fee') {
            $bed_space_fee = $row_fee['setting_value'];
        } elseif ($row_fee['setting_key'] == 'room_fee') {
            $room_fee = $row_fee['setting_value'];
        }
    }
    $result_fees->free();
}

if (isset($_POST['submit'])) {
  // Auto-generate invoice ID
  $invoiceId = 'INV-' . strtoupper(uniqid());

  $patientId   = $_POST['patientId'];
  $doctorId    = $_POST['doctorId'];
  $invoiceDate = $_POST['invoiceDate'];
  $dueDate     = $_POST['dueDate'];
  $status      = $_POST['status'];
  $notes       = $_POST['notes'];

  // Calculate total amount from invoice items
  $subtotal = 0;
  if (isset($_POST['item_total'])) {
    foreach ($_POST['item_total'] as $item_total) {
      $subtotal += (float)$item_total;
    }
  }

  // Example: tax = 7% of subtotal
  $tax         = $subtotal * 0.07;
  $totalAmount = $subtotal + $tax;

  // Insert into invoices table
  $query_invoice = "INSERT INTO invoices (invoiceId, patientId, doctorId, invoiceDate, dueDate, subtotal, tax, totalAmount, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)";
  $stmt_invoice  = $mysqli->prepare($query_invoice);
  $stmt_invoice->bind_param('siissddsss', $invoiceId, $patientId, $doctorId, $invoiceDate, $dueDate, $subtotal, $tax, $totalAmount, $status, $notes);
  $stmt_invoice->execute();
  $invoice_id = $stmt_invoice->insert_id;
  $stmt_invoice->close();

  // Insert into invoice_items table
  if ($invoice_id && isset($_POST['item_type'])) {
    $item_types   = $_POST['item_type'];
    $item_ids     = $_POST['item_id'];
    $quantities   = $_POST['quantity'];
    $unit_prices  = $_POST['unit_price'];
    $item_totals  = $_POST['item_total'];

    for ($i = 0; $i < count($item_types); $i++) {
      $item_type  = $item_types[$i];
      $item_id    = $item_ids[$i];
      $quantity   = $quantities[$i];
      $unit_price = $unit_prices[$i];
      $item_total = $item_totals[$i];

      // Description lookup
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
      } elseif ($item_type == 'bed_space') {
        $description = 'Bed Space Fee';
        $item_id = null; // No specific ID for a fixed fee
      } elseif ($item_type == 'room') {
        $description = 'Room Fee';
        $item_id = null; // No specific ID for a fixed fee
      }

      $query_item = "INSERT INTO invoice_items (invoiceId, itemName, description, unitCost, quantity) VALUES (?,?,?,?,?)";
      $stmt_item  = $mysqli->prepare($query_item);
      $stmt_item->bind_param('issdi', $invoice_id, $description, $description, $unit_price, $quantity);
      $stmt_item->execute();
      $stmt_item->close();
    }
  }

  // Insert into invoice_payments table
  if ($invoice_id) {
    $bankName  = $_POST['bankName'];
    $country   = $_POST['country'];
    $city      = $_POST['city'];
    $address   = $_POST['address'];
    $iban      = $_POST['iban'];
    $swiftCode = $_POST['swiftCode'];

    $query_payment = "INSERT INTO invoice_payments (invoiceId, bankName, country, city, address, iban, swiftCode) VALUES (?,?,?,?,?,?,?)";
    $stmt_payment  = $mysqli->prepare($query_payment);
    $stmt_payment->bind_param('issssss', $invoice_id, $bankName, $country, $city, $address, $iban, $swiftCode);
    $stmt_payment->execute();
    $stmt_payment->close();
  }

  echo "<script>alert('Invoice Created Successfully'); window.location.href='invoices.php';</script>";
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
            <h4 class="page-title">Add Invoice</h4>
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
                <a href="#">Add Invoice</a>
              </li>
            </ul>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <form method="post">

                    <div class="row">
                      <div class="col-md-12">
                        <h5 class="form-title"><span>Invoice Information</span></h5>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select name="patientId" class="form-control form-select searchable-dropdown mt-5" required>
                            <option value="" selected disabled>Select Patient</option>
                            <?php
                            $ret = "SELECT id, first_name, last_name FROM patients";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($row = $res->fetch_object()) {
                              echo "<option value='{$row->id}'>{$row->first_name} {$row->last_name}</option>";
                            }
                            ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <select name="doctorId" class="form-control form-select searchable-dropdown mt-5" required>
                            <option value="" selected disabled>Select Doctor</option>
                            <?php
                            $ret = "SELECT * FROM login WHERE role='doctor'";
                            $stmt = $mysqli->prepare($ret);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            while ($row = $res->fetch_object()) {
                              echo "<option value='{$row->id}'>{$row->staffname}</option>";
                            }
                            ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input type="date" placeholder="Invoice Date" name="invoiceDate" class="form-control" required>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <input type="date" placeholder="Due Date" name="dueDate" class="form-control" required>
                        </div>
                      </div>
                    </div>

                    <hr>

                    <!-- this is the second row for the invoice items -->
                    <div class="row">

                      <div class="col-md-12">
                        <h5 class="form-title"><span>Invoice Information</span></h5>
                      </div>

                      <div id="invoice-items-container">
                        <div class="row invoice-item">
                          <div class="col-md-3">
                            <div class="form-group">
                              <select name="item_type[]" class="form-control item-type form-select" required>
                                <option value="" selected disabled>Select Item Type</option>
                                <option value="product">Product</option>
                                <option value="service">Service</option>
                                <option value="bed_space">Bed Space</option>
                                <option value="room">Room</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="form-group">
                              <select name="item_id[]" class="form-control item-select form-select" required>
                                <option value="" selected disabled>Select Product/Service</option>
                              </select>
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="form-group">
                              <input type="number" placeholder="Quantity" name="quantity[]" class="form-control quantity" value="1" min="1" required>
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="form-group">
                              <input type="text" name="unit_price[]" class="form-control unit-price" placeholder="Unit Price" readonly>
                            </div>
                          </div>
                          <div class="col-md-2">
                            <div class="form-group">
                              <input type="text" name="item_total[]" class="form-control item-total" placeholder="Total" readonly>
                            </div>
                          </div>
                          <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-danger btn-icon btn-round mx-2 remove-item"><i class="fas fa-trash"></i></button>
                            <button type="button" class="btn btn-success btn-icon btn-round" id="add-item"><i class="fas fa-plus"></i></button>
                          </div>
                        </div>
                      </div>


                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Subtotal" name="subtotal" id="subtotalAmount" class="form-control" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Tax (7%)" name="tax" id="taxAmount" class="form-control" readonly>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <input type="text" placeholder="Total Invoice Amount" name="totalAmount" id="totalInvoiceAmount" class="form-control" readonly required>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="form-group">
                        <select name="status" class="form-control form-select" required>
                          <option value="" selected disabled>Status</option>
                          <option value="Pending">Pending</option>
                          <option value="Paid">Paid</option>
                          <option value="Cancelled">Cancelled</option>
                        </select>
                      </div>
                    </div>
 </div>

                    <!-- Payment Details -->
                     <div class="row">
                    <div class="col-md-12 mt-4">
                      <h5 class="form-title"><span>Payment Details</span></h5>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Bank Name" name="bankName" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Country" name="country" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="City" name="city" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="Address" name="address" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <input type="text" placeholder="IBAN" name="iban" class="form-control" required>
                      </div>
                    </div>
                    <div class="col-md-4 ">
                      <div class="form-group">
                        <input type="text" name="swiftCode" placeholder="SWIFT Code" class="form-control" required>
                      </div>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12 mt-4">
                      <div class="form-group">
                        <textarea name="notes" placeholder="Other Information / Notes" class="form-control"></textarea>
                      </div>
                    </div>
                     </div>

                    <div class="col-md-12 text-center">
                      <button type="submit" name="submit" class="btn btn-primary btn-round btn-icon"><i class="fas fa-save"></i></button>
                    </div>
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
    document.addEventListener('DOMContentLoaded', function() {
      const invoiceItemsContainer = document.getElementById('invoice-items-container');
      const addItemButton = document.getElementById('add-item');
      const subtotalInput = document.getElementById('subtotalAmount');
      const taxInput = document.getElementById('taxAmount');
      const totalInvoiceAmountInput = document.getElementById('totalInvoiceAmount');

      const products = <?php echo json_encode($products); ?>;
      const services = <?php echo json_encode($services); ?>;

      function updateItemOptions(itemRow) {
        const itemTypeSelect = itemRow.querySelector('.item-type');
        const itemSelect = itemRow.querySelector('.item-select');
        const unitPriceInput = itemRow.querySelector('.unit-price');
        const quantityInput = itemRow.querySelector('.quantity');
        const itemTotalInput = itemRow.querySelector('.item-total');

        itemSelect.innerHTML = '<option value="">Select Product/Service</option>';
        unitPriceInput.value = '';
        itemTotalInput.value = '';

        const selectedType = itemTypeSelect.value;
        let items = [];

        if (selectedType === 'product') {
          items = products;
          items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            itemSelect.appendChild(option);
          });
        } else if (selectedType === 'service') {
          items = services;
          items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.service_name;
            itemSelect.appendChild(option);
          });
        } else if (selectedType === 'bed_space') {
          const option = document.createElement('option');
          option.value = 'bed_space_fee'; // A dummy ID for internal tracking
          option.textContent = 'Bed Space Fee';
          itemSelect.appendChild(option);
          unitPriceInput.value = <?php echo json_encode($bed_space_fee); ?>;
          itemSelect.disabled = true; // Disable selection for fixed fees
        } else if (selectedType === 'room') {
          const option = document.createElement('option');
          option.value = 'room_fee'; // A dummy ID for internal tracking
          option.textContent = 'Room Fee';
          itemSelect.appendChild(option);
          unitPriceInput.value = <?php echo json_encode($room_fee); ?>;
          itemSelect.disabled = true; // Disable selection for fixed fees
        }

        itemSelect.onchange = function() {
          const selectedItemId = this.value;
          const selectedItem = items.find(item => item.id == selectedItemId);
          if (selectedItem) {
            unitPriceInput.value = selectedType === 'product' ? selectedItem.sell_price : selectedItem.price;
          } else if (selectedType === 'bed_space') {
            unitPriceInput.value = <?php echo json_encode($bed_space_fee); ?>;
          } else if (selectedType === 'room') {
            unitPriceInput.value = <?php echo json_encode($room_fee); ?>;
          } else {
            unitPriceInput.value = '';
          }
          calculateItemTotal(itemRow);
          calculateTotals();
        };

        // For bed_space and room, trigger calculation immediately as price is fixed
        if (selectedType === 'bed_space' || selectedType === 'room') {
            calculateItemTotal(itemRow);
            calculateTotals();
        }

        quantityInput.oninput = function() {
          calculateItemTotal(itemRow);
          calculateTotals();
        };
      }

      function calculateItemTotal(itemRow) {
        const quantity = parseFloat(itemRow.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(itemRow.querySelector('.unit-price').value) || 0;
        const itemTotal = quantity * unitPrice;
        itemRow.querySelector('.item-total').value = itemTotal.toFixed(2);
      }

      function calculateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.invoice-item').forEach(itemRow => {
          subtotal += parseFloat(itemRow.querySelector('.item-total').value) || 0;
        });
        const tax = subtotal * 0.07;
        const total = subtotal + tax;

        subtotalInput.value = subtotal.toFixed(2);
        taxInput.value = tax.toFixed(2);
        totalInvoiceAmountInput.value = total.toFixed(2);
      }

      // Initial setup for the first item row
      document.querySelectorAll('.invoice-item').forEach(itemRow => {
        updateItemOptions(itemRow);
        itemRow.querySelector('.item-type').addEventListener('change', () => updateItemOptions(itemRow));
        itemRow.querySelector('.remove-item').addEventListener('click', function() {
          itemRow.remove();
          calculateTotals();
        });
      });

      addItemButton.addEventListener('click', function() {
        const newItemRow = document.querySelector('.invoice-item').cloneNode(true);
        newItemRow.querySelectorAll('input, select').forEach(input => input.value = '');
        newItemRow.querySelector('.quantity').value = '1';
        invoiceItemsContainer.appendChild(newItemRow);
        updateItemOptions(newItemRow);
        newItemRow.querySelector('.item-type').addEventListener('change', () => updateItemOptions(newItemRow));
        newItemRow.querySelector('.remove-item').addEventListener('click', function() {
          newItemRow.remove();
          calculateTotals();
        });
        calculateTotals();
      });

      calculateTotals();
    });
  </script>
</body>

</html>
