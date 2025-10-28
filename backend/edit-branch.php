<?php
session_start();

include_once('database/db_connect.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

// Fetch branch data if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
  $branch_id = $_GET['id'];

  $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
  $stmt->bind_param("i", $branch_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $branch = $result->fetch_assoc();
    $branch_id = $branch['branch_id'];
    $branch_name = $branch['branch_name'];
    $address = $branch['address'];
    $phone = $branch['phone'];
    $email = $branch['email'];
    $state = $branch['state'];
    $country = $branch['country'];
  } else {
    $error_message = "Branch not found.";
  }
  $stmt->close();
}

// Handle form submission for updating branch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $branch_id = isset($_POST['branch_id']) ? $_POST['branch_id'] : '';
  $branch_name = isset($_POST['branch_name']) ? $_POST['branch_name'] : '';
  $address = isset($_POST['address']) ? $_POST['address'] : '';
  $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
  $email = isset($_POST['email']) ? $_POST['email'] : '';
  $state = isset($_POST['state']) ? $_POST['state'] : '';
  $country = isset($_POST['country']) ? $_POST['country'] : '';

  $stmt = $conn->prepare("UPDATE branches SET branch_name = ?, address = ?, phone = ?, email = ?, state = ?, country = ? WHERE branch_id = ?");
  $stmt->bind_param("ssssssi", $branch_name, $address, $phone, $email, $state, $country, $branch_id);

  if ($stmt->execute()) {
    $success_message = "Branch updated successfully!";
    header("Location: branches.php?success=" . urlencode($success_message));
    exit();
  } else {
    $error_message = "Error updating branch: " . $conn->error;
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
            <h4 class="page-title">Edit Branch</h4>
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
                <a href="branches">Branches</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Edit Branch</a>
              </li>
            </ul>
          </div>
          <div class="card p-3">
            <div class="row">
              <div class="col-12">
                <?php if (!empty($success_message)): ?>
                  <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($error_message)): ?>
                  <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                  <input type="hidden" name="branch_id" value="<?php echo htmlspecialchars($branch_id); ?>">
                  <div class="form-group">
                    <input class="form-control" style="border:1px solid red;" placeholder="Branch Name" name="branch_name" type="text" value="<?= htmlspecialchars($branch_name) ?>">
                  </div>
                  <div class="form-group">
                    <textarea cols="30" rows="4" name="address" style="border:1px solid red;" placeholder="Address" class="form-control"><?= htmlspecialchars($address) ?></textarea>
                  </div>
                  <div class="form-group">
                    <input class="form-control" name="phone" style="border:1px solid red;" placeholder="Contact Info" type="text" value="<?= htmlspecialchars($phone) ?>">
                  </div>
                  <div class="form-group">
                    <input class="form-control" name="email" style="border:1px solid red;" placeholder="Email" type="email" value="<?= htmlspecialchars($email) ?>">
                  </div>
                  <div class="form-group">
                    <input class="form-control" name="state" style="border:1px solid red;" placeholder="State" type="text" value="<?= htmlspecialchars($state) ?>">
                  </div>
                  <div class="form-group">
                    <input class="form-control" name="country" style="border:1px solid red;" placeholder="Country" type="text" value="<?= htmlspecialchars($country) ?>">
                  </div>

                  <div class="m-t-20 text-center">
                    <button class="btn btn-primary btn-icon btn-round"><i class="fas fa-save"></i></button>
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
