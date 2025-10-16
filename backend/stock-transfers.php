<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'pharmacist') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add Stock Transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $from_branch_id = $_POST['from_branch_id'] ?? null;
  $to_branch_id = $_POST['to_branch_id'] ?? null;
  $product_id = $_POST['product_id'] ?? null;
  $quantity = $_POST['quantity'] ?? 0;
  $notes = $_POST['notes'] ?? '';

  if (empty($from_branch_id) || empty($to_branch_id) || empty($product_id) || empty($quantity) || $quantity <= 0) {
    $error_message = "Please fill in all required fields and ensure quantity is positive.";
  } elseif ($from_branch_id == $to_branch_id) {
    $error_message = "Cannot transfer stock to the same branch.";
  } else {
    // Start transaction
    $conn->begin_transaction();


    try {
      // Check if enough stock in from_branch
      $stmt_check_stock = $conn->prepare("SELECT quantity FROM branch_product_inventory WHERE branch_id = ? AND productid = ?");
      $stmt_check_stock->bind_param("ii", $from_branch_id, $product_id);
      $stmt_check_stock->execute();
      $result_check_stock = $stmt_check_stock->get_result();
      $current_stock = 0;
      if ($row = $result_check_stock->fetch_assoc()) {
        $current_stock = (int)$row['quantity'];
      } else {
        // No inventory row found for this branch/product
        error_log("No inventory row: from_branch_id=$from_branch_id, product_id=$product_id");
        throw new Exception("No inventory record found for this product in the source branch.");
      }
      $stmt_check_stock->close();

      // Debug output
      error_log("from_branch_id: $from_branch_id, product_id: $product_id, quantity: $quantity, current_stock: $current_stock");

      if ($current_stock < (int)$quantity) {
        throw new Exception("Insufficient stock in the source branch.");
      }

      // Deduct from source branch inventory
      $stmt_deduct = $conn->prepare("UPDATE branch_product_inventory SET quantity = quantity - ? WHERE branch_id = ? AND productid = ?");
      $stmt_deduct->bind_param("iii", $quantity, $from_branch_id, $product_id);
      if (!$stmt_deduct->execute()) {
        throw new Exception("Error deducting stock from source branch: " . $stmt_deduct->error);
      }
      $stmt_deduct->close();

      // Add to destination branch inventory (UPSERT)
      $stmt_add = $conn->prepare("INSERT INTO branch_product_inventory (branch_id, productid, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
      $stmt_add->bind_param("iiii", $to_branch_id, $product_id, $quantity, $quantity);
      if (!$stmt_add->execute()) {
        throw new Exception("Error adding stock to destination branch: " . $stmt_add->error);
      }
      $stmt_add->close();

      // Record the stock transfer
      $stmt_transfer = $conn->prepare("INSERT INTO stock_transfers (from_branch_id, to_branch_id, product_id, quantity, notes) VALUES (?, ?, ?, ?, ?)");
      $stmt_transfer->bind_param("iiiis", $from_branch_id, $to_branch_id, $product_id, $quantity, $notes);
      if (!$stmt_transfer->execute()) {
        throw new Exception("Error recording stock transfer: " . $stmt_transfer->error);
      }
      $stmt_transfer->close();

      $conn->commit();
      $success_message = "Stock transfer recorded successfully!";
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to complete stock transfer: " . $e->getMessage();
    }
  }
}

// Fetch all stock transfers
$stock_transfers = [];
$sql = "SELECT st.*, 
               fb.branch_name AS from_branch_name, 
               tb.branch_name AS to_branch_name, 
               p.name AS product_name 
        FROM stock_transfers st
        LEFT JOIN branches fb ON st.from_branch_id = fb.branch_id
        LEFT JOIN branches tb ON st.to_branch_id = tb.branch_id
        LEFT JOIN products p ON st.product_id = p.id
        ORDER BY st.transfer_date DESC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $stock_transfers[] = $row;
  }
}

// Fetch branches for dropdowns
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name ASC");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
  }
}

// Fetch products for dropdown
$products = [];
$result_products = $conn->query("SELECT id, name FROM products ORDER BY name ASC");
if ($result_products) {
  while ($row = $result_products->fetch_assoc()) {
    $products[] = $row;
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
            <h3 class="fw-bold mb-3">Stock Transfers</h3>
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
                <a href="#">Stock Transfers</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-stock-transfer.php" class="btn btn-primary btn-round">Add Stock Transfer</a>
              </div>
            <?php endif; ?>
          </div>

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


          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-striped custom-table" id="basic-datatables">
                      <thead>
                        <tr>
                          <th>From Branch</th>
                          <th>To Branch</th>
                          <th>Product</th>
                          <th>Quantity</th>
                          <th>Transfer Date</th>
                          <th>Notes</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($stock_transfers) > 0): ?>
                          <?php foreach ($stock_transfers as $transfer): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($transfer['from_branch_name']); ?></td>
                              <td><?php echo htmlspecialchars($transfer['to_branch_name']); ?></td>
                              <td><?php echo htmlspecialchars($transfer['product_name']); ?></td>
                              <td><?php echo htmlspecialchars($transfer['quantity']); ?></td>
                              <td><?php echo htmlspecialchars($transfer['transfer_date']); ?></td>
                              <td><?php echo htmlspecialchars($transfer['notes']); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="6" class="text-center">No stock transfers found.</td>
                          </tr>
                        <?php endif; ?>
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
  <?php include('components/script.php'); ?>
</body>

</html>