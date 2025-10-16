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
  $product_id = $_POST['product_id'] ?? '';
  $dosage = $_POST['dosage'] ?? '';
  $administration_method = $_POST['administration_method'] ?? '';
  $side_effects = $_POST['side_effects'] ?? '';
  $expiry_date = $_POST['expiry_date'] ?? '';
  $storage_conditions = $_POST['storage_conditions'] ?? '';

  // Basic validation
  if (empty($product_id) || empty($dosage) || empty($administration_method) || empty($expiry_date)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Insert into medications table
    $stmt = $conn->prepare("INSERT INTO medications (product_id, dosage, administration_method, side_effects, expiry_date, storage_conditions) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $product_id, $dosage, $administration_method, $side_effects, $expiry_date, $storage_conditions);

    if ($stmt->execute()) {
      $success_message = "Medication added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: medications.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Failed to add medication: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch products for dropdown (only those with product_type 'medication' or 'supply' that can be medications)
$products = [];
$result_products = $conn->query("SELECT id, name FROM products WHERE product_type IN ('medication', 'supply')");
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
            <h4 class="page-title">Add Medication</h4>
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
                <a href="medications.php">Medications</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Medication</a>
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
                        <select class="form-control form-select" style="border:1px solid red;" name="product_id">
                          <option value="" selected disabled>Select Product</option>
                          <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo (isset($_POST['product_id']) && $_POST['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border:1px solid red;" placeholder="Dosage" type="text" name="dosage" value="<?php echo htmlspecialchars($_POST['dosage'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border:1px solid red;" placeholder="Administration Method" name="administration_method" value="<?php echo htmlspecialchars($_POST['administration_method'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Side Effects" name="side_effects"><?php echo htmlspecialchars($_POST['side_effects'] ?? ''); ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Expiry Date" style="border:1px solid red;" type="date" name="expiry_date" value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Storage Conditions" name="storage_conditions"><?php echo htmlspecialchars($_POST['storage_conditions'] ?? ''); ?></textarea>
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