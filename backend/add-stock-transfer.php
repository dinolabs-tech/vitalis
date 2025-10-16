<?php
session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin or pharmacist role
if (!isset($_SESSION['loggedin']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'pharmacist')) {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $from_branch_id = $_POST['from_branch_id'] ?? '';
  $to_branch_id = $_POST['to_branch_id'] ?? '';
  $product_id = $_POST['product_id'] ?? '';
  $quantity = $_POST['quantity'] ?? '';
  $notes = $_POST['notes'] ?? '';

  // Basic validation
  if (empty($from_branch_id) || empty($to_branch_id) || empty($product_id) || empty($quantity)) {
    $error_message = "Please fill in all required fields.";
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
        $current_stock = $row['quantity'];
      }
      $stmt_check_stock->close();

      if ($current_stock < $quantity) {
        throw new Exception("Insufficient stock in the source branch. Available: " . $current_stock);
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
      // Clear form fields
      $_POST = array();
      header("Location: stock-transfers.php?success=" . urlencode($success_message));
      exit();
    } catch (Exception $e) {
      $conn->rollback();
      $error_message = "Failed to transfer stock: " . $e->getMessage();
    }
  }
}

// Fetch branches for dropdowns
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
  }
}

// Fetch products for dropdown
$products = [];
$result_products = $conn->query("SELECT id, name FROM products");
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
            <h4 class="page-title">Add Stock Transfer</h4>
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
                <a href="stock-transfers.php">Stock Transfers</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Stock Transfer</a>
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6><hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="">
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="from_branch_id">
                          <option value="" selected disabled>Select Source Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['from_branch_id']) && $_POST['from_branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="to_branch_id">
                          <option value="" selected disabled>Select Destination Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['to_branch_id']) && $_POST['to_branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="product_id">
                          <option value="" selected disabled>Select Product</option>
                          <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <input class="form-control" placeholder="Quantity" style="border: 1px solid red;" type="number" name="quantity" value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" min="1">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                      </div>
                    </div>

                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary submit-btn btn-round">Transfer Stock</button>
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
</body>

</html>