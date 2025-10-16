<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$product_id = $_GET['id'] ?? '';
$product_record = null;

if (!empty($product_id)) {
  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $product_record = $result->fetch_assoc();
  } else {
    $error_message = "Product record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No product ID provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product_record) {
  $name = $_POST['name'] ?? '';
  $sku = $_POST['sku'] ?? '';
  $location = $_POST['location'] ?? '';
  $unit_price = $_POST['unit_price'] ?? '';
  $sell_price = $_POST['sell_price'] ?? '';
  $qty = $_POST['qty'] ?? '';
  $description = $_POST['description'] ?? '';
  $reorder_level = $_POST['reorder_level'] ?? '';
  $reorder_qty = $_POST['reorder_qty'] ?? '';
  $product_type = $_POST['product_type'] ?? 'supply';

  // Calculate total and profit
  $total = $sell_price * $qty;
  $profit = ($sell_price - $unit_price) * $qty;

  if (empty($name) || empty($sku) || empty($location) || empty($unit_price) || empty($sell_price) || empty($qty) || empty($description) || empty($reorder_level) || empty($reorder_qty)) {
    $error_message = "Please fill in all required fields.";
  } else {
    $stmt = $conn->prepare("UPDATE products SET name = ?, sku = ?, location = ?, unit_price = ?, sell_price = ?, qty = ?, total = ?, profit = ?, description = ?, reorder_level = ?, reorder_qty = ?, product_type = ? WHERE id = ?");
    $stmt->bind_param("sssddiddsssss", $name, $sku, $location, $unit_price, $sell_price, $qty, $total, $profit, $description, $reorder_level, $reorder_qty, $product_type, $product_id);

    if ($stmt->execute()) {
      $success_message = "Product updated successfully!";

      header("Location: products.php");
      exit();

      // Log audit
      $user_id = $_SESSION['user_id'] ?? null;
      $user_name = $_SESSION['username'] ?? 'Guest';
      $action = "Updated product record with ID: " . $product_id . " for item: " . $name;
      $details = json_encode($_POST);
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $module = "Products";
      $conn->query("INSERT INTO audit_logs (user_id, userName, action, module, ipAddress, details) VALUES ('$user_id', '$user_name', '$action', '$module', '$ip_address', '$details')");
    } else {
      $error_message = "Error updating product: " . $stmt->error;
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
            <h4 class="page-title">Edit Product</h4>
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
                <a href="products.php">Products</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Product</a>
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
                    <?php if ($product_record): ?>
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Product Name" type="text" name="name" value="<?php echo htmlspecialchars($product_record['name']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="SKU" type="text" name="sku" value="<?php echo htmlspecialchars($product_record['sku']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Location" type="text" name="location" value="<?php echo htmlspecialchars($product_record['location']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Unit Price" type="text" name="unit_price" value="<?php echo htmlspecialchars($product_record['unit_price']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Sell Price" name="sell_price" value="<?php echo htmlspecialchars($product_record['sell_price']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Quantity" name="qty" value="<?php echo htmlspecialchars($product_record['qty']); ?>" required>
                      </div>
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Description" name="description" rows="3" required><?php echo htmlspecialchars($product_record['description']); ?></textarea>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Reorder Level" name="reorder_level" value="<?php echo htmlspecialchars($product_record['reorder_level']); ?>" required>
                      </div>
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Reorder Quantity" name="reorder_qty" value="<?php echo htmlspecialchars($product_record['reorder_qty']); ?>" required>
                      </div>
                      <div class="form-group">
                        <select class="form-control form-select" name="product_type">
                          <option value="" selected disabled> Select Product Type</option>
                          <option value="medication" <?php echo ($product_record['product_type'] == 'medication') ? 'selected' : ''; ?>>Medication</option>
                          <option value="supply" <?php echo ($product_record['product_type'] == 'supply') ? 'selected' : ''; ?>>Supply</option>
                          <option value="equipment" <?php echo ($product_record['product_type'] == 'equipment') ? 'selected' : ''; ?>>Equipment</option>
                          <option value="service" <?php echo ($product_record['product_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                        </select>
                      </div>
                      <div class="m-t-20 text-center">
                        <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                      </div>
                    <?php else: ?>
                      <p>No product record to edit.</p>
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
</body>

</html>