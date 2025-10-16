<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$expense_id = $_GET['id'] ?? '';
$expense_record = null;

if (!empty($expense_id)) {
  $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
  $stmt->bind_param("i", $expense_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $expense_record = $result->fetch_assoc();
  } else {
    $error_message = "Expense record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No expense ID provided.";
}

// Fetch staff for dropdown
$staff_members = [];
$result_staff = $conn->query("SELECT id, staffname FROM login WHERE role != 'patient' ORDER BY staffname ASC");
if ($result_staff) {
  while ($row = $result_staff->fetch_assoc()) {
    $staff_members[] = $row;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $expense_record) {
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
    $stmt = $conn->prepare("UPDATE expenses SET itemName = ?, purchaseFrom = ?, purchaseDate = ?, paidBy = ?, amount = ?, status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ssssdsss", $item, $purchase_from, $purchase_date, $purchased_by, $amount, $status, $notes, $expense_id);

    if ($stmt->execute()) {
      $success_message = "Expense updated successfully!";


      // Log audit
      $user_id = $_SESSION['user_id'] ?? null;
      $user_name = $_SESSION['username'] ?? 'Guest';
      $action = "Updated expense record with ID: " . $expense_id . " for item: " . $item;
      $details = json_encode($_POST);
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $module = "Expenses";
      $conn->query("INSERT INTO audit_logs (user_id, userName, action, module, ipAddress, details) VALUES ('$user_id', '$user_name', '$action', '$module', '$ip_address', '$details')");

      header("Location: expenses.php");
      exit();
    } else {
      $error_message = "Error updating expense: " . $stmt->error;
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
            <h4 class="page-title">Edit Expense</h4>
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
                <a href="#">Edit Expenses</a>
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
                  <?php if ($expense_record): ?>
                    <div class="form-group">
                      <input class="form-control" style="border:1px solid red;" placeholder="Item" type="text" name="item" value="<?php echo htmlspecialchars($expense_record['itemName']); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border:1px solid red;" placeholder="Purchase From" type="text" name="purchase_from" value="<?php echo htmlspecialchars($expense_record['purchaseFrom']); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="date" style="border:1px solid red;" placeholder="Purchase Date" name="purchase_date" value="<?php echo htmlspecialchars($expense_record['purchaseDate']); ?>" required>

                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" style="border:1px solid red;" name="purchased_by" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($staff_members as $staff): ?>
                          <option value="<?php echo htmlspecialchars($staff['staffname']); ?>" <?php echo ($expense_record['paidBy'] == $staff['staffname']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($staff['staffname']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <input class="form-control" type="text" placeholder="Amount" style="border:1px solid red;" name="amount" value="<?php echo htmlspecialchars($expense_record['amount']); ?>" required>
                    </div>
                    <div class="form-group">
                      <select class="form-control form-select" name="status">
                        <option value="Pending" <?php echo ($expense_record['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($expense_record['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo ($expense_record['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($expense_record['notes']); ?></textarea>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  <?php else: ?>
                    <p>No expense record to edit.</p>
                  <?php endif; ?>
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