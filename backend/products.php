<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'pharmacist') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = $_POST['name'] ?? '';
  $sku = $_POST['sku'] ?? '';
  $location = $_POST['location'] ?? '';
  $unit_price = $_POST['unit_price'] ?? 0.00;
  $sell_price = $_POST['sell_price'] ?? 0.00;
  $qty = $_POST['qty'] ?? 0;
  $description = $_POST['description'] ?? '';
  $reorder_level = $_POST['reorder_level'] ?? 0;
  $reorder_qty = $_POST['reorder_qty'] ?? 0;
  $product_type = $_POST['product_type'] ?? 'supply';
  $branch_id = $_POST['branch_id'] ?? null;
  $product_id = $_POST['product_id'] ?? null; // For editing

  $total = $sell_price * $qty;
  $profit = ($sell_price - $unit_price) * $qty;

  if (empty($name) || empty($sku) || empty($location) || empty($unit_price) || empty($sell_price) || empty($qty)) {
    $error_message = "Please fill in all required fields.";
  } else {
    if ($product_id) {
      // Update existing product
      $stmt = $conn->prepare("UPDATE products SET name = ?, sku = ?, location = ?, unit_price = ?, sell_price = ?, qty = ?, total = ?, profit = ?, description = ?, reorder_level = ?, reorder_qty = ?, product_type = ?, branch_id = ? WHERE id = ?");
      $stmt->bind_param("sssddiddsiiisi", $name, $sku, $location, $unit_price, $sell_price, $qty, $total, $profit, $description, $reorder_level, $reorder_qty, $product_type, $branch_id, $product_id);
      if ($stmt->execute()) {
        $success_message = "Product updated successfully!";
      } else {
        $error_message = "Error updating product: " . $stmt->error;
      }
      $stmt->close();
    } else {
      // Add new product
      $stmt = $conn->prepare("INSERT INTO products (name, sku, location, unit_price, sell_price, qty, total, profit, description, reorder_level, reorder_qty, product_type, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssddiddsiiis", $name, $sku, $location, $unit_price, $sell_price, $qty, $total, $profit, $description, $reorder_level, $reorder_qty, $product_type, $branch_id);
      if ($stmt->execute()) {
        $success_message = "Product added successfully!";
      } else {
        $error_message = "Error adding product: " . $stmt->error;
      }
      $stmt->close();
    }
  }
}


// Handle Delete Product
if (isset($_POST['delete_id'])) {
  $delete_id = $_POST['delete_id'];

  $conn->begin_transaction();
  try {
    $stmt_product = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt_product->bind_param("i", $delete_id);

    if (!$stmt_product->execute()) {
      throw new Exception("Error deleting product record: " . $stmt_product->error);
    }

    $stmt_product->close();
    $conn->commit();

    $success_message = "Product deleted successfully!";
    header("Location: products.php?success=" . urlencode($success_message));
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    $error_message = "Failed to delete Product: " . $e->getMessage();
    header("Location: products.php?error=" . urlencode($error_message));
    exit;
  }
}

// Check for success/error messages in GET parameters after redirect
if (isset($_GET['success'])) {
  $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
  $error_message = htmlspecialchars($_GET['error']);
}

// Fetch all products
$products = [];
$sql = "SELECT p.*, b.branch_name FROM products p LEFT JOIN branches b ON p.branch_id = b.branch_id ORDER BY p.name ASC";
$result = $conn->query($sql);
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $products[] = $row;
  }
}

// Fetch product data for editing if ID is provided in GET
$edit_product_data = [];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
  $edit_product_id = $_GET['id'];
  $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
  $stmt->bind_param("i", $edit_product_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows === 1) {
    $edit_product_data = $result->fetch_assoc();
  } else {
    $error_message = "Product not found for editing.";
  }
  $stmt->close();
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
            <h3 class="fw-bold mb-3">Products</h3>
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
                <a href="#">Products</a>
              </li>
            </ul>
          </div>

          <div
            class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
              <div class="ms-md-auto py-2 py-md-0">
                <a href="add-product.php" class="btn btn-primary btn-round">Add Product</a>
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
                          <th>Name</th>
                          <th>SKU</th>
                          <th>Location</th>
                          <th>Unit Price</th>
                          <th>Sell Price</th>
                          <th>Qty</th>
                          <th>Total</th>
                          <th>Profit</th>
                          <th>Product Type</th>
                          <th>Branch</th>
                          <th>Created At</th>
                          <th class="text-right">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($products) > 0): ?>
                          <?php foreach ($products as $product): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($product['name']); ?></td>
                              <td><?php echo htmlspecialchars($product['sku']); ?></td>
                              <td><?php echo htmlspecialchars($product['location']); ?></td>
                              <td><?php echo htmlspecialchars($product['unit_price']); ?></td>
                              <td><?php echo htmlspecialchars($product['sell_price']); ?></td>
                              <td><?php echo htmlspecialchars($product['qty']); ?></td>
                              <td><?php echo htmlspecialchars($product['total']); ?></td>
                              <td><?php echo htmlspecialchars($product['profit']); ?></td>
                              <td><?php echo htmlspecialchars($product['product_type']); ?></td>
                              <td><?php echo htmlspecialchars($product['branch_name'] ?? 'N/A'); ?></td>
                              <td><?php echo htmlspecialchars($product['created_at']); ?></td>
                              <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'pharmacist'): ?>
                                <td class="text-right d-flex">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-icon btn-round btn-primary text-white mx-2"><i class="fas fa-edit"></i></a>
                                    <a href="#" data-id="<?php echo $product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" class="btn-icon btn-round btn-danger text-white btn-delete-product"><i class="fas fa-trash"></i> </a>
                                 </td>
                              <?php endif; ?>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="12" class="text-center">No products found.</td>
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
  <!-- Delete Modal -->
  <div id="delete_product_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img src="assets/img/sent.png" alt="" width="50" height="46">
          <h3 id="delete-product-message">Are you sure you want to delete this product record?</h3>
          <div class="m-t-20">
            <a href="#" class="btn btn-white" data-dismiss="modal">Close</a>
            <form id="deleteProductForm" method="POST" action="products.php" style="display: inline;">
              <input type="hidden" name="delete_id" id="delete-product-id">
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
      $('.btn-delete-product').on('click', function(e) {
        e.preventDefault();
        var productId = $(this).data('id');
        var productName = $(this).data('name');
        $('#delete-product-id').val(productId);
        $('#delete-product-message').text("Are you sure you want to delete the product '" + productName + "'?");
        $('#delete_product_modal').modal('show');
      });
    });
  </script>
</body>

</html>
