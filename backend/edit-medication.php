<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$id = '';
$medication_name = '';
$description = '';
$dosage = '';
$error_message = '';
$success_message = '';

// Fetch medication data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $id = $_GET['id'];

  $stmt = $conn->prepare("SELECT m.*, p.name AS product_name FROM medications m JOIN products p ON m.product_id = p.id WHERE m.id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $medication = $result->fetch_assoc();
    $id = $medication['id'];
    $product_id = $medication['product_id'];
    $product_name = $medication['product_name'];
    $dosage = $medication['dosage'];
    $administration_method = $medication['administration_method'];
    $side_effects = $medication['side_effects'];
    $expiry_date = $medication['expiry_date'];
    $storage_conditions = $medication['storage_conditions'];
  } else {
    $error_message = "Medication not found.";
  }
  $stmt->close();
}

// Fetch products for dropdown (only those with product_type 'medication')
$products = [];
$result_products = $conn->query("SELECT id, name FROM products WHERE product_type = 'medication' ORDER BY name ASC");
if ($result_products) {
  while ($row = $result_products->fetch_assoc()) {
    $products[] = $row;
  }
}

// Handle form submission for updating medication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = isset($_POST['id']) ? $_POST['id'] : '';
  // $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : '';
  $dosage = isset($_POST['dosage']) ? $_POST['dosage'] : '';
  $administration_method = isset($_POST['administration_method']) ? $_POST['administration_method'] : '';
  $side_effects = isset($_POST['side_effects']) ? $_POST['side_effects'] : '';
  $expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
  $storage_conditions = isset($_POST['storage_conditions']) ? $_POST['storage_conditions'] : '';

  $stmt = $conn->prepare("UPDATE medications SET product_id = ?, dosage = ?, administration_method = ?, side_effects = ?, expiry_date = ?, storage_conditions = ? WHERE id = ?");
  $stmt->bind_param("isssssi", $id, $dosage, $administration_method, $side_effects, $expiry_date, $storage_conditions, $id);

  if ($stmt->execute()) {
    $success_message = "Medication updated successfully!";
    header("Location: medications.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error updating medication: " . $conn->error;
  }
  $stmt->close();
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
            <h4 class="page-title">Edit Medication</h4>
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
                <a href="#">Edit Medications</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-md-12">
              <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
              <?php endif; ?>
              <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
              <?php endif; ?>

              <div class="card">
                <div class="card-title">
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

                    <div class="col-sm-6">
                      <div class="form-group">
                        <!-- Show product name in a readonly field -->
                        <input class="form-control" type="text" name="product_name" value="<?= htmlspecialchars($product_name ?? '') ?>" readonly>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" style="border: 1px solid red;" placeholder="Dosage" name="dosage" value="<?= htmlspecialchars($dosage ?? '') ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" style="border: 1px solid red;" placeholder="Administration Method" type="text" name="administration_method" value="<?= htmlspecialchars($administration_method ?? '') ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Side Effects" name="side_effects"><?= htmlspecialchars($side_effects ?? '') ?></textarea>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" placeholder="Expiry Date" style="border: 1px solid red;" type="date" name="expiry_date" value="<?= htmlspecialchars($expiry_date ?? '') ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <textarea class="form-control" placeholder="Storage Conditions" name="storage_conditions"><?= htmlspecialchars($storage_conditions ?? '') ?></textarea>
                      </div>
                    </div>

                    <div class="col-md-12 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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