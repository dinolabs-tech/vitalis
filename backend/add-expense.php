<?php
include 'includes/config.php';
include 'includes/checklogin.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

// Fetch staff for dropdown
$staff_members = [];
$result_staff = $mysqli->query("SELECT id, staffname FROM login WHERE role != 'patient' ORDER BY staffname ASC");
if ($result_staff) {
  while ($row = $result_staff->fetch_assoc()) {
    $staff_members[] = $row;
  }
}

// Fetch patients for dropdown
$patients = [];
$result_patients = $mysqli->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name ASC");
if ($result_patients) {
  while ($row = $result_patients->fetch_assoc()) {
    $patients[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = $_POST['patient_id'] ?? null; // New patient_id field
  $item = $_POST['item'] ?? '';
  $purchase_from = $_POST['purchase_from'] ?? '';
  $purchase_date = $_POST['purchase_date'] ?? '';
  $purchased_by = $_POST['purchased_by'] ?? '';
  $amount = $_POST['amount'] ?? '';
  $status = $_POST['status'] ?? 'Pending';
  $notes = $_POST['notes'] ?? '';

  if (empty($item) || empty($purchase_from) || empty($purchase_date) || empty($purchased_by) || empty($amount)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Validate amount is a number
    if (!is_numeric($amount) || $amount <= 0) {
      $error_message = "Amount must be a positive number.";
    } else {
      $mysqli->begin_transaction();
      try {
        // Insert into expenses table
        // Assuming 'patient_id' column exists in 'expenses' table
        $stmt = $mysqli->prepare("INSERT INTO expenses (id, itemName, purchaseFrom, purchaseDate, paidBy, amount, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $patient_id, $item, $purchase_from, $purchase_date, $purchased_by, $amount, $status, $notes);

        if (!$stmt->execute()) {
          throw new Exception("Error adding expense: " . $stmt->error);
        }
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // If patient_id is provided, add entry to patient_bills
        if ($patient_id) {
          $stmt_bill = $mysqli->prepare("INSERT INTO patient_bills (patient_id, admission_id, item_type, item_id, description, quantity, unit_price, total_amount, status, bill_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          $item_type = 'expense';
          $quantity = 1;
          $unit_price = $amount;
          $total_amount = $amount;
          $bill_status = 'pending'; // Or map to expense status
          $admission_id = null; // Assuming no direct admission link here

          $stmt_bill->bind_param("isisisddss", $patient_id, $admission_id, $item_type, $expense_id, $item, $quantity, $unit_price, $total_amount, $bill_status, $purchase_date);

          if (!$stmt_bill->execute()) {
            throw new Exception("Failed to add expense to patient bills: " . $stmt_bill->error);
          }
          $stmt_bill->close();
        }

        $mysqli->commit();
        $success_message = "Expense added successfully!";
        if ($patient_id) {
            $success_message .= " And linked to patient bill.";
        }

        // Log audit
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['username'] ?? 'Guest';
        $action = "Added expense for item: " . $item . " (Amount: " . $amount . ")";
        $details = json_encode($_POST);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $module = "Expenses";
        $mysqli->query("INSERT INTO audit_logs (user_id, userName, action, module, ipAddress, details) VALUES ('$user_id', '$user_name', '$action', '$module', '$ip_address', '$details')");

        // Clear form fields after successful submission
        $_POST = array();
        header("Location: expenses.php?success=" . urlencode($success_message));
        exit();
      } catch (Exception $e) {
        $mysqli->rollback();
        $error_message = $e->getMessage();
      }
    }
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
            <h4 class="page-title">Add Expense</h4>
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
                <a href="expenses.php">Expenses</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Expense</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
                <form method="POST" action="">
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
                  <label for="" class="text-danger ms-2">All placeholders with red border are compulsory</label>
                  <div class="form-group">
                    <select class="form-control form-select" name="patient_id">
                      <option value="" selected disabled>Select Patient (Optional)</option>
                      <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo $patient['id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Item" type="text" name="item" value="<?php echo htmlspecialchars($_POST['item'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Purchase From" type="text" name="purchase_from" value="<?php echo htmlspecialchars($_POST['purchase_from'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <input class="form-control" style="border: 1px solid red;" placeholder="Purchase Date" type="date" name="purchase_date" value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')); ?>" required>
                  </div>
                  <div class="form-group">
                    <select class="form-control form-select" style="border: 1px solid red;" name="purchased_by" required>
                      <option value="" selected disabled>Select Staff</option>
                      <?php foreach ($staff_members as $staff): ?>
                        <option value="<?php echo $staff['staffname']; ?>" <?php echo (isset($_POST['purchased_by']) && $_POST['purchased_by'] == $staff['staffname']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($staff['staffname']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <input class="form-control" type="text" placeholder="Amount" style="border: 1px solid red;" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                  </div>
                  <div class="form-group">
                    <select class="form-control form-select" name="status">
                      <option value="" selected disabled>Status</option>
                      <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                      <option value="Approved" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                      <option value="Rejected" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                  </div>
                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-plus"></i></button>
                  </div>
                </form>
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
