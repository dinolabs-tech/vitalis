<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once('database/db_connect.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $branch_name = $_POST['branch_name'] ?? '';
  $address = $_POST['address'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $email = $_POST['email'] ?? '';
  $state = $_POST['state'] ?? '';
  $country = $_POST['country'] ?? '';

  // Basic validation
  if (empty($branch_name) || empty($address) || empty($phone) || empty($email) || empty($state) || empty($country)) {
    $error_message = "Please fill in all required fields.";
  } else {
    // Insert into branches table
    $stmt = $conn->prepare("INSERT INTO branches (branch_name, address, phone, email, state, country) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $branch_name, $address, $phone, $email, $state, $country);

    if ($stmt->execute()) {
      $success_message = "Branch added successfully!";
      // Clear form fields
      $_POST = array();
      header("Location: branches.php?success=" . urlencode($success_message));
      exit;
    } else {
      $error_message = "Failed to add branch: " . $stmt->error;
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
            <h4 class="page-title">Add Branch</h4>
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
                <a href="branches.php">Branches</a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Add Branch</a>
              </li>
            </ul>
          </div>


          <div class="row">
            <div class="col-12">
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
                  <h6 class="text-danger m-4 small">All placeholders with red border are compulsory</h6>
                  <hr>
                </div>
                <div class="card-body">
                  <form method="POST" action="" class="row g-3">
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="branch_name" placeholder="Branch Name" style="border:1px solid red;" value="<?php echo htmlspecialchars($_POST['branch_name'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="address" style="border:1px solid red;" placeholder="Address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="phone" placeholder="Phone" style="border:1px solid red;" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="email" placeholder="Email" style="border:1px solid red;" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="state" style="border:1px solid red;" placeholder="State" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
                        <input class="form-control" type="text" name="country" style="border:1px solid red;" placeholder="Country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>">
                      </div>
                    </div>
                    <div class="col-sm-12 mt-3 text-center">
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
