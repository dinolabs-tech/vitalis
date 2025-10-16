<?php
session_start();
include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';
$provident_fund_id = $_GET['id'] ?? '';
$provident_fund_record = null;

if (!empty($provident_fund_id)) {
  $stmt = $conn->prepare("SELECT pf.*, l.staffname FROM provident_fund pf
    INNER JOIN login l ON pf.employeeId = l.id
     WHERE pf.id = ?");
  $stmt->bind_param("i", $provident_fund_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $provident_fund_record = $result->fetch_assoc();
  } else {
    $error_message = "Provident Fund record not found.";
  }
  $stmt->close();
} else {
  $error_message = "No Provident Fund ID provided.";
}

// Fetch users for dropdown (using login table as per error)
$users = []; // Renaming to $users for clarity, but keeping $employees for backward compatibility in the loop
$result_users = $conn->query("SELECT id, staffname FROM login ORDER BY staffname ASC"); // Simplified query to only fetch from login table
if ($result_users) {
  while ($row = $result_users->fetch_assoc()) {
    $users[] = $row; // Using $users array
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $provident_fund_record) {
  $employee_id = $_POST['employee_id'] ?? '';
  $employee_share = $_POST['employee_share'] ?? '';
  $organization_share = $_POST['organization_share'] ?? '';
  $total_share = $_POST['total_share'] ?? '';
  $notes = $_POST['notes'] ?? '';

  if (empty($employee_id) || empty($employee_share) || empty($organization_share) || empty($total_share)) {
    $error_message = "Please fill in all required fields.";
  } elseif (!is_numeric($employee_share) || $employee_share < 0 || !is_numeric($organization_share) || $organization_share < 0 || !is_numeric($total_share) || $total_share < 0) {
    $error_message = "Share amounts must be non-negative numbers.";
  } else {
    $stmt = $conn->prepare("UPDATE provident_fund SET employeeId = ?, employeeShare = ?, organizationShare = ?, providentFundAmount = ?, description = ? WHERE id = ?");
    $stmt->bind_param("idddsi", $employee_id, $employee_share, $organization_share, $total_share, $notes, $provident_fund_id);

    if ($stmt->execute()) {
      $success_message = "Provident Fund record updated successfully!";


      // Log audit
      $userName = $_SESSION['username'] ?? 'Unknown User'; // Assuming username is available in session
      $action = "Updated Provident Fund record with ID: " . $provident_fund_id . " for staff ID: " . $employee_id;
      $details = json_encode($_POST);
      $conn->query("INSERT INTO audit_logs (userName, action, module) VALUES ('$userName', '$action', '$details')");

      header("Location: provident-fund.php");
      exit();
    } else {
      $error_message = "Error updating Provident Fund record: " . $stmt->error;
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
            <h4 class="page-title">Edit Provident Fund</h4>
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
                <a href="provident-fund.php">Provident Fund</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Provident Fund</a>
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
                  <?php if ($provident_fund_record): ?>
                    <div class="form-group">
                      <select class="form-control form-select searchable-dropdown mt-5" style="border: 1px solid red;" name="employee_id" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($users as $user): ?>
                          <option value="<?php echo $user['id']; ?>" <?php echo ($provident_fund_record['employeeId'] == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['staffname'] ?? ''); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Employee Share" type="text" name="employee_share" value="<?php echo htmlspecialchars($provident_fund_record['employeeShare']); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Organization Share" type="text" name="organization_share" value="<?php echo htmlspecialchars($provident_fund_record['organizationShare']); ?>" required>
                    </div>
                    <div class="form-group">
                      <input class="form-control" style="border: 1px solid red;" placeholder="Total Share" type="text" name="total_share" value="<?php echo htmlspecialchars($provident_fund_record['providentFundAmount']); ?>" required>
                    </div>
                    <div class="form-group">
                      <textarea class="form-control" placeholder="Notes" name="notes" rows="3"><?php echo htmlspecialchars($provident_fund_record['description']); ?></textarea>
                    </div>
                    <div class="m-t-20 text-center">
                      <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
                    </div>
                  <?php else: ?>
                    <p>No Provident Fund record to edit.</p>
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