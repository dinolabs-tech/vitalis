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
  $branch_id = $_POST['branch_id'] ?? null;

  // Basic validation
  if (empty($name) || empty($sku) || empty($location) || empty($unit_price) || empty($branch_id) || empty($sell_price) || empty($qty) || empty($description) || empty($reorder_level) || empty($reorder_qty)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Calculate total and profit
    $total = $unit_price * $qty;
    $profit = ($sell_price - $unit_price) * $qty;

    // Insert into products table
    $stmt = $conn->prepare("INSERT INTO products (name, sku, location, unit_price, sell_price, qty, total, profit, description, reorder_level, reorder_qty, product_type, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdidddsiisi", $name, $sku, $location, $unit_price, $sell_price, $qty, $total, $profit, $description, $reorder_level, $reorder_qty, $product_type, $branch_id);

    if ($stmt->execute()) {
      $success_message = "Product added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: products.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Failed to add product: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch branches for dropdown
$branches = [];
$result_branches = $conn->query("SELECT branch_id, branch_name FROM branches");
if ($result_branches) {
  while ($row = $result_branches->fetch_assoc()) {
    $branches[] = $row;
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
            <h4 class="page-title">Add Product</h4>
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
                <a href="#">Add Product</a>
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
                  <form method="POST" action="" class="row g-3">
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Product Name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="SKU" name="sku" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Location" type="text" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Unit Price" type="text" name="unit_price" value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="sell_price" style="border: 1px solid red;" placeholder="Sell Price" value="<?php echo htmlspecialchars($_POST['sell_price'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Quantity" name="qty" value="<?php echo htmlspecialchars($_POST['qty'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <textarea class="form-control" style="border: 1px solid red;" placeholder="Description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Reorder Level" name="reorder_level" value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Reorder Quantity" name="reorder_qty" value="<?php echo htmlspecialchars($_POST['reorder_qty'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="product_type">
                          <option value="" selected disabled>Product Type</option>
                          <option value="supply" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'supply') ? 'selected' : ''; ?>>Supply</option>
                          <option value="medication" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'medication') ? 'selected' : ''; ?>>Medication</option>
                          <option value="equipment" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'equipment') ? 'selected' : ''; ?>>Equipment</option>
                          <option value="service" <?php echo (isset($_POST['product_type']) && $_POST['product_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <select class="form-control form-select" style="border: 1px solid red;" name="branch_id">
                          <option value="" selected disabled>Select Branch</option>
                          <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>" <?php echo (isset($_POST['branch_id']) && $_POST['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($branch['branch_name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
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
</body>

</html>